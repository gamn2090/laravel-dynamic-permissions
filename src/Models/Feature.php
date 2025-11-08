<?php

namespace Gamn2090\DynamicPermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feature extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'parent_id',
        'url',
        'icon',
        'order',
        'description',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            'order' => 'integer',
        ];
    }

    /**
     * Get the parent feature.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'parent_id');
    }

    /**
     * Get the child features.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Feature::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get all descendant features recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get roles associated with this feature.
     */
    public function roles(): BelongsToMany
    {
        $roleModel = config('dynamic-permissions.models.role', \App\Models\Role::class);

        return $this->belongsToMany($roleModel, 'role_features')
            ->withTimestamps();
    }

    /**
     * Scope to get only root features (no parent).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('order');
    }

    /**
     * Scope to get only active features.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get features by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if feature is a parent.
     */
    public function isParent(): bool
    {
        return in_array($this->type, ['grandparent', 'parent']);
    }

    /**
     * Check if feature is a child.
     */
    public function isChild(): bool
    {
        return $this->type === 'child';
    }

    /**
     * Get full path of feature (grandparent > parent > child).
     */
    public function getFullPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Get all child slugs recursively.
     */
    public function getAllChildSlugs(): array
    {
        $slugs = [$this->slug];

        foreach ($this->children as $child) {
            $slugs = array_merge($slugs, $child->getAllChildSlugs());
        }

        return $slugs;
    }
}
