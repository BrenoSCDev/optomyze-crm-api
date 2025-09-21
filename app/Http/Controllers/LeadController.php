<?php

namespace App\Http\Controllers;

use App\Models\Lead;
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

        $lead = Lead::create($validated);

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
        return response()->json($lead);
    }

    /**
     * Update an existing lead.
     */
    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate(Lead::validationRules());

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
            'stage_id' => 'required|integer|exists:stages,id',
        ]);

        $success = $lead->moveToStage($validated['stage_id']);

        if ($success) {
            return response()->json([
                'message' => 'Lead moved successfully',
                'data' => $lead->fresh()
            ]);
        }

        return response()->json([
            'message' => 'Invalid stage for this lead\'s funnel'
        ], 422);
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
}
