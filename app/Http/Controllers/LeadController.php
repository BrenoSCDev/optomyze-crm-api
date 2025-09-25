<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    /**
     * Display a listing of leads.
     */
    public function index(Request $request)
    {
        $query = Lead::query();

        // Optional: filter by company, funnel, stage, status, etc.
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $leads = $query->paginate(20);

        return response()->json($leads);
    }

    /**
     * Store a newly created lead.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(Lead::validationRules());

        // Create the lead
        $lead = Lead::create($validated);

        // Create a lead creation transaction
        LeadTransaction::createLeadCreation(
            $lead,
            Auth::user(), // current authenticated user (can be null if API token or automated)
            LeadTransaction::SOURCE_MANUAL, // or another source if you want
            [] // optional metadata array
        );

        return response()->json([
            'message' => 'Lead created successfully',
            'data' => $lead
        ], 201);
    }

    /**
     * Display a specific lead.
     */
    public function show(Lead $lead)
    {
        $transactions = LeadTransaction::where('lead_id', $lead->id)->get();
        return response()->json([
            'lead' => $lead,
            'transactions' => $transactions
        ]);
    }

    /**
     * Update an existing lead.
     */
    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate(Lead::updateValidationRules());

        $lead->update($validated);

        return response()->json([
            'message' => 'Lead updated successfully',
            'data' => $lead
        ]);
    }

    /**
     * Remove a lead (soft delete).
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();

        return response()->json([
            'message' => 'Lead deleted successfully'
        ]);
    }

     /**
     * Move a lead to a new stage.
     */
    public function moveToStage(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'stage_id' => 'required|exists:stages,id',
        ]);

        $lead->moveToStage($validated['stage_id']);

        return response()->json([
            'message' => 'Lead moved successfully',
            'data' => $lead->fresh()
        ]);
    }

    /**
     * Update tags for a specific lead.
     */
    public function updateTags(Request $request, $id)
    {
        $request->validate([
            'tags' => 'required|array', // Expect an array of tags
        ]);

        $user = Auth::user();

        $lead = Lead::fromCompany($user->company_id)->findOrFail($id);

        // Save tags as JSON (Laravel casts arrays automatically if $casts is set in model)
        $lead->tags = $request->input('tags');
        $lead->save();

        return response()->json([
            'message' => 'Tags updated successfully.',
            'lead' => $lead
        ], 200);
    }

    //////////////////// INTERNAL CRM API METHODS //////////////////////////

    /**
     * Store a newly created lead via API token authentication.
     */
    public function apiStore(Request $request)
    {
        // Validate only personal lead data + funnel_id
        $validated = $request->validate([
            'funnel_id' => 'required|exists:funnels,id',
            'assigned_to' => 'nullable|exists:users,id',
            'external_id' => 'nullable|string|max:255',
            'source_platform' => 'nullable|string|max:50',
            'source_type' => 'nullable|string|max:50',
            'workflow_id' => 'nullable|string|max:255',
            'automation_name' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'ddi' => 'nullable|string|max:20',
            'username' => 'nullable|string|max:100',
            'platform_user_id' => 'nullable|string|max:255',
            'status' => 'nullable|in:new,contacted,qualified,unqualified,converted,lost',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'estimated_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'contact_methods' => 'nullable|array',
            'preferred_contact_method' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:5',
            'ai_data' => 'nullable|array',
            'platform_data' => 'nullable|array',
            'initial_message' => 'nullable|string',
            'ai_score' => 'nullable|numeric|between:0,100',
            'tags' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'settings' => 'nullable|array',
            'notes' => 'nullable|string',
            'webhook_data' => 'nullable|array',
            'webhook_source' => 'nullable|string|max:100',
            'sync_enabled' => 'boolean',
        ]);

        $company = $request->get('company');
        if (!$company) {
            return response()->json(['message' => 'Unauthorized: no company found for token'], 401);
        }

        $funnel = \App\Models\Funnel::findOrFail($validated['funnel_id']);
        $stage = $funnel->stages()->where('type', 'entry')->first();

        if (!$stage) {
            return response()->json([
                'message' => 'No entry stage found for this funnel'
            ], 422);
        }

        // 🔍 Check if lead already exists in this company
        $existingLead = Lead::where('company_id', $company->id)
            ->where('first_name', $validated['first_name'] ?? null)
            ->where('last_name', $validated['last_name'] ?? null)
            ->where('email', $validated['email'] ?? null)
            ->where('phone', $validated['phone'] ?? null)
            ->first();

        if ($existingLead) {
            return response()->json([
                'message' => 'Duplicate lead detected',
                'data' => $existingLead
            ], 200); // returning 200 since it's not really an error
        }

        $leadData = array_merge($validated, [
            'company_id' => $company->id,
            'stage_id' => $stage->id,
        ]);

        $lead = Lead::create($leadData);

        return response()->json([
            'message' => 'Lead created successfully',
            'data' => $lead
        ], 201);
    }

}
