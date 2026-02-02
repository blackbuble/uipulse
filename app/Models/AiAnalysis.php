<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'design_id',
        'component_id',
        'type',
        'provider',
        'model_name',
        'status',
        'results',
        'prompt',
    ];

    protected $casts = [
        'results' => 'json',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    /**
     * Get the component this analysis belongs to.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}
