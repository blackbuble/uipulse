<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DesignAnnotation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'design_id',
        'user_id',
        'type',
        'data',
        'color',
        'stroke_width',
        'label',
        'comment_id',
    ];

    protected $casts = [
        'data' => 'array',
        'stroke_width' => 'integer',
    ];

    /**
     * Get the design that owns the annotation.
     */
    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    /**
     * Get the user who created the annotation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comment linked to this annotation.
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(DesignComment::class);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get SVG path for the annotation.
     */
    public function getSvgPath(): string
    {
        $data = $this->data;

        return match ($this->type) {
            'rectangle' => $this->getRectanglePath($data),
            'circle' => $this->getCirclePath($data),
            'arrow' => $this->getArrowPath($data),
            'line' => $this->getLinePath($data),
            default => '',
        };
    }

    /**
     * Get rectangle SVG path.
     */
    private function getRectanglePath(array $data): string
    {
        $x = $data['x'] ?? 0;
        $y = $data['y'] ?? 0;
        $width = $data['width'] ?? 100;
        $height = $data['height'] ?? 100;

        return "<rect x=\"{$x}\" y=\"{$y}\" width=\"{$width}\" height=\"{$height}\" stroke=\"{$this->color}\" stroke-width=\"{$this->stroke_width}\" fill=\"none\" />";
    }

    /**
     * Get circle SVG path.
     */
    private function getCirclePath(array $data): string
    {
        $cx = $data['cx'] ?? 0;
        $cy = $data['cy'] ?? 0;
        $r = $data['r'] ?? 50;

        return "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$r}\" stroke=\"{$this->color}\" stroke-width=\"{$this->stroke_width}\" fill=\"none\" />";
    }

    /**
     * Get arrow SVG path.
     */
    private function getArrowPath(array $data): string
    {
        $x1 = $data['x1'] ?? 0;
        $y1 = $data['y1'] ?? 0;
        $x2 = $data['x2'] ?? 100;
        $y2 = $data['y2'] ?? 100;

        return "<line x1=\"{$x1}\" y1=\"{$y1}\" x2=\"{$x2}\" y2=\"{$y2}\" stroke=\"{$this->color}\" stroke-width=\"{$this->stroke_width}\" marker-end=\"url(#arrowhead)\" />";
    }

    /**
     * Get line SVG path.
     */
    private function getLinePath(array $data): string
    {
        $x1 = $data['x1'] ?? 0;
        $y1 = $data['y1'] ?? 0;
        $x2 = $data['x2'] ?? 100;
        $y2 = $data['y2'] ?? 100;

        return "<line x1=\"{$x1}\" y1=\"{$y1}\" x2=\"{$x2}\" y2=\"{$y2}\" stroke=\"{$this->color}\" stroke-width=\"{$this->stroke_width}\" />";
    }
}
