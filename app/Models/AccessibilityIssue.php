<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessibilityIssue extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'design_id',
        'component_id',
        'type',
        'wcag_criterion',
        'wcag_level',
        'severity',
        'title',
        'description',
        'recommendation',
        'details',
        'element_info',
        'status',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'details' => 'array',
        'element_info' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the design that owns the issue.
     */
    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    /**
     * Get the component that owns the issue.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Mark issue as resolved.
     */
    public function resolve(?string $notes = null, ?int $userId = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $notes,
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    /**
     * Mark issue as ignored.
     */
    public function ignore(?string $notes = null): void
    {
        $this->update([
            'status' => 'ignored',
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Reopen issue.
     */
    public function reopen(): void
    {
        $this->update([
            'status' => 'open',
            'resolution_notes' => null,
            'resolved_at' => null,
            'resolved_by' => null,
        ]);
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get open issues.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope to get critical issues.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Get severity color for UI.
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get WCAG level badge color.
     */
    public function getWcagLevelColorAttribute(): string
    {
        return match ($this->wcag_level) {
            'A' => 'success',
            'AA' => 'warning',
            'AAA' => 'danger',
            default => 'secondary',
        };
    }
}
