<?php

namespace gamn2090\DynamicPermissions\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use gamn2090\DynamicPermissions\Models\Feature;

class SyncFeaturesCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 */
	protected $signature = 'dynamic-permissions:sync
                            {--dry-run : Preview changes without saving}
                            {--force : Force sync without confirmation}';

	/**
	 * The console command description.
	 */
	protected $description = 'Sync features from application routes';

	protected $created = 0;
	protected $updated = 0;
	protected $skipped = 0;

	/**
	 * Execute the console command.
	 */
	public function handle(): int
	{
		if (!config('dynamic-permissions.auto_sync.enabled') && !$this->option('force')) {
			$this->error('Auto-sync is disabled in config. Use --force to override.');
			return self::FAILURE;
		}

		$this->info('Syncing features from routes...');
		$this->newLine();

		$routes = $this->getApiRoutes();
		$grouped = $this->groupRoutesByResource($routes);

		if ($this->option('dry-run')) {
			$this->warn('DRY RUN MODE - No changes will be saved');
			$this->newLine();
		}

		foreach ($grouped as $resource => $actions) {
			$this->syncResource($resource, $actions);
		}

		$this->newLine();
		$this->info("Sync completed:");
		$this->line("  Created: {$this->created}");
		$this->line("  Updated: {$this->updated}");
		$this->line("  Skipped: {$this->skipped}");

		return self::SUCCESS;
	}

	/**
	 * Get API routes.
	 */
	protected function getApiRoutes(): array
	{
		$routes = [];
		$exclude = config('dynamic-permissions.auto_sync.route_patterns.exclude', []);

		foreach (Route::getRoutes() as $route) {
			$name = $route->getName();

			if (!$name || $this->shouldExclude($name, $exclude)) {
				continue;
			}

			// Only API routes
			if (in_array('api', $route->middleware())) {
				$routes[] = [
					'name' => $name,
					'uri' => $route->uri(),
					'methods' => $route->methods(),
				];
			}
		}

		return $routes;
	}

	/**
	 * Check if route should be excluded.
	 */
	protected function shouldExclude(string $routeName, array $patterns): bool
	{
		foreach ($patterns as $pattern) {
			if (Str::is($pattern, $routeName)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Group routes by resource.
	 */
	protected function groupRoutesByResource(array $routes): array
	{
		$grouped = [];

		foreach ($routes as $route) {
			$parts = explode('.', $route['name']);

			if (count($parts) >= 2) {
				$resource = $parts[0];
				$action = $parts[1];

				$grouped[$resource][$action] = $route;
			}
		}

		return $grouped;
	}

	/**
	 * Sync a resource and its actions.
	 */
	protected function syncResource(string $resource, array $actions): void
	{
		$resourceName = Str::title(str_replace('-', ' ', $resource));

		// Create parent feature
		$parentSlug = "{$resource}";
		$parent = $this->findOrCreateFeature([
			'name' => $resourceName,
			'slug' => $parentSlug,
			'type' => 'parent',
		]);

		if (!$parent) {
			$this->skipped++;
			return;
		}

		// Create child features for each action
		$mapping = config('dynamic-permissions.auto_sync.map_to_features', []);

		foreach ($actions as $action => $route) {
			if (!isset($mapping[$action])) {
				continue;
			}

			$config = $mapping[$action];
			$name = str_replace(':resource', $resourceName, $config['name']);

			$this->findOrCreateFeature([
				'name' => $name,
				'slug' => "{$resource}.{$action}",
				'type' => $config['type'],
				'parent_id' => $parent->id,
				'url' => $route['uri'],
			]);
		}
	}

	/**
	 * Find or create a feature.
	 */
	protected function findOrCreateFeature(array $data): ?Feature
	{
		if ($this->option('dry-run')) {
			$this->line("  [DRY RUN] Would create/update: {$data['slug']}");
			return null;
		}

		$feature = Feature::where('slug', $data['slug'])->first();

		if ($feature) {
			$updated = $feature->update($data);
			if ($updated) {
				$this->updated++;
				$this->line("  ↻ Updated: {$data['slug']}");
			} else {
				$this->skipped++;
			}
			return $feature;
		}

		$feature = Feature::create($data);
		$this->created++;
		$this->line("  ✓ Created: {$data['slug']}");

		return $feature;
	}
}
