<?php

namespace gamn2090\DynamicPermissions\Services;

use gamn2090\DynamicPermissions\Models\Feature;
use gamn2090\DynamicPermissions\Models\UserFeatureOverride;
use Illuminate\Support\Collection;

class PermissionCompiler
{
	/**
	 * Compile all accessible features for a user.
	 */
	public function compile($user): Collection
	{
		// 1. Get features from user's role
		$roleFeatures = $this->getRoleFeaturesForUser($user);

		// 2. Get user-specific overrides
		$overrides = $this->getUserOverrides($user);

		// 3. Apply overrides to role features
		$finalFeatures = $this->applyOverrides($roleFeatures, $overrides);

		// 4. Add additional granted features not in role
		$additionalFeatures = $this->getAdditionalGrantedFeatures($user, $overrides);

		// 5. Merge and deduplicate
		return $finalFeatures->merge($additionalFeatures)->unique('id');
	}

	/**
	 * Get features assigned to user's role.
	 */
	protected function getRoleFeaturesForUser($user): Collection
	{
		// If user has no role, return empty collection
		if (!$user->role_id && !method_exists($user, 'roles')) {
			return collect();
		}

		$query = Feature::query()->active();

		// Support for single role (role_id column)
		if (isset($user->role_id)) {
			$query->whereHas('roles', function ($q) use ($user) {
				$q->where('id', $user->role_id);
			});
		}
		// Support for multiple roles (many-to-many)
		elseif (method_exists($user, 'roles')) {
			$roleIds = $user->roles->pluck('id');
			$query->whereHas('roles', function ($q) use ($roleIds) {
				$q->whereIn('id', $roleIds);
			});
		}

		return $query->get();
	}

	/**
	 * Get user-specific overrides.
	 */
	protected function getUserOverrides($user): Collection
	{
		return UserFeatureOverride::where('user_id', $user->id)
			->active()
			->with('feature')
			->get();
	}

	/**
	 * Apply user overrides to role features.
	 */
	protected function applyOverrides(Collection $roleFeatures, Collection $overrides): Collection
	{
		return $roleFeatures->filter(function ($feature) use ($overrides) {
			$override = $overrides->firstWhere('feature_id', $feature->id);

			// If there's an override for this feature
			if ($override) {
				// Return based on has_access value
				return $override->has_access;
			}

			// No override, keep the feature
			return true;
		});
	}

	/**
	 * Get additional features granted to user that are not in their role.
	 */
	protected function getAdditionalGrantedFeatures($user, Collection $overrides): Collection
	{
		$grantedFeatureIds = $overrides
			->where('has_access', true)
			->pluck('feature_id')
			->unique();

		return Feature::whereIn('id', $grantedFeatureIds)
			->active()
			->get();
	}

	/**
	 * Check if user has specific feature access.
	 */
	public function hasFeatureAccess($user, string $featureSlug): bool
	{
		$features = $this->compile($user);
		return $features->contains('slug', $featureSlug);
	}

	/**
	 * Get feature slugs accessible by user.
	 */
	public function getAccessibleSlugs($user): array
	{
		return $this->compile($user)->pluck('slug')->toArray();
	}
}
