<?php

namespace Gamn2090\DynamicPermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
	use SoftDeletes;

	protected $fillable = [
		'name',
		'slug',
		'description',
		'is_active',
		'is_default',
		'priority',
	];

	protected $casts = [
		'is_active' => 'boolean',
		'is_default' => 'boolean',
		'priority' => 'integer',
	];

	/**
	 * Get the table associated with the model.
	 */
	public function getTable(): string
	{
		return config('dynamic-permissions.tables.roles', 'roles');
	}

	/**
	 * Users with this role
	 */
	public function users(): BelongsToMany
	{
		$userModel = config('dynamic-permissions.models.user', 'App\Models\User');

		return $this->belongsToMany($userModel, config('dynamic-permissions.tables.role_user', 'role_user'))
			->withPivot(['assigned_at', 'assigned_by'])
			->withTimestamps();
	}

	/**
	 * Features assigned to this role
	 */
	public function features(): BelongsToMany
	{
		return $this->belongsToMany(Feature::class, config('dynamic-permissions.tables.feature_role', 'feature_role'))
			->withPivot(['can_access', 'granted_by', 'granted_at'])
			->withTimestamps();
	}

	/**
	 * Check if role has a specific feature
	 */
	public function hasFeature(string $slug): bool
	{
		return $this->features()
			->where('slug', $slug)
			->wherePivot('can_access', true)
			->exists();
	}

	/**
	 * Grant a feature to this role
	 */
	public function grantFeature(int|string $feature, ?int $grantedBy = null): void
	{
		$featureId = is_int($feature)
			? $feature
			: Feature::where('slug', $feature)->value('id');

		if (!$featureId) {
			throw new \InvalidArgumentException("Feature not found: {$feature}");
		}

		$this->features()->syncWithoutDetaching([
			$featureId => [
				'can_access' => true,
				'granted_by' => $grantedBy,
				'granted_at' => now(),
			]
		]);

		// Clear cache for users with this role
		$this->clearUsersCache();
	}

	/**
	 * Revoke a feature from this role
	 */
	public function revokeFeature(int|string $feature): void
	{
		$featureId = is_int($feature)
			? $feature
			: Feature::where('slug', $feature)->value('id');

		if (!$featureId) {
			throw new \InvalidArgumentException("Feature not found: {$feature}");
		}

		$this->features()->detach($featureId);

		// Clear cache for users with this role
		$this->clearUsersCache();
	}

	/**
	 * Sync features for this role
	 */
	public function syncFeatures(array $featureIds, ?int $grantedBy = null): void
	{
		$syncData = [];
		foreach ($featureIds as $featureId) {
			$syncData[$featureId] = [
				'can_access' => true,
				'granted_by' => $grantedBy,
				'granted_at' => now(),
			];
		}

		$this->features()->sync($syncData);

		// Clear cache for users with this role
		$this->clearUsersCache();
	}

	/**
	 * Get all feature slugs for this role
	 */
	public function getFeatureSlugs(): array
	{
		return $this->features()
			->wherePivot('can_access', true)
			->pluck('slug')
			->toArray();
	}

	/**
	 * Clear cache for all users with this role
	 */
	protected function clearUsersCache(): void
	{
		if (!config('dynamic-permissions.cache.enabled', true)) {
			return;
		}

		$cachePrefix = config('dynamic-permissions.cache.prefix', 'dynamic_permissions');

		foreach ($this->users as $user) {
			cache()->forget("{$cachePrefix}.user.{$user->id}.features");
			cache()->forget("{$cachePrefix}.user.{$user->id}.sidebar");
		}
	}

	/**
	 * Scope: Only active roles
	 */
	public function scopeActive($query)
	{
		return $query->where('is_active', true);
	}

	/**
	 * Scope: Order by priority (highest first)
	 */
	public function scopeByPriority($query)
	{
		return $query->orderBy('priority', 'desc');
	}

	/**
	 * Scope: Default roles
	 */
	public function scopeDefault($query)
	{
		return $query->where('is_default', true);
	}

	/**
	 * Check if this is the default role
	 */
	public function isDefault(): bool
	{
		return $this->is_default;
	}

	/**
	 * Check if role is active
	 */
	public function isActive(): bool
	{
		return $this->is_active;
	}

	/**
	 * Activate the role
	 */
	public function activate(): bool
	{
		return $this->update(['is_active' => true]);
	}

	/**
	 * Deactivate the role
	 */
	public function deactivate(): bool
	{
		return $this->update(['is_active' => false]);
	}

	/**
	 * Set as default role
	 */
	public function setAsDefault(): bool
	{
		// Remove default flag from other roles
		static::where('is_default', true)->update(['is_default' => false]);

		return $this->update(['is_default' => true]);
	}
}
