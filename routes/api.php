<?php

use App\Http\Controllers\FunnelController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadTransactionController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\N8nAgentController;
use App\Http\Controllers\N8nIntegrationController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('/funnels', [FunnelController::class, 'index']);
    Route::post('/funnels', [FunnelController::class, 'store']);
    Route::get('/funnels/{id}', [FunnelController::class, 'leadsByStages']);
    Route::put('/funnels/{id}', [FunnelController::class, 'update']);
    Route::delete('/funnels/{id}', [FunnelController::class, 'destroy']);

    Route::get('/funnels/{funnelId}/stages', [StageController::class, 'index']);
    Route::post('/funnels/{funnelId}/stages', [StageController::class, 'store']);
    Route::get('/funnels/{funnelId}/stages/{stageId}', [StageController::class, 'show']);
    Route::put('/funnels/{funnelId}/stages/{stageId}', [StageController::class, 'update']);
    Route::delete('/funnels/{funnelId}/stages/{stageId}', [StageController::class, 'destroy']);

    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store']);
    Route::get('/leads/{leadId}', [LeadController::class, 'show']);
    Route::put('/leads/{leadId}', [LeadController::class, 'update']);
    Route::delete('/leads/{stageId}', [LeadController::class, 'destroy']);
    Route::post('/leads/{lead}/move-stage', [LeadController::class, 'moveToStage']);
    Route::put('/leads/{id}/tags', [LeadController::class, 'updateTags']);


    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);
    Route::delete('/tags/{id}', [TagController::class, 'destroy']);

    Route::get('/companies/{companyId}/transactions', [LeadTransactionController::class, 'transactionsByCompany']);
    Route::get('/leads/{leadId}/transactions', [LeadTransactionController::class, 'transactionsByLead']);

    Route::get('/metrics/leads-breakdown', [MetricsController::class, 'dashboard']);

    Route::get('/integrations/available', [IntegrationController::class, 'available']);
    Route::post('/integrations/n8n-integrations', [N8nIntegrationController::class, 'createIntegration']);
    Route::put('/integrations/n8n-integrations/configure', [N8nIntegrationController::class, 'configure']);
    Route::get('/integrations/n8n-integrations/data', [N8nIntegrationController::class, 'configureData']);
    Route::get('/integrations/n8n/workflows', [N8nIntegrationController::class, 'fetchWorkflows']);
    
    Route::get('/integrations/agents/n8n', [N8nAgentController::class, 'index']);
    Route::get('/integrations/agents/n8n/executions/{agent}', [N8nAgentController::class, 'fetchExecutions']);
});
