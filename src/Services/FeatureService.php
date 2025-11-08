<?php

namespace Gamn2090\DynamicPermissions\Services;

use Gamn2090\DynamicPermissions\Models\Feature;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class FeatureService
{
    /**
     * Create a new feature.
     */
    public function create(array $data): Feature
    {
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return Feature::create($data);
    }

    /**
     * Update an existing feature.
     */
    public function update(Feature $feature, array $data): Feature
    {
        $feature->update($data);
        return $feature->fresh();
    }

    /**
     * Delete a feature and handle children.
     */
    public function delete(Feature $feature, bool $deleteChildren = false): bool
    {
        if ($deleteChildren) {
            // Delete all descendants
            $this->deleteDescendants($feature);
        } else {
            // Move children to parent's parent (or make them roots)
            $feature->children()->update(['parent_id' => $feature->parent_id]);
        }

        return $feature->delete();
    }

    /**
     * Delete feature and all its descendants.
     */
    protected function deleteDescendants(Feature $feature): void
    {
        foreach ($feature->children as $child) {
            $this->deleteDescendants($child);
            $child->delete();
        }
    }

    /**
     * Sync features with roles.
     */
    public function syncRoles(Feature $feature, array $roleIds): void
    {
        $feature->roles()->sync($roleIds);
    }

    /**
     * Get feature tree.
     */
    public function getTree(): Collection
    {
        return Feature::with('descendants')
            ->roots()
            ->active()
            ->get();
    }

    /**
     * Get all features as flat list.
     */
    public function getAll(): Collection
    {
        return Feature::active()->orderBy('order')->get();
    }

    /**
     * Find feature by slug.
     */
    public function findBySlug(string $slug): ?Feature
    {
        return Feature::where('slug', $slug)->first();
    }

    /**
     * Reorder features.
     */
    public function reorder(array $order): void
    {
        foreach ($order as $index => $featureId) {
            Feature::where('id', $featureId)->update(['order' => $index]);
        }
    }

    /**
     * Move feature to new parent.
     */
    public function move(Feature $feature, ?int $newParentId): Feature
    {
        // Prevent moving to itself or its own descendant
        if ($newParentId && $this->isDescendant($feature->id, $newParentId)) {
            throw new \InvalidArgumentException('Cannot move feature to its own descendant');
        }

        $feature->parent_id = $newParentId;
        $feature->save();

        return $feature->fresh();
    }

    /**
     * Check if feature is descendant of another.
     */
    protected function isDescendant(int $featureId, int $potentialParentId): bool
    {
        $parent = Feature::find($potentialParentId);

        if (!$parent) {
            return false;
        }

        if ($parent->id === $featureId) {
            return true;
        }

        if ($parent->parent_id) {
            return $this->isDescendant($featureId, $parent->parent_id);
        }

        return false;
    }

    /**
     * Duplicate a feature and optionally its children.
     */
    public function duplicate(Feature $feature, bool $withChildren = false): Feature
    {
        $newFeature = $feature->replicate();
        $newFeature->slug = $feature->slug . '-copy-' . time();
        $newFeature->name = $feature->name . ' (Copy)';
        $newFeature->save();

        if ($withChildren) {
            foreach ($feature->children as $child) {
                $duplicatedChild = $this->duplicate($child, true);
                $duplicatedChild->parent_id = $newFeature->id;
                $duplicatedChild->save();
            }
        }

        return $newFeature;
    }
}
