<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComponentVersion extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'component_id',
        'version_number',
        'version_tag',
        'branch',
        'snapshot',
        'diff',
        'changelog',
        'breaking_changes',
        'is_published',
        'is_approved',
        'published_at',
        'approved_at',
        'created_by',
        'approved_by',
        'previous_version_id',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'diff' => 'array',
        'is_published' => 'boolean',
        'is_approved' => 'boolean',
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the component this version belongs to.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Get the user who created this version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this version.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the previous version.
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(ComponentVersion::class, 'previous_version_id');
    }

    /**
     * Get the next version.
     */
    public function nextVersion()
    {
        return ComponentVersion::where('previous_version_id', $this->id)->first();
    }

    /**
     * Publish this version.
     */
    public function publish(?int $userId = null): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $userId ?? $this->created_by,
        ]);
    }

    /**
     * Approve this version.
     */
    public function approve(int $userId): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $userId,
        ]);
    }

    /**
     * Calculate diff from previous version.
     */
    public function calculateDiff(): array
    {
        if (!$this->previousVersion) {
            return ['type' => 'initial', 'changes' => []];
        }

        $previous = $this->previousVersion->snapshot;
        $current = $this->snapshot;

        $diff = [
            'type' => 'update',
            'changes' => [],
        ];

        // Compare properties
        if (isset($current['properties']) && isset($previous['properties'])) {
            $diff['changes']['properties'] = [
                'added' => array_diff_key($current['properties'], $previous['properties']),
                'removed' => array_diff_key($previous['properties'], $current['properties']),
                'modified' => $this->getModifiedProperties($previous['properties'], $current['properties']),
            ];
        }

        // Compare other fields
        foreach (['name', 'description', 'type', 'category'] as $field) {
            if (isset($current[$field]) && isset($previous[$field]) && $current[$field] !== $previous[$field]) {
                $diff['changes'][$field] = [
                    'from' => $previous[$field],
                    'to' => $current[$field],
                ];
            }
        }

        return $diff;
    }

    /**
     * Get modified properties between two property arrays.
     */
    protected function getModifiedProperties(array $old, array $new): array
    {
        $modified = [];

        foreach ($new as $key => $value) {
            if (isset($old[$key]) && $old[$key] !== $value) {
                $modified[$key] = [
                    'from' => $old[$key],
                    'to' => $value,
                ];
            }
        }

        return $modified;
    }

    /**
     * Scope to get published versions.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to get approved versions.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeOnBranch($query, string $branch)
    {
        return $query->where('branch', $branch);
    }

    /**
     * Get version display name.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = "v{$this->version_number}";

        if ($this->version_tag) {
            $name .= "-{$this->version_tag}";
        }

        if ($this->branch !== 'main') {
            $name .= " ({$this->branch})";
        }

        return $name;
    }

    /**
     * Check if this version has breaking changes.
     */
    public function hasBreakingChanges(): bool
    {
        return !empty($this->breaking_changes);
    }
}
