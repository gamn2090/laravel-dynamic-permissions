<?php

namespace Gamn2090\DynamicPermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFeatureOverride extends Model
{
    protected $fillable = [
        'user_id',
        'feature_id',
        'can_access',
        'granted_by',
        'revoked_by',
        'reason',
        'expires_at',
        'granted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'can_access' => 'boolean',
            'expires_at' => 'datetime',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('dynamic-permissions.tables.user_feature_overrides', 'user_feature_overrides');
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        $userModel = config('dynamic-permissions.models.user', \App\Models\User::class);
        return $this->belongsTo($userModel);
    }

    /**
     * Get the feature.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * Get the user who granted this override.
     */
    public function grantedBy(): BelongsTo
    {
        $userModel = config('dynamic-permissions.models.user', \App\Models\User::class);
        return $this->belongsTo($userModel, 'granted_by');
    }

    /**
     * Scope to get only active overrides (not expired).
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to get expired overrides.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Check if override is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
