<?php

namespace Gamn2090\DynamicPermissions\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMultipleFeatures
{
    /**
     * Handle an incoming request.
     *
     * @param string $features Comma-separated feature slugs
     * @param string $mode 'any' or 'all' (default: 'any')
     */
    public function handle(Request $request, Closure $next, string $features, string $mode = 'any'): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user = auth()->user();

        if (!method_exists($user, 'hasFeatureAccess')) {
            return response()->json([
                'message' => 'User model must use HasDynamicPermissions trait',
            ], 500);
        }

        $featureList = array_map('trim', explode(',', $features));

        $hasAccess = $mode === 'all'
            ? $user->hasAllFeatureAccess($featureList)
            : $user->hasAnyFeatureAccess($featureList);

        if (!$hasAccess) {
            return response()->json([
                'message' => "Forbidden. You need {$mode} of these features: " . implode(', ', $featureList),
                'required_features' => $featureList,
                'mode' => $mode,
            ], 403);
        }

        return $next($request);
    }
}
