<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Design extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory, Searchable;

    protected $fillable = [
        'project_id',
        'name',
        'figma_url',
        'figma_file_key',
        'figma_node_id',
        'metadata',
        'image_data',
        'status',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'figma_file_key' => $this->figma_file_key,
            'status' => $this->status,
        ];
    }
}
