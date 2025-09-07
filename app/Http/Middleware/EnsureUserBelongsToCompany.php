<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Check if route has company parameter
        $companyId = $request->route('company');
        if ($companyId && $user->company_id != $companyId) {
            return response()->json([
                'message' => 'Access denied. You can only access resources from your company.'
            ], 403);
        }

        // Check if route has user parameter
        $userId = $request->route('user');
        if ($userId) {
            $targetUser = \App\Models\User::find($userId);
            if ($targetUser && $user->company_id != $targetUser->company_id) {
                return response()->json([
                    'message' => 'Access denied. You can only manage users from your company.'
                ], 403);
            }
        }

        // Check if route has funnel parameter
        $funnelId = $request->route('funnel');
        if ($funnelId) {
            $funnel = \App\Models\Funnel::find($funnelId);
            if ($funnel && $user->company_id != $funnel->company_id) {
                return response()->json([
                    'message' => 'Access denied. You can only access funnels from your company.'
                ], 403);
            }
        }

        return $next($request);
    }
}