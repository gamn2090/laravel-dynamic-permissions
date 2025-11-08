<?php

namespace gamn2090\DynamicPermissions\Http\Controllers;

use gamn2090\DynamicPermissions\Models\UserFeatureOverride;
use gamn2090\DynamicPermissions\Services\PermissionCompiler;
use gamn2090\DynamicPermissions\Services\SidebarBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserFeatureController extends Controller
{
	public function __construct(
		protected PermissionCompiler $compiler,
		protected SidebarBuilder $sidebarBuilder
	) {}

	/**
	 * Get authenticated user's accessible features.
	 */
	public function myFeatures(Request $request): JsonResponse
	{
		$user = $request->user();

		if (!method_exists($user, 'getAccessibleFeatures')) {
			return response()->json([
				'message' => 'User model must use HasDynamicPermissions trait',
			], 500);
		}

		$features = $user->getAccessibleFeatures();

		return response()->json([
			'features' => $features,
			'count' => $features->count(),
		]);
	}

	/**
	 * Get authenticated user's sidebar.
	 */
	public function mySidebar(Request $request): JsonResponse
	{
		$user = $request->user();

		if (!method_exists($user, 'getSidebarFeatures')) {
			return response()->json([
				'message' => 'User model must use HasDynamicPermissions trait',
			], 500);
		}

		$options = [
			'include_grandparents' => $request->boolean('include_grandparents', true),
			'include_parents' => $request->boolean('include_parents', true),
			'only_with_children' => $request->boolean('only_with_children', false),
		];

		$sidebar = $user->getSidebarFeatures($options);

		return response()->json($sidebar);
	}

	/**
	 * Check if user has specific feature access.
	 */
	public function checkAccess(Request $request): JsonResponse
	{
		$validated = $request->validate([
			'feature' => 'required|string|exists:features,slug',
		]);

		$user = $request->user();
		$hasAccess = $user->hasFeatureAccess($validated['feature']);

		return response()->json([
			'feature' => $validated['feature'],
			'has_access' => $hasAccess,
		]);
	}

	/**
	 * Get user's feature overrides.
	 */
	public function getOverrides(Request $request, int $userId): JsonResponse
	{
		$overrides = UserFeatureOverride::where('user_id', $userId)
			->with(['feature', 'grantedBy'])
			->active()
			->get();

		return response()->json($overrides);
	}

	/**
	 * Grant feature access to a user.
	 */
	public function grantAccess(Request $request, int $userId): JsonResponse
	{
		$validated = $request->validate([
			'feature_slug' => 'required|string|exists:features,slug',
			'reason' => 'nullable|string|max:500',
			'expires_at' => 'nullable|date|after:now',
		]);

		$userModel = config('dynamic-permissions.models.user');
		$user = $userModel::findOrFail($userId);

		if (!method_exists($user, 'grantFeatureAccess')) {
			return response()->json([
				'message' => 'User model must use HasDynamicPermissions trait',
			], 500);
		}

		$override = $user->grantFeatureAccess(
			$validated['feature_slug'],
			$request->user()->id,
			$validated['reason'] ?? null,
			isset($validated['expires_at']) ? new \DateTime($validated['expires_at']) : null
		);

		return response()->json([
			'message' => 'Feature access granted successfully',
			'override' => $override->load('feature'),
		], 201);
	}

	/**
	 * Revoke feature access from a user.
	 */
	public function revokeAccess(Request $request, int $userId): JsonResponse
	{
		$validated = $request->validate([
			'feature_slug' => 'required|string|exists:features,slug',
			'reason' => 'nullable|string|max:500',
		]);

		$userModel = config('dynamic-permissions.models.user');
		$user = $userModel::findOrFail($userId);

		if (!method_exists($user, 'revokeFeatureAccess')) {
			return response()->json([
				'message' => 'User model must use HasDynamicPermissions trait',
			], 500);
		}

		$override = $user->revokeFeatureAccess(
			$validated['feature_slug'],
			$request->user()->id,
			$validated['reason'] ?? null
		);

		return response()->json([
			'message' => 'Feature access revoked successfully',
			'override' => $override->load('feature'),
		]);
	}

	/**
	 * Remove feature override (back to role default).
	 */
	public function removeOverride(Request $request, int $userId): JsonResponse
	{
		$validated = $request->validate([
			'feature_slug' => 'required|string|exists:features,slug',
		]);

		$userModel = config('dynamic-permissions.models.user');
		$user = $userModel::findOrFail($userId);

		if (!method_exists($user, 'removeFeatureOverride')) {
			return response()->json([
				'message' => 'User model must use HasDynamicPermissions trait',
			], 500);
		}

		$removed = $user->removeFeatureOverride($validated['feature_slug']);

		if ($removed) {
			return response()->json([
				'message' => 'Feature override removed successfully',
			]);
		}

		return response()->json([
			'message' => 'No override found for this feature',
		], 404);
	}

	/**
	 * Get all users with specific feature access.
	 */
	public function usersWithFeature(Request $request, string $featureSlug): JsonResponse
	{
		$feature = \gamn2090\DynamicPermissions\Models\Feature::where('slug', $featureSlug)
			->firstOrFail();

		// Users via role
		$usersViaRole = $feature->roles()
			->with('users')
			->get()
			->pluck('users')
			->flatten()
			->unique('id');

		// Users via override
		$usersViaOverride = UserFeatureOverride::where('feature_id', $feature->id)
			->where('has_access', true)
			->active()
			->with('user')
			->get()
			->pluck('user');

		$allUsers = $usersViaRole->merge($usersViaOverride)->unique('id')->values();

		return response()->json([
			'feature' => $feature,
			'users' => $allUsers,
			'count' => $allUsers->count(),
		]);
	}

	/**
	 * Clear user's permissions cache.
	 */
	public function clearCache(Request $request, int $userId): JsonResponse
	{
		$userModel = config('dynamic-permissions.models.user');
		$user = $userModel::findOrFail($userId);

		if (method_exists($user, 'clearPermissionsCache')) {
			$user->clearPermissionsCache();

			return response()->json([
				'message' => 'User permissions cache cleared successfully',
			]);
		}

		return response()->json([
			'message' => 'User model must use HasDynamicPermissions trait',
		], 500);
	}
}
