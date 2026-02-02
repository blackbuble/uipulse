<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'design_id',
        'organization_id',
        'type',
        'name',
        'description',
        'properties',
        'figma_node',
        'bounding_box',
        'category',
        'subcategory',
        'usage_count',
        'variant_count',
        'thumbnail_url',
        'is_in_library',
        'added_to_library_at',
        // Versioning
        'version',
        'parent_component_id',
        'changelog',
        'is_latest_version',
        'version_created_at',
        'version_created_by',
    ];

    protected $casts = [
        'properties' => 'array',
        'figma_node' => 'array',
        'bounding_box' => 'array',
        'usage_count' => 'integer',
        'variant_count' => 'integer',
        'is_in_library' => 'boolean',
        'added_to_library_at' => 'datetime',
        'is_latest_version' => 'boolean',
        'version_created_at' => 'datetime',
    ];

    /**
     * Get the design that owns the component.
     */
    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    /**
     * Get the organization that owns the component.
     * TODO: Uncomment when Organization model is implemented
     */
    // public function organization(): BelongsTo
    // {
    //     return $this->belongsTo(Organization::class);
    // }

    /**
     * Get the variants for the component.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ComponentVariant::class);
    }

    /**
     * Get the parent component (for versioning).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'parent_component_id');
    }

    /**
     * Get all child versions of this component.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Component::class, 'parent_component_id')
            ->orderBy('version', 'desc');
    }

    /**
     * Get the user who created this version.
     */
    public function versionCreator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'version_created_by');
    }

    /**
     * Get all detailed version records (Option 2).
     */
    public function detailedVersions(): HasMany
    {
        return $this->hasMany(ComponentVersion::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest published version.
     */
    public function latestPublishedVersion()
    {
        return $this->detailedVersions()
            ->published()
            ->latest()
            ->first();
    }

    /**
     * Get versions on a specific branch.
     */
    public function versionsOnBranch(string $branch)
    {
        return $this->detailedVersions()
            ->onBranch($branch)
            ->get();
    }

    /**
     * Get all AI analyses for this component.
     */
    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest AI analysis.
     */
    public function latestAiAnalysis(?string $type = null)
    {
        $query = $this->aiAnalyses()->latest();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->first();
    }

    /**
     * Check if component has AI analysis of specific type.
     */
    public function hasAiAnalysis(string $type): bool
    {
        return $this->aiAnalyses()->where('type', $type)->exists();
    }

    /**
     * Get the default variant.
     */
    public function defaultVariant()
    {
        return $this->variants()->where('is_default', true)->first();
    }

    /**
     * Add component to library.
     */
    public function addToLibrary(): void
    {
        $this->update([
            'is_in_library' => true,
            'added_to_library_at' => now(),
        ]);
    }

    /**
     * Remove component from library.
     */
    public function removeFromLibrary(): void
    {
        $this->update([
            'is_in_library' => false,
            'added_to_library_at' => null,
        ]);
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get thumbnail URL or generate placeholder.
     */
    public function getThumbnailUrlAttribute($value): string
    {
        return $value ?? "https://ui-avatars.com/api/?name={$this->name}&background=random";
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get library components.
     */
    public function scopeInLibrary($query)
    {
        return $query->where('is_in_library', true);
    }

    /**
     * Scope to filter by organization.
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope to get only latest versions.
     */
    public function scopeLatestVersions($query)
    {
        return $query->where('is_latest_version', true);
    }

    /**
     * Scope to get version history.
     */
    public function scopeVersionHistory($query, $parentId)
    {
        return $query->where('parent_component_id', $parentId)
            ->orWhere('id', $parentId)
            ->orderBy('version', 'desc');
    }

    /**
     * Create a new version of this component.
     */
    public function createNewVersion(array $changes, ?string $changelog = null, ?int $userId = null): Component
    {
        // Mark current version as not latest
        $this->update(['is_latest_version' => false]);

        // Increment version number
        $newVersion = $this->incrementVersion($this->version);

        // Create new version
        $newComponent = $this->replicate();
        $newComponent->version = $newVersion;
        $newComponent->parent_component_id = $this->parent_component_id ?? $this->id;
        $newComponent->changelog = $changelog;
        $newComponent->is_latest_version = true;
        $newComponent->version_created_at = now();
        $newComponent->version_created_by = $userId;

        // Apply changes
        foreach ($changes as $key => $value) {
            $newComponent->$key = $value;
        }

        $newComponent->save();

        return $newComponent;
    }

    /**
     * Increment semantic version.
     */
    protected function incrementVersion(string $currentVersion): string
    {
        $parts = explode('.', $currentVersion);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);

        // Increment minor version
        $minor++;

        return "{$major}.{$minor}";
    }

    /**
     * Get all versions including this one.
     */
    public function getAllVersions()
    {
        $parentId = $this->parent_component_id ?? $this->id;

        return Component::versionHistory($parentId)->get();
    }

    /**
     * Get the latest version of this component.
     */
    public function getLatestVersion(): ?Component
    {
        $parentId = $this->parent_component_id ?? $this->id;

        return Component::where('parent_component_id', $parentId)
            ->orWhere('id', $parentId)
            ->where('is_latest_version', true)
            ->first();
    }

    /**
     * Check if this is the latest version.
     */
    public function isLatestVersion(): bool
    {
        return $this->is_latest_version;
    }

    /**
     * Restore this version as the latest.
     */
    public function restoreAsLatest(?string $changelog = null, ?int $userId = null): Component
    {
        // Mark all versions as not latest
        $parentId = $this->parent_component_id ?? $this->id;
        Component::versionHistory($parentId)->update(['is_latest_version' => false]);

        // Create new version based on this one
        $newVersion = $this->replicate();
        $newVersion->version = $this->incrementVersion($this->getLatestVersion()->version ?? $this->version);
        $newVersion->parent_component_id = $parentId;
        $newVersion->changelog = $changelog ?? "Restored from version {$this->version}";
        $newVersion->is_latest_version = true;
        $newVersion->version_created_at = now();
        $newVersion->version_created_by = $userId;
        $newVersion->save();

        return $newVersion;
    }

    /**
     * Create a detailed version record (Option 2).
     */
    public function createDetailedVersion(
        string $versionNumber,
        ?string $changelog = null,
        ?string $breakingChanges = null,
        string $branch = 'main',
        ?string $tag = null,
        ?int $userId = null
    ): ComponentVersion {
        // Get previous version on same branch
        $previousVersion = $this->detailedVersions()
            ->onBranch($branch)
            ->latest()
            ->first();

        // Create snapshot of current state
        $snapshot = [
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'description' => $this->description,
            'properties' => $this->properties,
            'figma_node' => $this->figma_node,
            'bounding_box' => $this->bounding_box,
        ];

        // Create version record
        $version = $this->detailedVersions()->create([
            'version_number' => $versionNumber,
            'version_tag' => $tag,
            'branch' => $branch,
            'snapshot' => $snapshot,
            'changelog' => $changelog,
            'breaking_changes' => $breakingChanges,
            'created_by' => $userId,
            'previous_version_id' => $previousVersion?->id,
        ]);

        // Calculate and store diff
        if ($previousVersion) {
            $diff = $version->calculateDiff();
            $version->update(['diff' => $diff]);
        }

        return $version;
    }

    /**
     * Restore component from a version snapshot.
     */
    public function restoreFromVersion(ComponentVersion $version, ?int $userId = null): ComponentVersion
    {
        $snapshot = $version->snapshot;

        // Update component with snapshot data
        $this->update([
            'name' => $snapshot['name'] ?? $this->name,
            'type' => $snapshot['type'] ?? $this->type,
            'category' => $snapshot['category'] ?? $this->category,
            'description' => $snapshot['description'] ?? $this->description,
            'properties' => $snapshot['properties'] ?? $this->properties,
        ]);

        // Create new version record for the restoration
        $newVersionNumber = $this->incrementVersion(
            $this->latestPublishedVersion()?->version_number ?? '1.0'
        );

        return $this->createDetailedVersion(
            versionNumber: $newVersionNumber,
            changelog: "Restored from version {$version->version_number}",
            branch: 'main',
            userId: $userId
        );
    }
}

