<?php

namespace App\Services;

use App\Models\Design;
use App\Models\DesignVersion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DesignVersionService
{
    /**
     * Fetch version history from Figma API.
     */
    public function fetchVersionsFromFigma(Design $design): array
    {
        $figmaToken = config('services.figma.token');

        if (!$figmaToken) {
            throw new \Exception('Figma token not configured');
        }

        if (!$design->figma_file_key) {
            throw new \Exception('Design does not have Figma file key');
        }

        try {
            // Fetch version history from Figma
            $response = Http::withHeaders([
                'X-Figma-Token' => $figmaToken
            ])->timeout(30)
                ->get("https://api.figma.com/v1/files/{$design->figma_file_key}/versions");

            if (!$response->successful()) {
                Log::error('Figma API version fetch failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to fetch versions from Figma: ' . $response->body());
            }

            $data = $response->json();

            return $data['versions'] ?? [];

        } catch (\Exception $e) {
            Log::error('Error fetching Figma versions', [
                'design_id' => $design->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync Figma versions to database.
     */
    public function syncVersionsFromFigma(Design $design, int $userId): int
    {
        $figmaVersions = $this->fetchVersionsFromFigma($design);

        if (empty($figmaVersions)) {
            return 0;
        }

        $synced = 0;

        foreach ($figmaVersions as $index => $figmaVersion) {
            // Check if version already exists
            $exists = DesignVersion::where('design_id', $design->id)
                ->where('version_number', $index + 1)
                ->exists();

            if ($exists) {
                continue;
            }

            // Create version record
            DesignVersion::create([
                'design_id' => $design->id,
                'user_id' => $userId,
                'version_number' => $index + 1,
                'version_name' => $figmaVersion['label'] ?? null,
                'description' => $figmaVersion['description'] ?? null,
                'snapshot' => [
                    'figma_version_id' => $figmaVersion['id'],
                    'created_at' => $figmaVersion['created_at'] ?? null,
                    'user' => $figmaVersion['user'] ?? null,
                ],
                'is_auto_version' => true,
                'created_at' => $figmaVersion['created_at'] ?? now(),
            ]);

            $synced++;
        }

        Log::info('Synced Figma versions', [
            'design_id' => $design->id,
            'versions_synced' => $synced,
        ]);

        return $synced;
    }

    /**
     * Create manual version snapshot.
     */
    public function createManualVersion(
        Design $design,
        int $userId,
        ?string $versionName = null,
        ?string $description = null,
        ?array $tags = null
    ): DesignVersion {
        // Get next version number
        $latestVersion = DesignVersion::where('design_id', $design->id)
            ->orderBy('version_number', 'desc')
            ->first();

        $versionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

        // Create snapshot of current design state
        $snapshot = [
            'name' => $design->name,
            'figma_url' => $design->figma_url,
            'figma_file_key' => $design->figma_file_key,
            'figma_node_id' => $design->figma_node_id,
            'status' => $design->status,
            'metadata' => $design->metadata,
            'components_count' => $design->components()->count(),
            'created_at' => now()->toIso8601String(),
        ];

        // Calculate changes from previous version
        $changes = null;
        if ($latestVersion) {
            $changes = $this->calculateChanges($latestVersion->snapshot, $snapshot);
        }

        return DesignVersion::create([
            'design_id' => $design->id,
            'user_id' => $userId,
            'version_number' => $versionNumber,
            'version_name' => $versionName,
            'description' => $description,
            'snapshot' => $snapshot,
            'metadata_snapshot' => $design->metadata,
            'changes' => $changes,
            'tags' => $tags,
            'is_auto_version' => false,
            'previous_version_id' => $latestVersion?->id,
        ]);
    }

    /**
     * Calculate changes between two snapshots.
     */
    protected function calculateChanges(array $oldSnapshot, array $newSnapshot): array
    {
        $changes = [];

        foreach ($newSnapshot as $key => $value) {
            if (!isset($oldSnapshot[$key])) {
                $changes['added'][$key] = $value;
            } elseif ($oldSnapshot[$key] !== $value) {
                $changes['modified'][$key] = [
                    'from' => $oldSnapshot[$key],
                    'to' => $value,
                ];
            }
        }

        foreach ($oldSnapshot as $key => $value) {
            if (!isset($newSnapshot[$key])) {
                $changes['removed'][$key] = $value;
            }
        }

        return $changes;
    }

    /**
     * Restore design from a version.
     */
    public function restoreFromVersion(DesignVersion $version, int $userId): DesignVersion
    {
        $design = $version->design;
        $snapshot = $version->snapshot;

        // Update design with snapshot data
        $design->update([
            'name' => $snapshot['name'] ?? $design->name,
            'figma_url' => $snapshot['figma_url'] ?? $design->figma_url,
            'figma_file_key' => $snapshot['figma_file_key'] ?? $design->figma_file_key,
            'figma_node_id' => $snapshot['figma_node_id'] ?? $design->figma_node_id,
            'status' => $snapshot['status'] ?? $design->status,
            'metadata' => $snapshot['metadata'] ?? $design->metadata,
        ]);

        // Create new version for the restoration
        return $this->createManualVersion(
            design: $design,
            userId: $userId,
            versionName: "Restored from v{$version->version_number}",
            description: "Restored design state from version {$version->version_number}",
            tags: ['restored']
        );
    }
}
