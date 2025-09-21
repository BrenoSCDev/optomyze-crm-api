<?php

namespace App\Http\Controllers;

use App\Models\N8nIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IntegrationController extends Controller
{
    /**
     * Return available integrations for the authenticated user's company
     */
    public function available(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'message' => 'User does not belong to a company.'
            ], 400);
        }

        $integration = N8nIntegration::where('company_id', $user->company_id)
            ->active()
            ->first();

        if (!$integration) {
            return response()->json([
                'n8n_available' => false,
            ], 200);
        }

        return response()->json([
            'n8n_available' => true,
        ], 200);
    }
}
