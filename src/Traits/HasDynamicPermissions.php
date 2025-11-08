<?php

namespace Gamn2090\DynamicPermissions\Traits;

use Gamn2090\DynamicPermissions\Models\Feature;
use Gamn2090\DynamicPermissions\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasDynamicPermissions
{
	/**
	 * User's roles
	 */
	public function roles(): BelongsToMany
	{
		return $this->belongsToMany(
			Role::class,
			config('dynamic-permissions.tables.role_user', 'role_user')
		)
			->withPivot(['assigned_at', 'assigned_by'])
			->withTimestamps();
	}

	/**
	 * User's feature overrides
	 */
	public function featureOverrides(): BelongsToMany
	{
		return $this->belongsToMany(
			Feature::class,
			config('dynamic-permissions.tables.user_features', 'user_features')
		)
			->withPivot([
				'can_access',
				'granted_by',
				'granted_at',
				'revoked_by',
				'revoked_at',
				'reason',
				'expires_at'
			])
			->withTimestamps();
	}

	/**
	 * Check if user has a specific role
	 */
	public function hasRole(string|int $role): bool
	{
		if (is_int($role)) {
			return $this->roles()->where('role_id', $role)->exists();
		}

		return $this->roles()->where('slug', $role)->exists();
	}

	/**
	 * Check if user has any of the given roles
	 */
	public function hasAnyRole(array $roles): bool
	{
		foreach ($roles as $role) {
			if ($this->hasRole($role)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user has all of the given roles
	 */
	public function hasAllRoles(array $roles): bool
	{
		foreach ($roles as $role) {
			if (!$this->hasRole($role)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Assign a role to the user
	 */
	public function assignRole(int|string $role, ?int $assignedBy = null): void
	{
		$roleId = is_int($role)
			? $role
			: Role::where('slug', $role)->value('id');

		if (!$roleId) {
			throw new \InvalidArgumentException("Role not found: {$role}");
		}

		$this->roles()->syncWithoutDetaching([
			$roleId => [
				'assigned_by' => $assignedBy,
				'assigned_at' => now(),
			]
		]);

		$this->clearPermissionCache();
	}

	/**
	 * Remove a role from the user
	 */
	public function removeRole(int|string $role): void
	{
		$roleId = is_int($role)
			? $role
			: Role::where('slug', $role)->value('id');

		if (!$roleId) {
			throw new \InvalidArgumentException("Role not found: {$role}");
		}

		$this->roles()->detach($roleId);
		$this->clearPermissionCache();
	}

	/**
	 * Sync user's roles
	 */
	public function syncRoles(array $roleIds, ?int $assignedBy = null): void
	{
		$syncData = [];
		foreach ($roleIds as $roleId) {
			$syncData[$roleId] = [
				'assigned_by' => $assignedBy,
				'assigned_at' => now(),
			];
		}

		$this->roles()->sync($syncData);
		$this->clearPermissionCache();
	}

	/**
	 * Get all features the user has access to (from roles + overrides)
	 */
	public function getAllFeatures(): Collection
	{
		$cacheKey = $this->getFeatureCacheKey();

		if (config('dynamic-permissions.cache.enabled', true)) {
			return cache()->remember(
				$cacheKey,
				config('dynamic-permissions.cache.ttl', 3600),
				fn() => $this->loadAllFeatures()
			);
		}

		return $this->loadAllFeatures();
	}

	/**
	 * Load all features from roles and overrides
	 */
	protected function loadAllFeatures(): Collection
	{
		// Get features from all user's active roles
		$roleFeatures = Feature::query()
			->join(
				config('dynamic-permissions.tables.feature_role', 'feature_role'),
				'features.id',
				'=',
				config('dynamic-permissions.tables.feature_role', 'feature_role') . '.feature_id'
			)
			->join(
				config('dynamic-permissions.tables.role_user', 'role_user'),
				config('dynamic-permissions.tables.feature_role', 'feature_role') . '.role_id',
				'=',
				config('dynamic-permissions.tables.role_user', 'role_user') . '.role_id'
			)
			->join(
				config('dynamic-permissions.tables.roles', 'roles'),
				config('dynamic-permissions.tables.role_user', 'role_user') . '.role_id',
				'=',
				config('dynamic-permissions.tables.roles', 'roles') . '.id'
			)
			->where(config('dynamic-permissions.tables.role_user', 'role_user') . '.user_id', $this->id)
			->where(config('dynamic-permissions.tables.roles', 'roles') . '.is_active', true)
			->where(config('dynamic-permissions.tables.feature_role', 'feature_role') . '.can_access', true)
			->select('features.*')
			->distinct()
			->get()
			->keyBy('slug');

		// Get user-specific overrides
		$overrides = $this->featureOverrides()
			->wherePivot('can_access', true)
			->where(function ($query) {
				$query->whereNull('user_features.expires_at')
					->orWhere('user_features.expires_at', '>', now());
			})
			->get()
			->keyBy('slug');

		// Get revoked features
		$revokedFeatures = $this->featureOverrides()
			->wherePivot('can_access', false)
			->pluck('slug')
			->toArray();

		// Merge: Start with role features, add overrides, remove revoked
		$allFeatures = $roleFeatures->merge($overrides);

		// Remove revoked features
		foreach ($revokedFeatures as $revokedSlug) {
			$allFeatures->forget($revokedSlug);
		}

		return $allFeatures->values();
	}

	/**
	 * Check if user has access to a specific feature
	 */
	public function hasFeatureAccess(string $featureSlug): bool
	{
		// Check for user-specific override first
		$override = $this->featureOverrides()
			->where('slug', $featureSlug)
			->wherePivot(function ($query) {
				$query->whereNull('expires_at')
					->orWhere('expires_at', '>', now());
			})
			->first();

		if ($override) {
			return $override->pivot->can_access;
		}

		// Check role-based access
		return Feature::query()
			->where('slug', $featureSlug)
			->whereHas('roles', function ($query) {
				$query->whereIn(
					config('dynamic-permissions.tables.roles', 'roles') . '.id',
					$this->roles()->pluck(config('dynamic-permissions.tables.roles', 'roles') . '.id')
				)
					->where(config('dynamic-permissions.tables.roles', 'roles') . '.is_active', true)
					->wherePivot('can_access', true);
			})
			->exists();
	}

	/**
	 * Check if user has access to any of the given features
	 */
	public function hasAnyFeatureAccess(array $featureSlugs): bool
	{
		foreach ($featureSlugs as $slug) {
			if ($this->hasFeatureAccess($slug)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user has access to all of the given features
	 */
	public function hasAllFeatureAccess(array $featureSlugs): bool
	{
		foreach ($featureSlugs as $slug) {
			if (!$this->hasFeatureAccess($slug)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Grant feature access to user (override)
	 */
	public function grantFeatureAccess(
		string $featureSlug,
		?int $grantedBy = null,
		?string $reason = null,
		?\DateTimeInterface $expiresAt = null
	): void {
		$feature = Feature::where('slug', $featureSlug)->firstOrFail();

		$this->featureOverrides()->syncWithoutDetaching([
			$feature->id => [
				'can_access' => true,
				'granted_by' => $grantedBy,
				'granted_at' => now(),
				'revoked_by' => null,
				'revoked_at' => null,
				'reason' => $reason,
				'expires_at' => $expiresAt,
			]
		]);

		$this->clearPermissionCache();
	}

	/**
	 * Revoke feature access from user (override)
	 */
	public function revokeFeatureAccess(
		string $featureSlug,
		?int $revokedBy = null,
		?string $reason = null
	): void {
		$feature = Feature::where('slug', $featureSlug)->firstOrFail();

		$this->featureOverrides()->syncWithoutDetaching([
			$feature->id => [
				'can_access' => false,
				'revoked_by' => $revokedBy,
				'revoked_at' => now(),
				'reason' => $reason,
				'granted_by' => null,
				'granted_at' => null,
				'expires_at' => null,
			]
		]);

		$this->clearPermissionCache();
	}

	/**
	 * Remove feature override (back to role default)
	 */
	public function removeFeatureOverride(string $featureSlug): void
	{
		$feature = Feature::where('slug', $featureSlug)->first();

		if ($feature) {
			$this->featureOverrides()->detach($feature->id);
			$this->clearPermissionCache();
		}
	}

	/**
	 * Get sidebar structure based on user's permissions
	 */
	public function getSidebarFeatures(): array
	{
		$cacheKey = $this->getSidebarCacheKey();

		if (config('dynamic-permissions.cache.enabled', true)) {
			return cache()->remember(
				$cacheKey,
				config('dynamic-permissions.cache.ttl', 3600),
				fn() => $this->buildSidebarStructure()
			);
		}

		return $this->buildSidebarStructure();
	}

	/**
	 * Build hierarchical sidebar structure
	 */
	protected function buildSidebarStructure(): array
	{
		$allFeatures = $this->getAllFeatures();
		$featureSlugs = $allFeatures->pluck('slug')->toArray();

		// Get all parent and grandparent features
		$parentIds = $allFeatures->pluck('parent_id')->filter()->unique()->toArray();
		$parents = Feature::whereIn('id', $parentIds)->get();

		$grandparentIds = $parents->pluck('parent_id')->filter()->unique()->toArray();
		$grandparents = Feature::whereIn('id', $grandparentIds)->get();

		// Combine all relevant features
		$relevantFeatures = $allFeatures
			->merge($parents)
			->merge($grandparents)
			->unique('id');

		// Build tree structure
		return $this->buildFeatureTree($relevantFeatures, null);
	}

	/**
	 * Recursively build feature tree
	 */
	protected function buildFeatureTree(Collection $features, ?int $parentId): array
	{
		return $features
			->where('parent_id', $parentId)
			->sortBy('order')
			->map(function ($feature) use ($features) {
				$node = [
					'id' => $feature->id,
					'name' => $feature->name,
					'slug' => $feature->slug,
					'icon' => $feature->icon,
					'url' => $feature->url,
					'type' => $feature->type,
				];

				$children = $this->buildFeatureTree($features, $feature->id);
				if (!empty($children)) {
					$node['children'] = $children;
				}

				return $node;
			})
			->values()
			->toArray();
	}

	/**
	 * Clear permission cache for this user
	 */
	public function clearPermissionCache(): void
	{
		if (!config('dynamic-permissions.cache.enabled', true)) {
			return;
		}

		cache()->forget($this->getFeatureCacheKey());
		cache()->forget($this->getSidebarCacheKey());
	}

	/**
	 * Get cache key for user features
	 */
	protected function getFeatureCacheKey(): string
	{
		$prefix = config('dynamic-permissions.cache.prefix', 'dynamic_permissions');
		return "{$prefix}.user.{$this->id}.features";
	}

	/**
	 * Get cache key for user sidebar
	 */
	protected function getSidebarCacheKey(): string
	{
		$prefix = config('dynamic-permissions.cache.prefix', 'dynamic_permissions');
		return "{$prefix}.user.{$this->id}.sidebar";
	}
}
