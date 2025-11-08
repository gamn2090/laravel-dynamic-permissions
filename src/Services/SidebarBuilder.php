<?php

namespace gamn2090\DynamicPermissions\Services;

use Illuminate\Support\Collection;

class SidebarBuilder
{
	/**
	 * Build sidebar structure from features.
	 */
	public function build(Collection $features, array $options = []): array
	{
		$includeGrandparents = $options['include_grandparents'] ?? true;
		$includeParents = $options['include_parents'] ?? true;
		$onlyWithChildren = $options['only_with_children'] ?? false;

		// Get root features
		$roots = $features->whereNull('parent_id')
			->where('is_active', true)
			->sortBy('order');

		$tree = [];

		foreach ($roots as $root) {
			// Skip grandparents if not included
			if (!$includeGrandparents && $root->type === 'grandparent') {
				continue;
			}

			$node = $this->buildNode($root, $features, $options);

			// Skip nodes without children if only_with_children is true
			if ($onlyWithChildren && empty($node['children'])) {
				continue;
			}

			$tree[] = $node;
		}

		return $tree;
	}

	/**
	 * Build a single node with its children.
	 */
	protected function buildNode($feature, Collection $allFeatures, array $options): array
	{
		$node = [
			'id' => $feature->id,
			'name' => $feature->name,
			'slug' => $feature->slug,
			'type' => $feature->type,
			'icon' => $feature->icon,
			'url' => $feature->url,
			'order' => $feature->order,
			'description' => $feature->description,
		];

		// Get children
		$children = $allFeatures
			->where('parent_id', $feature->id)
			->where('is_active', true)
			->sortBy('order');

		$node['children'] = [];

		foreach ($children as $child) {
			$childNode = $this->buildNode($child, $allFeatures, $options);
			$node['children'][] = $childNode;
		}

		return $node;
	}

	/**
	 * Build flat list of features (no hierarchy).
	 */
	public function buildFlat(Collection $features): array
	{
		return $features
			->where('is_active', true)
			->sortBy('order')
			->map(function ($feature) {
				return [
					'id' => $feature->id,
					'name' => $feature->name,
					'slug' => $feature->slug,
					'type' => $feature->type,
					'url' => $feature->url,
					'icon' => $feature->icon,
					'full_path' => $feature->getFullPath(),
				];
			})
			->values()
			->toArray();
	}

	/**
	 * Build menu structure for specific parent.
	 */
	public function buildForParent(Collection $features, int $parentId): array
	{
		$children = $features
			->where('parent_id', $parentId)
			->where('is_active', true)
			->sortBy('order');

		$menu = [];

		foreach ($children as $child) {
			$menu[] = [
				'id' => $child->id,
				'name' => $child->name,
				'slug' => $child->slug,
				'url' => $child->url,
				'icon' => $child->icon,
				'children' => $this->buildForParent($features, $child->id),
			];
		}

		return $menu;
	}
}
