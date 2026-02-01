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
            // 1. Get design nodes from Figma
            $nodes = $this->getFigmaNodes($design);

            if (empty($nodes)) {
                Log::warning("No Figma nodes found for design: {$design->id}");
                return [];
            }

            // 2. Analyze with AI
            $detectedComponents = $this->aiService->analyzeComponents($nodes);

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

            // 4. Group similar components
            $this->groupSimilarComponents($components);

            Log::info("Component detection completed. Found " . count($components) . " components");

            return $components;
        } catch (\Exception $e) {
            Log::error("Component detection failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get Figma nodes from design.
     */
    private function getFigmaNodes(Design $design): array
    {
        if (!$design->figma_file_key || !$design->figma_node_id) {
            return [];
        }

        // This would integrate with Figma API
        // For now, return mock data or use existing metadata
        return $design->metadata['nodes'] ?? [];
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
     * Group similar components together.
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
                foreach ($group as $component) {
                    $component->update(['usage_count' => count($group)]);
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
