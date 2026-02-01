<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentVariant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'component_id',
        'variant_name',
        'description',
        'properties',
        'figma_node',
        'state',
        'is_default',
    ];

    protected $casts = [
        'properties' => 'array',
        'figma_node' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Get the component that owns the variant.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Set as default variant.
     */
    public function setAsDefault(): void
    {
        // Unset other defaults
        $this->component->variants()->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }

    /**
     * Scope to get default variants.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to filter by state.
     */
    public function scopeInState($query, string $state)
    {
        return $query->where('state', $state);
    }
}
