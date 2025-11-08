<?php

namespace gamn2090\DynamicPermissions\Http\Controllers;

use gamn2090\DynamicPermissions\Models\Feature;
use gamn2090\DynamicPermissions\Services\FeatureService;
use gamn2090\DynamicPermissions\Services\SidebarBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FeatureController extends Controller
{
	public function __construct(
		protected FeatureService $featureService,
		protected SidebarBuilder $sidebarBuilder
	) {}

	/**
	 * Get all features as tree.
	 */
	public function index(Request $request): JsonResponse
	{
		$format = $request->get('format', 'tree'); // tree or flat

		if ($format === 'flat') {
			$features = $this->featureService->getAll();
			return response()->json($features);
		}

		$tree = $this->featureService->getTree();
		return response()->json($tree);
	}

	/**
	 * Store a new feature.
	 */
	public function store(Request $request): JsonResponse
	{
		$validated = $request->validate([
			'name' => 'required|string|max:255',
			'slug' => 'nullable|string|unique:features,slug',
			'type' => 'required|in:grandparent,parent,child',
			'parent_id' => 'nullable|exists:features,id',
			'url' => 'nullable|string|max:500',
			'icon' => 'nullable|string|max:100',
			'order' => 'nullable|integer',
			'description' => 'nullable|string',
			'is_active' => 'boolean',
			'metadata' => 'nullable|array',
		]);

		$feature = $this->featureService->create($validated);

		return response()->json([
			'message' => 'Feature created successfully',
			'feature' => $feature,
		], 201);
	}

	/**
	 * Show a specific feature.
	 */
	public function show(Feature $feature): JsonResponse
	{
		return response()->json($feature->load(['parent', 'children', 'roles']));
	}

	/**
	 * Update a feature.
	 */
	public function update(Request $request, Feature $feature): JsonResponse
	{
		$validated = $request->validate([
			'name' => 'string|max:255',
			'slug' => 'string|unique:features,slug,' . $feature->id,
			'type' => 'in:grandparent,parent,child',
			'parent_id' => 'nullable|exists:features,id',
			'url' => 'nullable|string|max:500',
			'icon' => 'nullable|string|max:100',
			'order' => 'nullable|integer',
			'description' => 'nullable|string',
			'is_active' => 'boolean',
			'metadata' => 'nullable|array',
		]);

		$updated = $this->featureService->update($feature, $validated);

		return response()->json([
			'message' => 'Feature updated successfully',
			'feature' => $updated,
		]);
	}

	/**
	 * Delete a feature.
	 */
	public function destroy(Request $request, Feature $feature): JsonResponse
	{
		$deleteChildren = $request->boolean('delete_children', false);

		$this->featureService->delete($feature, $deleteChildren);

		return response()->json([
			'message' => 'Feature deleted successfully',
		]);
	}

	/**
	 * Get feature tree for sidebar.
	 */
	public function sidebar(Request $request): JsonResponse
	{
		$options = [
			'include_grandparents' => $request->boolean('include_grandparents', true),
			'include_parents' => $request->boolean('include_parents', true),
			'only_with_children' => $request->boolean('only_with_children', false),
		];

		$features = $this->featureService->getAll();
		$sidebar = $this->sidebarBuilder->build($features, $options);

		return response()->json($sidebar);
	}

	/**
	 * Sync roles with feature.
	 */
	public function syncRoles(Request $request, Feature $feature): JsonResponse
	{
		$validated = $request->validate([
			'role_ids' => 'required|array',
			'role_ids.*' => 'exists:' . config('dynamic-permissions.tables.roles', 'roles') . ',id',
		]);

		$this->featureService->syncRoles($feature, $validated['role_ids']);

		return response()->json([
			'message' => 'Roles synced successfully',
			'feature' => $feature->load('roles'),
		]);
	}

	/**
	 * Reorder features.
	 */
	public function reorder(Request $request): JsonResponse
	{
		$validated = $request->validate([
			'order' => 'required|array',
			'order.*' => 'exists:features,id',
		]);

		$this->featureService->reorder($validated['order']);

		return response()->json([
			'message' => 'Features reordered successfully',
		]);
	}

	/**
	 * Move feature to new parent.
	 */
	public function move(Request $request, Feature $feature): JsonResponse
	{
		$validated = $request->validate([
			'new_parent_id' => 'nullable|exists:features,id',
		]);

		try {
			$moved = $this->featureService->move($feature, $validated['new_parent_id']);

			return response()->json([
				'message' => 'Feature moved successfully',
				'feature' => $moved,
			]);
		} catch (\InvalidArgumentException $e) {
			return response()->json([
				'message' => $e->getMessage(),
			], 422);
		}
	}

	/**
	 * Duplicate a feature.
	 */
	public function duplicate(Request $request, Feature $feature): JsonResponse
	{
		$withChildren = $request->boolean('with_children', false);

		$duplicated = $this->featureService->duplicate($feature, $withChildren);

		return response()->json([
			'message' => 'Feature duplicated successfully',
			'feature' => $duplicated,
		], 201);
	}
}
