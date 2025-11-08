<?php

namespace gamn2090\DynamicPermissions\Traits;

use gamn2090\DynamicPermissions\Models\Feature;
use gamn2090\DynamicPermissions\Models\UserFeatureOverride;
use gamn2090\DynamicPermissions\Services\PermissionCompiler;
use gamn2090\DynamicPermissions\Services\SidebarBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasDynamicPermissions
{
	/**
	 * Get user feature overrides.
	 */
	public function featureOverrides(): HasMany
	{
		return $this->hasMany(UserFeatureOverride::class, 'user_id');
	}

	/**
	 * Check if user has access to a feature.
	 */
	public function hasFeatureAccess(string $featureSlug): bool
	{
		$cacheKey = "user.{$this->id}.feature.{$featureSlug}";
		$cacheTtl = config('dynamic-permissions.cache.ttl', 3600);

		return Cache::remember($cacheKey, $cacheTtl, function () use ($featureSlug) {
			$compiler = app(PermissionCompiler::class);
			$features = $compiler->compile($this);

			return $features->contains('slug', $featureSlug);
		});
	}

	/**
	 * Check if user has access to any of the given features.
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
	 * Check if user has access to all given features.
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
	 * Get all accessible features for this user.
	 */
	public function getAccessibleFeatures()
	{
		$cacheKey = "user.{$this->id}.features";
		$cacheTtl = config('dynamic-permissions.cache.ttl', 3600);

		return Cache::remember($cacheKey, $cacheTtl, function () {
			$compiler = app(PermissionCompiler::class);
			return $compiler->compile($this);
		});
	}

	/**
	 * Get sidebar structure for this user.
	 */
	public function getSidebarFeatures(array $options = [])
	{
		$cacheKey = "user.{$this->id}.sidebar." . md5(json_encode($options));
		$cacheTtl = config('dynamic-permissions.cache.ttl', 3600);

		return Cache::remember($cacheKey, $cacheTtl, function () use ($options) {
			$builder = app(SidebarBuilder::class);
			$features = $this->getAccessibleFeatures();

			return $builder->build($features, $options);
		});
	}

	/**
	 * Grant specific feature access to this user.
	 */
	public function grantFeatureAccess(
		string $featureSlug,
		?int $grantedBy = null,
		?string $reason = null,
		?\DateTime $expiresAt = null
	): UserFeatureOverride {
		$feature = Feature::where('slug', $featureSlug)->firstOrFail();

		$override = UserFeatureOverride::updateOrCreate(
			[
				'user_id' => $this->id,
				'feature_id' => $feature->id,
			],
			[
				'has_access' => true,
				'granted_by' => $grantedBy,
				'reason' => $reason,
				'expires_at' => $expiresAt,
			]
		);

		$this->clearPermissionsCache();

		return $override;
	}

	/**
	 * Revoke specific feature access from this user.
	 */
	public function revokeFeatureAccess(
		string $featureSlug,
		?int $grantedBy = null,
		?string $reason = null
	): UserFeatureOverride {
		$feature = Feature::where('slug', $featureSlug)->firstOrFail();

		$override = UserFeatureOverride::updateOrCreate(
			[
				'user_id' => $this->id,
				'feature_id' => $feature->id,
			],
			[
				'has_access' => false,
				'granted_by' => $grantedBy,
				'reason' => $reason,
			]
		);

		$this->clearPermissionsCache();

		return $override;
	}

	/**
	 * Remove feature override (back to role default).
	 */
	public function removeFeatureOverride(string $featureSlug): bool
	{
		$feature = Feature::where('slug', $featureSlug)->first();

		if (!$feature) {
			return false;
		}

		$deleted = UserFeatureOverride::where('user_id', $this->id)
			->where('feature_id', $feature->id)
			->delete();

		if ($deleted) {
			$this->clearPermissionsCache();
		}

		return $deleted > 0;
	}

	/**
	 * Clear all permissions cache for this user.
	 */
	public function clearPermissionsCache(): void
	{
		$pattern = "user.{$this->id}.*";

		if (method_exists(Cache::getStore(), 'forget')) {
			// Clear specific keys
			Cache::forget("user.{$this->id}.features");
			Cache::forget("user.{$this->id}.sidebar");

			// Clear all feature-specific caches
			$features = Feature::pluck('slug');
			foreach ($features as $slug) {
				Cache::forget("user.{$this->id}.feature.{$slug}");
			}
		}
	}
}
