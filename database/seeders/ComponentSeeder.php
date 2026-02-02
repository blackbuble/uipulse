<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\Design;
use Illuminate\Database\Seeder;

class ComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a design to attach components to
        $design = Design::first();

        if (!$design) {
            $this->command->warn('No designs found. Please create a design first.');
            return;
        }

        $this->command->info("Creating mock components for design: {$design->name}");

        $components = [
            [
                'name' => 'Primary Button',
                'type' => 'button',
                'category' => 'navigation',
                'description' => 'Main call-to-action button with primary brand color',
                'properties' => [
                    'backgroundColor' => '#3B82F6',
                    'textColor' => '#FFFFFF',
                    'borderRadius' => 8,
                    'padding' => '12px 24px',
                    'fontSize' => '16px',
                    'fontWeight' => '600',
                ],
                'is_in_library' => true,
                'usage_count' => 15,
            ],
            [
                'name' => 'Secondary Button',
                'type' => 'button',
                'category' => 'navigation',
                'description' => 'Secondary action button with outline style',
                'properties' => [
                    'backgroundColor' => 'transparent',
                    'textColor' => '#3B82F6',
                    'borderColor' => '#3B82F6',
                    'borderRadius' => 8,
                    'padding' => '12px 24px',
                ],
                'is_in_library' => true,
                'usage_count' => 8,
            ],
            [
                'name' => 'Text Input Field',
                'type' => 'input',
                'category' => 'form',
                'description' => 'Standard text input with label and placeholder',
                'properties' => [
                    'backgroundColor' => '#FFFFFF',
                    'borderColor' => '#D1D5DB',
                    'borderRadius' => 6,
                    'padding' => '10px 14px',
                    'fontSize' => '14px',
                ],
                'is_in_library' => true,
                'usage_count' => 12,
            ],
            [
                'name' => 'Feature Card',
                'type' => 'card',
                'category' => 'layout',
                'description' => 'Card component for displaying features with icon and description',
                'properties' => [
                    'backgroundColor' => '#FFFFFF',
                    'borderRadius' => 12,
                    'padding' => '24px',
                    'boxShadow' => '0 4px 6px rgba(0,0,0,0.1)',
                ],
                'is_in_library' => true,
                'usage_count' => 6,
            ],
            [
                'name' => 'Navigation Menu',
                'type' => 'navigation',
                'category' => 'navigation',
                'description' => 'Main navigation menu with links',
                'properties' => [
                    'backgroundColor' => '#1F2937',
                    'textColor' => '#FFFFFF',
                    'padding' => '16px 32px',
                ],
                'is_in_library' => true,
                'usage_count' => 1,
            ],
            [
                'name' => 'Modal Dialog',
                'type' => 'modal',
                'category' => 'overlay',
                'description' => 'Modal dialog for confirmations and forms',
                'properties' => [
                    'backgroundColor' => '#FFFFFF',
                    'borderRadius' => 16,
                    'maxWidth' => '500px',
                    'padding' => '32px',
                ],
                'is_in_library' => false,
                'usage_count' => 3,
            ],
            [
                'name' => 'Checkbox',
                'type' => 'checkbox',
                'category' => 'form',
                'description' => 'Custom styled checkbox input',
                'properties' => [
                    'size' => '20px',
                    'borderRadius' => 4,
                    'checkedColor' => '#3B82F6',
                ],
                'is_in_library' => true,
                'usage_count' => 10,
            ],
            [
                'name' => 'Dropdown Select',
                'type' => 'select',
                'category' => 'form',
                'description' => 'Dropdown select input with custom styling',
                'properties' => [
                    'backgroundColor' => '#FFFFFF',
                    'borderColor' => '#D1D5DB',
                    'borderRadius' => 6,
                    'padding' => '10px 14px',
                ],
                'is_in_library' => true,
                'usage_count' => 7,
            ],
        ];

        foreach ($components as $componentData) {
            Component::create([
                'design_id' => $design->id,
                'organization_id' => $design->project->organization_id ?? null,
                'name' => $componentData['name'],
                'type' => $componentData['type'],
                'category' => $componentData['category'],
                'description' => $componentData['description'],
                'properties' => $componentData['properties'],
                'figma_node' => [],
                'bounding_box' => [],
                'is_in_library' => $componentData['is_in_library'],
                'usage_count' => $componentData['usage_count'],
            ]);
        }

        $this->command->info('✓ Created ' . count($components) . ' mock components');
    }
}
