<?php

namespace gamn2090\DynamicPermissions\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeature
{
	/**
	 * Handle an incoming request.
	 */
	public function handle(Request $request, Closure $next, string $feature): Response
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

		if (!$user->hasFeatureAccess($feature)) {
			return response()->json([
				'message' => 'Forbidden. You do not have access to this feature.',
				'required_feature' => $feature,
				'user_features' => config('app.debug') ? $user->getAccessibleFeatures()->pluck('slug') : null,
			], 403);
		}

		return $next($request);
	}
}
