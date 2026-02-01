<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignVersion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'design_id',
        'user_id',
        'version_number',
        'version_name',
        'description',
        'snapshot',
        'metadata_snapshot',
        'image_snapshot_url',
        'changes',
        'previous_version_id',
        'tags',
        'is_auto_version',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'metadata_snapshot' => 'array',
        'changes' => 'array',
        'tags' => 'array',
        'is_auto_version' => 'boolean',
    ];

    /**
     * Get the design that owns the version.
     */
    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    /**
     * Get the user who created the version.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the previous version.
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(DesignVersion::class, 'previous_version_id');
    }

    /**
     * Add a tag to the version.
     */
    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];

        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    /**
     * Remove a tag from the version.
     */
    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];

        $tags = array_filter($tags, fn($t) => $t !== $tag);

        $this->update(['tags' => array_values($tags)]);
    }

    /**
     * Check if version has a specific tag.
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    /**
     * Get formatted version string.
     */
    public function getVersionStringAttribute(): string
    {
        return $this->version_name
            ? "v{$this->version_number} - {$this->version_name}"
            : "v{$this->version_number}";
    }

    /**
     * Scope to get tagged versions.
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope to get manual versions only.
     */
    public function scopeManual($query)
    {
        return $query->where('is_auto_version', false);
    }

    /**
     * Scope to get auto versions only.
     */
    public function scopeAuto($query)
    {
        return $query->where('is_auto_version', true);
    }

    /**
     * Get change summary.
     */
    public function getChangeSummary(): array
    {
        $changes = $this->changes ?? [];

        return [
            'total_changes' => count($changes),
            'added' => count(array_filter($changes, fn($c) => ($c['type'] ?? '') === 'added')),
            'modified' => count(array_filter($changes, fn($c) => ($c['type'] ?? '') === 'modified')),
            'removed' => count(array_filter($changes, fn($c) => ($c['type'] ?? '') === 'removed')),
        ];
    }
}
