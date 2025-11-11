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
        'role' => \Gamn2090\DynamicPermissions\Models\Role::class,
        'feature' => \Gamn2090\DynamicPermissions\Models\Feature::class,
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
        'roles' => 'roles',
        'features' => 'features',
        'role_user' => 'role_users',
        'feature_role' => 'feature_roles',
        'user_features' => 'user_feature_overrides',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching for permissions and features.
    |
    */
    'cache' => [
        'enabled' => env('DYNAMIC_PERMISSIONS_CACHE_ENABLED', true),
        'ttl' => env('DYNAMIC_PERMISSIONS_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'dynamic_permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Sync from Routes
    |--------------------------------------------------------------------------
    |
    | Automatically sync features from your route names.
    |
    */
    'auto_sync' => [
        'enabled' => env('DYNAMIC_PERMISSIONS_AUTO_SYNC', false),
        'route_prefixes' => ['admin', 'dashboard'], // Only sync routes with these prefixes
        'excluded_routes' => ['login', 'logout', 'register'], // Skip these routes
    ],

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Configure the API routes for managing permissions.
    |
    */
    'routes' => [
        'enabled' => env('DYNAMIC_PERMISSIONS_API_ENABLED', true),
        'prefix' => 'api/permissions',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    |
    | The default role slug to assign to new users.
    | Set to null to disable automatic role assignment.
    |
    */
    'default_role' => env('DYNAMIC_PERMISSIONS_DEFAULT_ROLE', null),

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Super admin role bypasses all permission checks.
    |
    */
    'super_admin' => [
        'enabled' => env('DYNAMIC_PERMISSIONS_SUPER_ADMIN_ENABLED', true),
        'role_slug' => 'super-admin',
    ],
];
