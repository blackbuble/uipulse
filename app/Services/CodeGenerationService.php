<?php

namespace App\Services;

use App\Models\Component;
use Illuminate\Support\Facades\Log;

class CodeGenerationService
{
    public function __construct(
        private AiService $aiService
    ) {
    }

    /**
     * Generate code for a component.
     */
    public function generateCode(Component $component, string $framework = 'react'): array
    {
        Log::info("Generating {$framework} code for component: {$component->name}");

        try {
            $code = match ($framework) {
                'react' => $this->generateReactCode($component),
                'vue' => $this->generateVueCode($component),
                'html' => $this->generateHtmlCode($component),
                default => throw new \InvalidArgumentException("Unsupported framework: {$framework}"),
            };

            return [
                'framework' => $framework,
                'component_name' => $component->name,
                'code' => $code,
                'filename' => $this->getFilename($component, $framework),
            ];

        } catch (\Exception $e) {
            Log::error("Code generation failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate React + TypeScript code.
     */
    private function generateReactCode(Component $component): string
    {
        $props = $component->properties;
        $componentName = $this->toPascalCase($component->name);

        // Extract props from component properties
        $propsInterface = $this->generatePropsInterface($component);
        $styles = $this->generateTailwindClasses($component);

        return <<<REACT
        import React from 'react';

        {$propsInterface}

        export const {$componentName}: React.FC<{$componentName}Props> = ({
          children,
          className = '',
          ...props
        }) => {
          return (
            <{$this->getHtmlTag($component->type)}
              className={\`{$styles} \${className}\`}
              {...props}
            >
              {children}
            </{$this->getHtmlTag($component->type)}>
          );
        };

        {$componentName}.displayName = '{$componentName}';
        REACT;
    }

    /**
     * Generate Vue 3 + TypeScript code.
     */
    private function generateVueCode(Component $component): string
    {
        $componentName = $this->toPascalCase($component->name);
        $styles = $this->generateTailwindClasses($component);
        $propsInterface = $this->generateVueProps($component);

        return <<<VUE
        <script setup lang="ts">
        {$propsInterface}

        const props = withDefaults(defineProps<Props>(), {
          className: '',
        });
        </script>

        <template>
          <{$this->getHtmlTag($component->type)}
            :class="['{$styles}', props.className]"
          >
            <slot />
          </{$this->getHtmlTag($component->type)}>
        </template>
        VUE;
    }

    /**
     * Generate HTML + Tailwind CSS code.
     */
    private function generateHtmlCode(Component $component): string
    {
        $styles = $this->generateTailwindClasses($component);
        $tag = $this->getHtmlTag($component->type);

        return <<<HTML
        <{$tag} class="{$styles}">
          <!-- Component content -->
        </{$tag}>
        HTML;
    }

    /**
     * Generate TypeScript props interface.
     */
    private function generatePropsInterface(Component $component): string
    {
        $componentName = $this->toPascalCase($component->name);
        $props = $component->properties;

        $interfaceProps = [
            'children?: React.ReactNode;',
            'className?: string;',
        ];

        // Add component-specific props based on type
        if ($component->type === 'button') {
            $interfaceProps[] = 'onClick?: () => void;';
            $interfaceProps[] = 'disabled?: boolean;';
            $interfaceProps[] = 'type?: "button" | "submit" | "reset";';
        } elseif ($component->type === 'input') {
            $interfaceProps[] = 'value?: string;';
            $interfaceProps[] = 'onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;';
            $interfaceProps[] = 'placeholder?: string;';
            $interfaceProps[] = 'disabled?: boolean;';
        }

        $propsString = implode("\n  ", $interfaceProps);

        return <<<INTERFACE
        interface {$componentName}Props {
          {$propsString}
        }
        INTERFACE;
    }

    /**
     * Generate Vue props definition.
     */
    private function generateVueProps(Component $component): string
    {
        return <<<PROPS
        interface Props {
          className?: string;
        }
        PROPS;
    }

    /**
     * Generate Tailwind CSS classes from component properties.
     */
    private function generateTailwindClasses(Component $component): string
    {
        $props = $component->properties;
        $classes = [];

        // Layout
        if (isset($props['dimensions'])) {
            if (isset($props['dimensions']['width'])) {
                $classes[] = $this->convertToTailwind('width', $props['dimensions']['width']);
            }
            if (isset($props['dimensions']['height'])) {
                $classes[] = $this->convertToTailwind('height', $props['dimensions']['height']);
            }
        }

        // Colors
        if (isset($props['colors'])) {
            if (isset($props['colors']['background'])) {
                $classes[] = 'bg-' . $this->colorToTailwind($props['colors']['background']);
            }
            if (isset($props['colors']['text'])) {
                $classes[] = 'text-' . $this->colorToTailwind($props['colors']['text']);
            }
            if (isset($props['colors']['border'])) {
                $classes[] = 'border-' . $this->colorToTailwind($props['colors']['border']);
            }
        }

        // Spacing
        if (isset($props['spacing'])) {
            if (isset($props['spacing']['padding'])) {
                $classes[] = 'p-' . $this->spacingToTailwind($props['spacing']['padding']);
            }
            if (isset($props['spacing']['margin'])) {
                $classes[] = 'm-' . $this->spacingToTailwind($props['spacing']['margin']);
            }
        }

        // Border radius
        if (isset($props['borderRadius'])) {
            $classes[] = 'rounded-' . $this->borderRadiusToTailwind($props['borderRadius']);
        }

        // Typography
        if (isset($props['typography'])) {
            if (isset($props['typography']['fontSize'])) {
                $classes[] = 'text-' . $this->fontSizeToTailwind($props['typography']['fontSize']);
            }
            if (isset($props['typography']['fontWeight'])) {
                $classes[] = 'font-' . $this->fontWeightToTailwind($props['typography']['fontWeight']);
            }
        }

        // Component-specific classes
        $classes = array_merge($classes, $this->getComponentSpecificClasses($component));

        return implode(' ', array_filter($classes));
    }

    /**
     * Get component-specific Tailwind classes.
     */
    private function getComponentSpecificClasses(Component $component): array
    {
        return match ($component->type) {
            'button' => ['inline-flex', 'items-center', 'justify-center', 'transition-colors', 'focus:outline-none', 'focus:ring-2'],
            'input' => ['border', 'focus:ring-2', 'focus:border-blue-500', 'outline-none'],
            'card' => ['border', 'shadow-sm'],
            'modal' => ['fixed', 'inset-0', 'z-50', 'flex', 'items-center', 'justify-center'],
            default => [],
        };
    }

    /**
     * Convert color to Tailwind color name.
     */
    private function colorToTailwind(string $color): string
    {
        // Simple color mapping - in production, use a color similarity algorithm
        $colorMap = [
            '#000000' => 'black',
            '#ffffff' => 'white',
            '#3b82f6' => 'blue-500',
            '#10b981' => 'green-500',
            '#ef4444' => 'red-500',
        ];

        return $colorMap[strtolower($color)] ?? 'gray-500';
    }

    /**
     * Convert spacing value to Tailwind spacing scale.
     */
    private function spacingToTailwind($spacing): string
    {
        if (is_numeric($spacing)) {
            $px = (int) $spacing;
            return match (true) {
                $px <= 4 => '1',
                $px <= 8 => '2',
                $px <= 12 => '3',
                $px <= 16 => '4',
                $px <= 24 => '6',
                $px <= 32 => '8',
                default => '12',
            };
        }

        return '4';
    }

    /**
     * Convert border radius to Tailwind.
     */
    private function borderRadiusToTailwind($radius): string
    {
        if (is_numeric($radius)) {
            $px = (int) $radius;
            return match (true) {
                $px <= 2 => 'sm',
                $px <= 4 => '',
                $px <= 8 => 'md',
                $px <= 12 => 'lg',
                $px <= 16 => 'xl',
                default => 'full',
            };
        }

        return 'md';
    }

    /**
     * Convert font size to Tailwind.
     */
    private function fontSizeToTailwind($size): string
    {
        if (is_numeric($size)) {
            $px = (int) $size;
            return match (true) {
                $px <= 12 => 'xs',
                $px <= 14 => 'sm',
                $px <= 16 => 'base',
                $px <= 18 => 'lg',
                $px <= 20 => 'xl',
                default => '2xl',
            };
        }

        return 'base';
    }

    /**
     * Convert font weight to Tailwind.
     */
    private function fontWeightToTailwind($weight): string
    {
        if (is_numeric($weight)) {
            return match ((int) $weight) {
                100 => 'thin',
                200 => 'extralight',
                300 => 'light',
                400 => 'normal',
                500 => 'medium',
                600 => 'semibold',
                700 => 'bold',
                800 => 'extrabold',
                900 => 'black',
                default => 'normal',
            };
        }

        return 'normal';
    }

    /**
     * Convert to Tailwind utility.
     */
    private function convertToTailwind(string $property, $value): string
    {
        // Simplified conversion - expand as needed
        return '';
    }

    /**
     * Get HTML tag for component type.
     */
    private function getHtmlTag(string $type): string
    {
        return match ($type) {
            'button' => 'button',
            'input' => 'input',
            'card' => 'div',
            'modal' => 'div',
            'heading' => 'h2',
            default => 'div',
        };
    }

    /**
     * Get filename for generated code.
     */
    private function getFilename(Component $component, string $framework): string
    {
        $name = $this->toPascalCase($component->name);

        return match ($framework) {
            'react' => "{$name}.tsx",
            'vue' => "{$name}.vue",
            'html' => "{$name}.html",
            default => "{$name}.txt",
        };
    }

    /**
     * Convert string to PascalCase.
     */
    private function toPascalCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }
}
