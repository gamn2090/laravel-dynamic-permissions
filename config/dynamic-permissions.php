<?php

return [
	/*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Customize the models used by the package.
    |
    */
	'models' => [
		'user' => \App\Models\User::class,
		'role' => \App\Models\Role::class,
	],

	/*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by the package.
    |
    */
	'tables' => [
		'features' => 'features',
		'role_features' => 'role_features',
		'user_feature_overrides' => 'user_feature_overrides',
	],

	/*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for permissions.
    |
    */
	'cache' => [
		'enabled' => true,
		'ttl' => 3600, // Cache TTL in seconds (1 hour)
		'prefix' => 'dynamic_permissions',
		'store' => null, // null uses default cache store
	],

	/*
    |--------------------------------------------------------------------------
    | Feature Types
    |--------------------------------------------------------------------------
    |
    | Define the types of features available.
    |
    */
	'feature_types' => [
		'grandparent' => 'Grandparent (Top-level group)',
		'parent' => 'Parent (Sub-group)',
		'child' => 'Child (Actual feature/page)',
	],

	/*
    |--------------------------------------------------------------------------
    | Auto-Sync Features
    |--------------------------------------------------------------------------
    |
    | Automatically sync features from routes.
    |
    */
	'auto_sync' => [
		'enabled' => false,
		'route_patterns' => [
			// Only sync API routes
			'prefix' => ['api'],
			// Exclude these routes
			'exclude' => [
				'sanctum.*',
				'ignition.*',
				'_ignition.*',
				'horizon.*',
				'telescope.*',
			],
		],
		'map_to_features' => [
			'index' => ['type' => 'child', 'name' => 'View :resource'],
			'show' => ['type' => 'child', 'name' => 'View :resource Details'],
			'store' => ['type' => 'child', 'name' => 'Create :resource'],
			'update' => ['type' => 'child', 'name' => 'Update :resource'],
			'destroy' => ['type' => 'child', 'name' => 'Delete :resource'],
		],
	],

	/*
    |--------------------------------------------------------------------------
    | Sidebar Configuration
    |--------------------------------------------------------------------------
    |
    | Configure sidebar generation behavior.
    |
    */
	'sidebar' => [
		'include_grandparents' => true,
		'include_parents' => true,
		'only_with_children' => false,
		'show_icons' => true,
		'show_descriptions' => false,
	],

	/*
    |--------------------------------------------------------------------------
    | API Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the package's API routes.
    |
    */
	'routes' => [
		'enabled' => true,
		'prefix' => 'api/permissions',
		'middleware' => ['api', 'auth:sanctum'],
	],

	/*
    |--------------------------------------------------------------------------
    | Override Expiration
    |--------------------------------------------------------------------------
    |
    | Automatically expire user feature overrides.
    |
    */
	'override_expiration' => [
		'enabled' => true,
		'check_interval' => 'daily', // Run expiration check daily
	],

	/*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log permission checks for debugging.
    |
    */
	'logging' => [
		'enabled' => false,
		'channel' => 'stack',
	],
];
