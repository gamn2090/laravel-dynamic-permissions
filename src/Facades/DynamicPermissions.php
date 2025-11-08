<?php

namespace gamn2090\DynamicPermissions\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \gamn2090\DynamicPermissions\Models\Feature create(array $data)
 * @method static \gamn2090\DynamicPermissions\Models\Feature update(\gamn2090\DynamicPermissions\Models\Feature $feature, array $data)
 * @method static bool delete(\gamn2090\DynamicPermissions\Models\Feature $feature, bool $deleteChildren = false)
 * @method static void syncRoles(\gamn2090\DynamicPermissions\Models\Feature $feature, array $roleIds)
 * @method static \Illuminate\Support\Collection getTree()
 * @method static \Illuminate\Support\Collection getAll()
 * @method static \gamn2090\DynamicPermissions\Models\Feature|null findBySlug(string $slug)
 * @method static void reorder(array $order)
 * @method static \gamn2090\DynamicPermissions\Models\Feature move(\gamn2090\DynamicPermissions\Models\Feature $feature, ?int $newParentId)
 * @method static \gamn2090\DynamicPermissions\Models\Feature duplicate(\gamn2090\DynamicPermissions\Models\Feature $feature, bool $withChildren = false)
 *
 * @see \gamn2090\DynamicPermissions\Services\FeatureService
 */
class DynamicPermissions extends Facade
{
	/**
	 * Get the registered name of the component.
	 */
	protected static function getFacadeAccessor(): string
	{
		return 'dynamic-permissions';
	}
}
