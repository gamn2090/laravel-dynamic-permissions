<?php

namespace gamn2090\DynamicPermissions\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearPermissionsCacheCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 */
	protected $signature = 'dynamic-permissions:clear-cache
                            {--user= : Clear cache for specific user ID}';

	/**
	 * The console command description.
	 */
	protected $description = 'Clear dynamic permissions cache';

	/**
	 * Execute the console command.
	 */
	public function handle(): int
	{
		$userId = $this->option('user');

		if ($userId) {
			$this->clearUserCache($userId);
			$this->info("Cache cleared for user ID: {$userId}");
		} else {
			$this->clearAllCache();
			$this->info('All permissions cache cleared successfully!');
		}

		return self::SUCCESS;
	}

	/**
	 * Clear cache for specific user.
	 */
	protected function clearUserCache(int $userId): void
	{
		$prefix = config('dynamic-permissions.cache.prefix', 'dynamic_permissions');

		Cache::forget("{$prefix}.user.{$userId}.features");
		Cache::forget("{$prefix}.user.{$userId}.sidebar");

		// Clear all feature-specific caches
		$features = \gamn2090\DynamicPermissions\Models\Feature::pluck('slug');
		foreach ($features as $slug) {
			Cache::forget("{$prefix}.user.{$userId}.feature.{$slug}");
		}
	}

	/**
	 * Clear all permissions cache.
	 */
	protected function clearAllCache(): void
	{
		$prefix = config('dynamic-permissions.cache.prefix', 'dynamic_permissions');

		// If using Redis/Memcached, this would be more efficient
		// For now, we'll just flush the entire cache if enabled
		if (config('dynamic-permissions.cache.enabled')) {
			$store = config('dynamic-permissions.cache.store');

			if ($store) {
				Cache::store($store)->flush();
			} else {
				// Flush default cache store
				Cache::flush();
			}
		}
	}
}
