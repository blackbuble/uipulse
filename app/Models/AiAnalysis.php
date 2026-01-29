<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'design_id',
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
}
