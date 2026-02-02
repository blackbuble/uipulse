<?php

namespace App\Services;

use App\Models\Component;
use App\Models\Design;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComponentDetectionService
{
    public function __construct(
        private AiService $aiService
    ) {
    }

    /**
     * Detect components from a design.
     */
    public function detectComponents(Design $design): array
    {
        Log::info("Starting component detection for design: {$design->id}");

        try {
            // 1. Get all pages/frames from Figma
            $allNodes = $this->getAllFigmaNodes($design);

            if (empty($allNodes)) {
                Log::warning("No Figma nodes found for design: {$design->id}");
                return [];
            }

            Log::info("Found " . count($allNodes) . " pages/frames to analyze");

            // 2. Analyze with AI (batch processing for multiple pages)
            $detectedComponents = $this->aiService->analyzeComponents($allNodes);

            // 3. Save to database
            $components = [];
            foreach ($detectedComponents as $componentData) {
                $component = $this->createComponent($design, $componentData);
                $components[] = $component;

                // Create variants if detected
                if (isset($componentData['variants']) && is_array($componentData['variants'])) {
                    $this->createVariants($component, $componentData['variants']);
                }
            }

            // 4. Group similar components across all pages
            $this->groupSimilarComponents($components);

            Log::info("Component detection completed. Found " . count($components) . " components across all pages");

            return $components;
        } catch (\Exception $e) {
            Log::error("Component detection failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all Figma nodes from all pages/frames in the file.
     */
    private function getAllFigmaNodes(Design $design): array
    {
        if (!$design->figma_file_key) {
            return $design->metadata['nodes'] ?? [];
        }

        $figmaToken = config('services.figma.token');

        if (!$figmaToken) {
            Log::warning("Figma token not configured, using metadata");
            return $design->metadata['nodes'] ?? [];
        }

        try {
            // Get file structure from Figma API with optimized parameters
            $response = Http::withHeaders([
                'X-Figma-Token' => $figmaToken
            ])
                ->timeout(30)
                ->get("https://api.figma.com/v1/files/{$design->figma_file_key}", [
                    'depth' => 4,  // Increased depth to capture frames and their children
                    'geometry' => 'paths',  // Only get paths, not full geometry
                ]);

            if (!$response->successful()) {
                Log::error("Figma API request failed: " . $response->body());
                return $design->metadata['nodes'] ?? [];
            }

            $fileData = $response->json();
            $allNodes = [];

            // Extract nodes from all pages
            if (isset($fileData['document']['children'])) {
                foreach ($fileData['document']['children'] as $page) {
                    if ($page['type'] === 'CANVAS') {
                        Log::info("Processing page: {$page['name']}");

                        // Get all frames/components in this page
                        if (isset($page['children'])) {
                            foreach ($page['children'] as $frame) {
                                $allNodes[] = [
                                    'page' => $page['name'],
                                    'frame' => $frame['name'],
                                    'node' => $frame,
                                    'children' => $this->extractChildNodes($frame),
                                ];
                            }
                        }
                    }
                }
            }

            // If specific node_id is provided, filter to that node
            if ($design->figma_node_id && !empty($allNodes)) {
                $nodeId = str_replace('-', ':', $design->figma_node_id);
                $allNodes = array_filter($allNodes, function ($item) use ($nodeId) {
                    return isset($item['node']['id']) && $item['node']['id'] === $nodeId;
                });
            }

            Log::info("Extracted " . count($allNodes) . " frames from Figma file");

            return array_values($allNodes);
        } catch (\Exception $e) {
            Log::error("Failed to fetch Figma nodes: " . $e->getMessage());
            return $design->metadata['nodes'] ?? [];
        }
    }

    /**
     * Recursively extract child nodes from a frame.
     */
    private function extractChildNodes(array $node, int $depth = 0, int $maxDepth = 5): array
    {
        if ($depth >= $maxDepth || !isset($node['children'])) {
            return [];
        }

        $children = [];
        foreach ($node['children'] as $child) {
            $children[] = [
                'id' => $child['id'] ?? null,
                'name' => $child['name'] ?? 'Unnamed',
                'type' => $child['type'] ?? 'UNKNOWN',
                'bounds' => $child['absoluteBoundingBox'] ?? null,
                'properties' => $this->extractNodeProperties($child),
                'children' => $this->extractChildNodes($child, $depth + 1, $maxDepth),
            ];
        }

        return $children;
    }

    /**
     * Extract relevant properties from a Figma node.
     */
    private function extractNodeProperties(array $node): array
    {
        return [
            'visible' => $node['visible'] ?? true,
            'opacity' => $node['opacity'] ?? 1,
            'fills' => $node['fills'] ?? [],
            'strokes' => $node['strokes'] ?? [],
            'effects' => $node['effects'] ?? [],
            'cornerRadius' => $node['cornerRadius'] ?? null,
            'constraints' => $node['constraints'] ?? null,
            'layoutMode' => $node['layoutMode'] ?? null,
            'primaryAxisSizingMode' => $node['primaryAxisSizingMode'] ?? null,
            'counterAxisSizingMode' => $node['counterAxisSizingMode'] ?? null,
            'paddingLeft' => $node['paddingLeft'] ?? null,
            'paddingRight' => $node['paddingRight'] ?? null,
            'paddingTop' => $node['paddingTop'] ?? null,
            'paddingBottom' => $node['paddingBottom'] ?? null,
            'itemSpacing' => $node['itemSpacing'] ?? null,
        ];
    }

    /**
     * Get Figma nodes from design (legacy method for backward compatibility).
     */
    private function getFigmaNodes(Design $design): array
    {
        return $this->getAllFigmaNodes($design);
    }

    /**
     * Create a component from detected data.
     */
    private function createComponent(Design $design, array $data): Component
    {
        return Component::create([
            'design_id' => $design->id,
            'organization_id' => $design->project->organization_id ?? null,
            'type' => $data['type'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'properties' => $data['properties'] ?? [],
            'figma_node' => $data['node'] ?? [],
            'bounding_box' => $data['bounds'] ?? [],
            'category' => $this->categorizeComponent($data['type']),
            'subcategory' => $data['subcategory'] ?? null,
        ]);
    }

    /**
     * Create variants for a component.
     */
    private function createVariants(Component $component, array $variants): void
    {
        foreach ($variants as $index => $variantData) {
            $component->variants()->create([
                'variant_name' => $variantData['name'],
                'description' => $variantData['description'] ?? null,
                'properties' => $variantData['properties'] ?? [],
                'figma_node' => $variantData['node'] ?? null,
                'state' => $variantData['state'] ?? null,
                'is_default' => $index === 0, // First variant is default
            ]);
        }

        // Update variant count
        $component->update(['variant_count' => count($variants)]);
    }

    /**
     * Categorize component by type.
     */
    private function categorizeComponent(string $type): string
    {
        return match (strtolower($type)) {
            'button', 'link', 'nav', 'menu' => 'navigation',
            'input', 'select', 'checkbox', 'radio', 'textarea', 'form' => 'form',
            'card', 'container', 'grid', 'flex', 'section', 'div' => 'layout',
            'modal', 'dialog', 'tooltip', 'popover', 'dropdown' => 'overlay',
            'text', 'heading', 'paragraph', 'label' => 'typography',
            'icon', 'image', 'avatar' => 'media',
            default => 'other',
        };
    }

    /**
     * Group similar components together (across all pages).
     */
    private function groupSimilarComponents(array $components): void
    {
        // Group components by type and similar properties
        $groups = [];

        foreach ($components as $component) {
            $signature = $this->getComponentSignature($component);

            if (!isset($groups[$signature])) {
                $groups[$signature] = [];
            }

            $groups[$signature][] = $component;
        }

        // Update usage count for similar components
        foreach ($groups as $group) {
            if (count($group) > 1) {
                // Mark the first one as the primary/library version
                $group[0]->update([
                    'usage_count' => count($group),
                    'in_library' => true,
                ]);

                // Update others as instances
                for ($i = 1; $i < count($group); $i++) {
                    $group[$i]->update([
                        'usage_count' => count($group),
                    ]);
                }
            }
        }
    }

    /**
     * Get component signature for grouping.
     */
    private function getComponentSignature(Component $component): string
    {
        $props = $component->properties;

        return md5(json_encode([
            'type' => $component->type,
            'category' => $component->category,
            'width' => $props['width'] ?? null,
            'height' => $props['height'] ?? null,
            'backgroundColor' => $props['backgroundColor'] ?? null,
        ]));
    }

    /**
     * Detect component type from Figma node.
     */
    public function detectComponentType(array $node): string
    {
        $name = strtolower($node['name'] ?? '');
        $type = strtolower($node['type'] ?? '');

        // Check node name for hints
        if (str_contains($name, 'button') || str_contains($name, 'btn')) {
            return 'button';
        }

        if (str_contains($name, 'input') || str_contains($name, 'field')) {
            return 'input';
        }

        if (str_contains($name, 'card')) {
            return 'card';
        }

        if (str_contains($name, 'modal') || str_contains($name, 'dialog')) {
            return 'modal';
        }

        if (str_contains($name, 'nav') || str_contains($name, 'menu')) {
            return 'navigation';
        }

        // Fallback to Figma node type
        return match ($type) {
            'FRAME', 'COMPONENT' => 'container',
            'TEXT' => 'text',
            'RECTANGLE' => 'box',
            'VECTOR' => 'icon',
            default => 'unknown',
        };
    }
}
