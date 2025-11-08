<?php

use Illuminate\Support\Facades\Route;
use gamn2090\DynamicPermissions\Http\Controllers\FeatureController;
use gamn2090\DynamicPermissions\Http\Controllers\UserFeatureController;

/*
|--------------------------------------------------------------------------
| Dynamic Permissions API Routes
|--------------------------------------------------------------------------
*/

if (config('dynamic-permissions.routes.enabled', true)) {

	$prefix = config('dynamic-permissions.routes.prefix', 'api/permissions');
	$middleware = config('dynamic-permissions.routes.middleware', ['api', 'auth:sanctum']);

	Route::prefix($prefix)->middleware($middleware)->group(function () {

		/*
        |--------------------------------------------------------------------------
        | Features Management
        |--------------------------------------------------------------------------
        */
		Route::prefix('features')->group(function () {
			Route::get('/', [FeatureController::class, 'index']);
			Route::post('/', [FeatureController::class, 'store']);
			Route::get('/sidebar', [FeatureController::class, 'sidebar']);
			Route::post('/reorder', [FeatureController::class, 'reorder']);

			Route::get('/{feature}', [FeatureController::class, 'show']);
			Route::put('/{feature}', [FeatureController::class, 'update']);
			Route::delete('/{feature}', [FeatureController::class, 'destroy']);

			Route::post('/{feature}/sync-roles', [FeatureController::class, 'syncRoles']);
			Route::post('/{feature}/move', [FeatureController::class, 'move']);
			Route::post('/{feature}/duplicate', [FeatureController::class, 'duplicate']);
		});

		/*
        |--------------------------------------------------------------------------
        | User Features
        |--------------------------------------------------------------------------
        */
		Route::prefix('user')->group(function () {
			// Current authenticated user
			Route::get('/features', [UserFeatureController::class, 'myFeatures']);
			Route::get('/sidebar', [UserFeatureController::class, 'mySidebar']);
			Route::post('/check-access', [UserFeatureController::class, 'checkAccess']);
		});

		/*
        |--------------------------------------------------------------------------
        | User Management (Admin)
        |--------------------------------------------------------------------------
        */
		Route::prefix('users/{userId}')->group(function () {
			Route::get('/overrides', [UserFeatureController::class, 'getOverrides']);
			Route::post('/grant', [UserFeatureController::class, 'grantAccess']);
			Route::post('/revoke', [UserFeatureController::class, 'revokeAccess']);
			Route::delete('/override', [UserFeatureController::class, 'removeOverride']);
			Route::post('/clear-cache', [UserFeatureController::class, 'clearCache']);
		});

		/*
        |--------------------------------------------------------------------------
        | Feature Users Lookup
        |--------------------------------------------------------------------------
        */
		Route::get('/feature/{featureSlug}/users', [UserFeatureController::class, 'usersWithFeature']);
	});
}
