<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Funnel;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FunnelController extends Controller
{
    /**
     * Display a listing of funnels for the authenticated user's company.
     */
    public function index()
    {
        $user = Auth::user();

        $funnels = Funnel::fromCompany($user->company_id)->get();

        return response()->json($funnels);
    }

    /**
     * Store a newly created funnel in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
            'settings'    => 'nullable|array',
        ]);

        $funnel = Funnel::create([
            'company_id'  => $user->company_id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
            'created_by'  => $user->id,
            'settings'    => $validated['settings'] ?? null,
        ]);

        return response()->json($funnel, 201);
    }

    /**
     * Display the specified funnel (only if belongs to company).
     */
    public function show($id)
    {
        $user = Auth::user();

        $funnel = Funnel::fromCompany($user->company_id)->findOrFail($id);

        return response()->json($funnel);
    }

    /**
     * Update the specified funnel.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $funnel = Funnel::fromCompany($user->company_id)->findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
            'settings'    => 'nullable|array',
        ]);

        $funnel->update($validated);

        return response()->json($funnel);
    }

    /**
     * Remove the specified funnel.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $funnel = Funnel::fromCompany($user->company_id)->findOrFail($id);

        $funnel->delete();

        return response()->json(['message' => 'Funnel deleted successfully']);
    }

    public function leadsByStages($id)
    {
        $user = Auth::user();

        $funnel = Funnel::with([
            'stages.activeLeads' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        $tags = Tag::fromCompany($user->company_id)->get();

        return response()->json([
            'funnel' => $funnel,
            'tags'   => $tags,
        ]);
    }
}
