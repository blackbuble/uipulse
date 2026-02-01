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
    ];

    protected $casts = [
        'properties' => 'array',
        'figma_node' => 'array',
        'bounding_box' => 'array',
        'usage_count' => 'integer',
        'variant_count' => 'integer',
        'is_in_library' => 'boolean',
        'added_to_library_at' => 'datetime',
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
}
