<?php

namespace gamn2090\DynamicPermissions;

use Illuminate\Support\ServiceProvider;
use gamn2090\DynamicPermissions\Services\FeatureService;
use gamn2090\DynamicPermissions\Services\PermissionCompiler;
use gamn2090\DynamicPermissions\Services\SidebarBuilder;
use gamn2090\DynamicPermissions\Console\Commands\SyncFeaturesCommand;
use gamn2090\DynamicPermissions\Console\Commands\ClearPermissionsCacheCommand;
use gamn2090\DynamicPermissions\Console\Commands\InstallPackageCommand;
use gamn2090\DynamicPermissions\Middleware\CheckFeature;
use gamn2090\DynamicPermissions\Middleware\CheckMultipleFeatures;
use Illuminate\Routing\Router;

class DynamicPermissionsServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		// Merge config
		$this->mergeConfigFrom(__DIR__ . '/../config/dynamic-permissions.php', 'dynamic-permissions');

		// Register services
		$this->app->singleton(FeatureService::class, function ($app) {
			return new FeatureService();
		});

		$this->app->singleton(PermissionCompiler::class, function ($app) {
			return new PermissionCompiler();
		});

		$this->app->singleton(SidebarBuilder::class, function ($app) {
			return new SidebarBuilder();
		});

		// Register facade
		$this->app->bind('dynamic-permissions', function ($app) {
			return $app->make(FeatureService::class);
		});
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		// Publish config
		$this->publishes([
			__DIR__ . '/../config/dynamic-permissions.php' => config_path('dynamic-permissions.php'),
		], 'dynamic-permissions-config');

		// Publish migrations
		$this->publishes([
			__DIR__ . '/../database/migrations' => database_path('migrations'),
		], 'dynamic-permissions-migrations');

		// Load migrations
		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

		// Load routes
		$this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

		// Register commands
		if ($this->app->runningInConsole()) {
			$this->commands([
				SyncFeaturesCommand::class,
				ClearPermissionsCacheCommand::class,
				InstallPackageCommand::class,
			]);
		}

		// Register middleware
		$router = $this->app->make(Router::class);
		$router->aliasMiddleware('feature', CheckFeature::class);
		$router->aliasMiddleware('features', CheckMultipleFeatures::class);
	}
}
