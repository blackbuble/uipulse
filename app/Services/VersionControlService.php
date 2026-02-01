<?php

namespace App\Services;

use App\Models\Design;
use App\Models\DesignVersion;
use Illuminate\Support\Facades\Log;

class VersionControlService
{
    /**
     * Create a new version snapshot.
     */
    public function createVersion(
        Design $design,
        ?int $userId = null,
        ?string $versionName = null,
        ?string $description = null,
        array $tags = [],
        bool $isAutoVersion = false
    ): DesignVersion {
        Log::info("Creating version for design: {$design->id}");

        // Get the latest version number
        $latestVersion = $design->versions()->latest('version_number')->first();
        $versionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

        // Create snapshot of current state
        $snapshot = $this->createSnapshot($design);

        // Calculate changes from previous version
        $changes = $latestVersion
            ? $this->calculateChanges($latestVersion->snapshot, $snapshot)
            : [];

        $version = DesignVersion::create([
            'design_id' => $design->id,
            'user_id' => $userId,
            'version_number' => $versionNumber,
            'version_name' => $versionName,
            'description' => $description,
            'snapshot' => $snapshot,
            'metadata_snapshot' => $design->metadata,
            'changes' => $changes,
            'previous_version_id' => $latestVersion?->id,
            'tags' => $tags,
            'is_auto_version' => $isAutoVersion,
        ]);

        Log::info("Version created", [
            'version_id' => $version->id,
            'version_number' => $versionNumber,
            'changes_count' => count($changes),
        ]);

        return $version;
    }

    /**
     * Create a snapshot of the design's current state.
     */
    private function createSnapshot(Design $design): array
    {
        return [
            'name' => $design->name,
            'figma_url' => $design->figma_url,
            'figma_file_key' => $design->figma_file_key,
            'figma_node_id' => $design->figma_node_id,
            'status' => $design->status,
            'components' => $design->components()->get()->map(function ($component) {
                return [
                    'id' => $component->id,
                    'type' => $component->type,
                    'name' => $component->name,
                    'properties' => $component->properties,
                    'category' => $component->category,
                ];
            })->toArray(),
            'accessibility_issues' => $design->accessibilityIssues()->get()->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'type' => $issue->type,
                    'severity' => $issue->severity,
                    'status' => $issue->status,
                ];
            })->toArray(),
        ];
    }

    /**
     * Calculate changes between two snapshots.
     */
    private function calculateChanges(array $oldSnapshot, array $newSnapshot): array
    {
        $changes = [];

        // Compare components
        $oldComponents = collect($oldSnapshot['components'] ?? []);
        $newComponents = collect($newSnapshot['components'] ?? []);

        // Find added components
        foreach ($newComponents as $newComp) {
            if (!$oldComponents->contains('id', $newComp['id'])) {
                $changes[] = [
                    'type' => 'added',
                    'entity' => 'component',
                    'data' => $newComp,
                ];
            }
        }

        // Find removed components
        foreach ($oldComponents as $oldComp) {
            if (!$newComponents->contains('id', $oldComp['id'])) {
                $changes[] = [
                    'type' => 'removed',
                    'entity' => 'component',
                    'data' => $oldComp,
                ];
            }
        }

        // Find modified components
        foreach ($newComponents as $newComp) {
            $oldComp = $oldComponents->firstWhere('id', $newComp['id']);

            if ($oldComp && $this->hasChanged($oldComp, $newComp)) {
                $changes[] = [
                    'type' => 'modified',
                    'entity' => 'component',
                    'old' => $oldComp,
                    'new' => $newComp,
                    'diff' => $this->getDiff($oldComp, $newComp),
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if two items have changed.
     */
    private function hasChanged(array $old, array $new): bool
    {
        // Simple comparison - in production, use a more sophisticated diff
        return json_encode($old) !== json_encode($new);
    }

    /**
     * Get detailed diff between two items.
     */
    private function getDiff(array $old, array $new): array
    {
        $diff = [];

        foreach ($new as $key => $value) {
            if (!isset($old[$key]) || $old[$key] !== $value) {
                $diff[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $diff;
    }

    /**
     * Rollback to a specific version.
     */
    public function rollback(Design $design, DesignVersion $version): void
    {
        Log::info("Rolling back design {$design->id} to version {$version->version_number}");

        $snapshot = $version->snapshot;

        // Restore design properties
        $design->update([
            'name' => $snapshot['name'] ?? $design->name,
            'figma_url' => $snapshot['figma_url'] ?? $design->figma_url,
            'figma_file_key' => $snapshot['figma_file_key'] ?? $design->figma_file_key,
            'figma_node_id' => $snapshot['figma_node_id'] ?? $design->figma_node_id,
            'metadata' => $version->metadata_snapshot ?? $design->metadata,
        ]);

        // Create a new version to mark the rollback
        $this->createVersion(
            $design,
            null,
            "Rollback to v{$version->version_number}",
            "Rolled back from current state to version {$version->version_number}",
            ['rollback'],
            false
        );

        Log::info("Rollback completed");
    }

    /**
     * Compare two versions.
     */
    public function compareVersions(DesignVersion $version1, DesignVersion $version2): array
    {
        $snapshot1 = $version1->snapshot;
        $snapshot2 = $version2->snapshot;

        return [
            'version1' => [
                'number' => $version1->version_number,
                'name' => $version1->version_string,
                'created_at' => $version1->created_at,
            ],
            'version2' => [
                'number' => $version2->version_number,
                'name' => $version2->version_string,
                'created_at' => $version2->created_at,
            ],
            'changes' => $this->calculateChanges($snapshot1, $snapshot2),
            'summary' => $this->getComparisonSummary($snapshot1, $snapshot2),
        ];
    }

    /**
     * Get comparison summary.
     */
    private function getComparisonSummary(array $snapshot1, array $snapshot2): array
    {
        $components1 = count($snapshot1['components'] ?? []);
        $components2 = count($snapshot2['components'] ?? []);

        return [
            'components_diff' => $components2 - $components1,
            'components_added' => max(0, $components2 - $components1),
            'components_removed' => max(0, $components1 - $components2),
        ];
    }

    /**
     * Auto-create version on significant changes.
     */
    public function autoVersion(Design $design): ?DesignVersion
    {
        $latestVersion = $design->versions()->latest('created_at')->first();

        // Don't auto-version if last version was created recently (within 1 hour)
        if ($latestVersion && $latestVersion->created_at->diffInHours(now()) < 1) {
            return null;
        }

        return $this->createVersion(
            $design,
            null,
            null,
            'Auto-saved version',
            ['auto-save'],
            true
        );
    }
}
