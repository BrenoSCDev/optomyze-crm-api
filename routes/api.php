<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\LeadDocumentController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\FunnelController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationReportController;
use App\Http\Controllers\CustomProductFieldController;
use App\Http\Controllers\GoogleAdsIntegrationController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadTransactionController;
use App\Http\Controllers\MetaAdsIntegrationController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\N8nAgentController;
use App\Http\Controllers\N8nIntegrationController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LeadProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleDocController;
use Illuminate\Http\Request;
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


Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-pwd', [AuthController::class, 'forgotPassword']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/update', [AuthController::class, 'updateProfile']);
    Route::post('/auth/reset/password', [AuthController::class, 'resetPasswordAuth']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

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
    Route::put('/funnels/{funnelId}/stages/{stageId}/move', [StageController::class, 'moveOrder']);

    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store']);
    Route::get('/leads/{lead}', [LeadController::class, 'show']);
    Route::put('/leads/{lead}', [LeadController::class, 'update']);
    Route::delete('/leads/{stageId}', [LeadController::class, 'destroy']);
    Route::post('/leads/{lead}/move-stage', [LeadController::class, 'moveToStage']);
    Route::put('/leads/{id}/tags', [LeadController::class, 'updateTags']);
    Route::post('/leads/{lead}/assign', [LeadController::class, 'assignUser']);

    Route::post('/leads/{lead}/products', [LeadProductController::class, 'store']);

    Route::post('/leads/{lead}/sales', [SaleController::class, 'store']);

    Route::delete('/sales/{sale}', [SaleController::class, 'destroy']);

    Route::post('/sales/{sale}/docs', [SaleDocController::class, 'store']);
    Route::delete('/sales/docs/{doc}', [SaleDocController::class, 'destroy']);

    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);
    Route::delete('/tags/{id}', [TagController::class, 'destroy']);

    Route::get('/tokens', [ApiTokenController::class, 'index']);
    Route::post('/tokens', [ApiTokenController::class, 'generate']);
    Route::delete('/tokens/{id}', [ApiTokenController::class, 'destroy']);

    Route::get('/companies/{companyId}/transactions', [LeadTransactionController::class, 'transactionsByCompany']);
    Route::get('/leads/{leadId}/transactions', [LeadTransactionController::class, 'transactionsByLead']);

    Route::get('/metrics/leads-breakdown', [MetricsController::class, 'dashboard']);

    Route::get('/integrations/available', [IntegrationController::class, 'available']);

    Route::post('/integrations/n8n-integrations', [N8nIntegrationController::class, 'createIntegration']);
    Route::put('/integrations/n8n-integrations/configure', [N8nIntegrationController::class, 'configure']);
    Route::get('/integrations/n8n-integrations/data', [N8nIntegrationController::class, 'configureData']);
    Route::get('/integrations/n8n/workflows', [N8nIntegrationController::class, 'fetchWorkflows']);

    Route::post('/integrations/meta-ads', [MetaAdsIntegrationController::class, 'createIntegration']);
    Route::put('/integrations/meta-ads/configure', [MetaAdsIntegrationController::class, 'configure']);
    Route::get('/integrations/meta-ads/fetch-meta-data', [MetaAdsIntegrationController::class, 'fetchMetaData']);

    Route::post('/integrations/google-ads', [GoogleAdsIntegrationController::class, 'createIntegration']);
    Route::put('/integrations/google-ads/configure', [GoogleAdsIntegrationController::class, 'configure']);
    Route::get('/integrations/google-ads/fetch-google-data', [GoogleAdsIntegrationController::class, 'fetchGoogleData']);
    
    Route::get('/integrations/agents/n8n', [N8nAgentController::class, 'index']);
    Route::get('/integrations/agents/n8n/executions/{agent}', [N8nAgentController::class, 'fetchExecutions']);

    Route::post('/conversation-reports', [ConversationReportController::class, 'store']);
    Route::get('/conversation-reports/lead/{leadId}', [ConversationReportController::class, 'getByLead']);
    Route::get('/conversation-reports/agent/{agentId}', [ConversationReportController::class, 'getByAgent']);

    Route::get('/tasks', [TaskController::class, 'companyTasks']);
    Route::post('/tasks', [TaskController::class, 'store']);       
    Route::get('/tasks/{task}', [TaskController::class, 'show']);    
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']); 

    Route::post('/lead-documents', [LeadDocumentController::class, 'store']);
    Route::delete('/lead-documents/{leadDocument}', [LeadDocumentController::class, 'destroy']);

    Route::apiResource('products', ProductController::class);
    Route::post('/products/{id}/toggle-active', [ProductController::class, 'toggleActive']);

    Route::prefix('products/{product}')->group(function () {
        Route::get('images', [ProductImageController::class, 'index']);
        Route::post('images', [ProductImageController::class, 'store']);
    });

    Route::put('/product-images/{productImage}', [ProductImageController::class, 'update']);
    Route::post('/product-images/{productImage}/primary', [ProductImageController::class, 'setPrimary']);
    Route::post('/product-images/reorder', [ProductImageController::class, 'reorder']);
    Route::delete('/product-images/{productImage}', [ProductImageController::class, 'destroy']);

    Route::get('/custom-product-fields', [CustomProductFieldController::class, 'index']);
    Route::get('/custom-product-fields/{customProductField}', [CustomProductFieldController::class, 'show']);
    Route::post('/custom-product-fields', [CustomProductFieldController::class, 'store']);
    Route::put('/custom-product-fields/{customProductField}', [CustomProductFieldController::class, 'update']);
    Route::delete('/custom-product-fields/{customProductField}', [CustomProductFieldController::class, 'destroy']);

    Route::prefix('products/{product}')->group(function () {
        // Route::get('/custom-fields', [ProductController::class, 'index']);
        Route::post('/custom-fields', [ProductController::class, 'storeCustomField']);
        Route::put('/custom-fields/{fieldKey}', [ProductController::class, 'updateCustomField']);
        Route::delete('/custom-fields/{fieldKey}', [ProductController::class, 'destroyCustomField']);
    });


    Route::get('/settings-panel', [SettingsController::class, 'panel']);
    
});


Route::middleware('verify.api.token')->prefix('v1')->group(function () {
    Route::get('/protected', function(Request $request) {
        return response()->json([
            'message' => 'Access granted',
            'company' => $request->company
        ]);
    });

    Route::post('/leads', [LeadController::class, 'apiStore']);
    
    Route::post('/conversation-reports', [ConversationReportController::class, 'store']);
});
