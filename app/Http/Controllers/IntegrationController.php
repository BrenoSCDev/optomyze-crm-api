<?php

namespace App\Http\Controllers;

use App\Models\GoogleAdsIntegration;
use App\Models\MetaAdsIntegration;
use App\Models\N8nIntegration;
use App\Models\WhatsAppEvoIntegration;
use App\Models\WhatsAppInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IntegrationController extends Controller
{
    /**
     * Return available integrations for the authenticated user's company
     */
    public function available()
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'message' => 'User does not belong to a company.'
            ], 400);
        }

        // Mapeamento de integrações => Model
        $integrations = [
            'n8n'      => N8nIntegration::class,
            'meta_ads' => MetaAdsIntegration::class,
            'google_ads' => GoogleAdsIntegration::class,
            'wpp_evo' => WhatsAppEvoIntegration::class,
            // 'hubspot'   => HubspotIntegration::class,
            // adicionar mais aqui
        ];

        $availability = [];

        foreach ($integrations as $key => $model) {
            $availability[$key . '_available'] = $model::where('company_id', $user->company_id)
                ->active()
                ->exists();
        }

        return response()->json($availability, 200);
    }

    public function availableWithSettings()
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return response()->json([
                'message' => 'User does not belong to a company.'
            ], 400);
        }

        // Integration key => Model mapping
        $integrations = [
            'wpp_evo'    => WhatsAppEvoIntegration::class,
        ];

        $availability = [];

        foreach ($integrations as $key => $model) {
            $isAvailable = $model::where('company_id', $user->company_id)
                ->active()
                ->exists();

            $availability[$key . '_available'] = $isAvailable;

            // Special case: WhatsApp Evolution
            if ($key === 'wpp_evo' && $isAvailable) {

                $integration = $model::where('company_id', $user->company_id)
                    ->active()
                    ->first();

                $availability['wpp_evo_instances'] = WhatsAppInstance::where(
                    'company_id',
                    $user->company_id
                )
                ->where(
                    'whats_app_evo_integration_id',
                    $integration->id
                )
                ->select(
                    'instance_name', 'id', 'created_at'
                )
                ->get();
            }
        }

        return response()->json($availability, 200);
    }

}
