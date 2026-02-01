<?php

namespace App\Services;

use App\Models\AccessibilityIssue;
use App\Models\Component;
use App\Models\Design;
use Illuminate\Support\Facades\Log;

class AccessibilityService
{
    /**
     * Analyze design for accessibility issues.
     */
    public function analyzeDesign(Design $design): array
    {
        Log::info("Starting accessibility analysis for design: {$design->id}");

        $issues = [];

        // Get all components for this design
        $components = $design->components;

        foreach ($components as $component) {
            $componentIssues = $this->analyzeComponent($component);
            $issues = array_merge($issues, $componentIssues);
        }

        Log::info("Accessibility analysis completed", [
            'design_id' => $design->id,
            'issues_found' => count($issues),
        ]);

        return $issues;
    }

    /**
     * Analyze a single component.
     */
    public function analyzeComponent(Component $component): array
    {
        $issues = [];

        // Run all checks
        $issues = array_merge($issues, $this->checkColorContrast($component));
        $issues = array_merge($issues, $this->checkTextSize($component));
        $issues = array_merge($issues, $this->checkTouchTargets($component));
        $issues = array_merge($issues, $this->checkAltText($component));
        $issues = array_merge($issues, $this->checkHeadingHierarchy($component));

        // Save issues to database
        foreach ($issues as $issueData) {
            AccessibilityIssue::create(array_merge($issueData, [
                'design_id' => $component->design_id,
                'component_id' => $component->id,
            ]));
        }

        return $issues;
    }

    /**
     * Check color contrast (WCAG 1.4.3).
     */
    private function checkColorContrast(Component $component): array
    {
        $issues = [];
        $props = $component->properties;

        if (!isset($props['colors']['background']) || !isset($props['colors']['text'])) {
            return $issues;
        }

        $bgColor = $props['colors']['background'];
        $textColor = $props['colors']['text'];

        $ratio = $this->calculateContrastRatio($bgColor, $textColor);
        $fontSize = $props['typography']['fontSize'] ?? 16;

        // WCAG AA requires 4.5:1 for normal text, 3:1 for large text (18pt+)
        $requiredRatio = $fontSize >= 18 ? 3.0 : 4.5;

        if ($ratio < $requiredRatio) {
            $issues[] = [
                'type' => 'contrast',
                'wcag_criterion' => '1.4.3',
                'wcag_level' => 'AA',
                'severity' => $ratio < 3.0 ? 'critical' : 'high',
                'title' => 'Insufficient Color Contrast',
                'description' => "Contrast ratio of {$ratio}:1 is below the required {$requiredRatio}:1 for {$fontSize}px text.",
                'recommendation' => "Increase contrast between text ({$textColor}) and background ({$bgColor}) to at least {$requiredRatio}:1.",
                'details' => [
                    'contrast_ratio' => $ratio,
                    'required_ratio' => $requiredRatio,
                    'background_color' => $bgColor,
                    'text_color' => $textColor,
                    'font_size' => $fontSize,
                ],
                'status' => 'open',
            ];
        }

        return $issues;
    }

    /**
     * Calculate contrast ratio between two colors.
     */
    private function calculateContrastRatio(string $color1, string $color2): float
    {
        $l1 = $this->getRelativeLuminance($color1);
        $l2 = $this->getRelativeLuminance($color2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    /**
     * Get relative luminance of a color.
     */
    private function getRelativeLuminance(string $hex): float
    {
        // Remove # if present
        $hex = ltrim($hex, '#');

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        // Apply gamma correction
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        // Calculate luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Check text size (WCAG 1.4.4).
     */
    private function checkTextSize(Component $component): array
    {
        $issues = [];
        $props = $component->properties;

        if (!isset($props['typography']['fontSize'])) {
            return $issues;
        }

        $fontSize = $props['typography']['fontSize'];

        // Minimum recommended size is 16px for body text
        if ($fontSize < 12) {
            $issues[] = [
                'type' => 'text_size',
                'wcag_criterion' => '1.4.4',
                'wcag_level' => 'AA',
                'severity' => 'high',
                'title' => 'Text Too Small',
                'description' => "Font size of {$fontSize}px is below the minimum recommended size of 12px.",
                'recommendation' => "Increase font size to at least 16px for body text.",
                'details' => [
                    'current_size' => $fontSize,
                    'minimum_size' => 12,
                    'recommended_size' => 16,
                ],
                'status' => 'open',
            ];
        }

        return $issues;
    }

    /**
     * Check touch target size (WCAG 2.5.5).
     */
    private function checkTouchTargets(Component $component): array
    {
        $issues = [];

        // Only check interactive elements
        if (!in_array($component->type, ['button', 'link', 'input', 'checkbox', 'radio'])) {
            return $issues;
        }

        $props = $component->properties;
        $width = $props['dimensions']['width'] ?? 0;
        $height = $props['dimensions']['height'] ?? 0;

        // WCAG 2.5.5 Level AAA requires 44x44px minimum
        $minSize = 44;

        if ($width < $minSize || $height < $minSize) {
            $issues[] = [
                'type' => 'touch_target',
                'wcag_criterion' => '2.5.5',
                'wcag_level' => 'AAA',
                'severity' => 'medium',
                'title' => 'Touch Target Too Small',
                'description' => "Interactive element ({$width}x{$height}px) is smaller than the recommended {$minSize}x{$minSize}px.",
                'recommendation' => "Increase the size or add padding to make the touch target at least {$minSize}x{$minSize}px.",
                'details' => [
                    'current_width' => $width,
                    'current_height' => $height,
                    'minimum_size' => $minSize,
                ],
                'status' => 'open',
            ];
        }

        return $issues;
    }

    /**
     * Check for alt text on images (WCAG 1.1.1).
     */
    private function checkAltText(Component $component): array
    {
        $issues = [];

        // Only check image components
        if (!in_array($component->type, ['image', 'icon', 'avatar'])) {
            return $issues;
        }

        $props = $component->properties;

        if (!isset($props['alt']) || empty($props['alt'])) {
            $issues[] = [
                'type' => 'alt_text',
                'wcag_criterion' => '1.1.1',
                'wcag_level' => 'A',
                'severity' => 'critical',
                'title' => 'Missing Alt Text',
                'description' => "Image component is missing alternative text for screen readers.",
                'recommendation' => "Add descriptive alt text that conveys the purpose and content of the image.",
                'details' => [
                    'component_type' => $component->type,
                ],
                'status' => 'open',
            ];
        }

        return $issues;
    }

    /**
     * Check heading hierarchy (WCAG 1.3.1).
     */
    private function checkHeadingHierarchy(Component $component): array
    {
        $issues = [];

        // Only check heading components
        if (!in_array($component->type, ['heading', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
            return $issues;
        }

        // This would require context of the entire page
        // For now, just check if heading level is specified

        $props = $component->properties;

        if (!isset($props['headingLevel'])) {
            $issues[] = [
                'type' => 'heading_hierarchy',
                'wcag_criterion' => '1.3.1',
                'wcag_level' => 'A',
                'severity' => 'medium',
                'title' => 'Heading Level Not Specified',
                'description' => "Heading component should have a specific level (h1-h6) for proper document structure.",
                'recommendation' => "Specify the appropriate heading level based on the document hierarchy.",
                'details' => [
                    'component_type' => $component->type,
                ],
                'status' => 'open',
            ];
        }

        return $issues;
    }

    /**
     * Get accessibility score for a design.
     */
    public function getAccessibilityScore(Design $design): array
    {
        $totalIssues = $design->accessibilityIssues()->count();
        $criticalIssues = $design->accessibilityIssues()->where('severity', 'critical')->count();
        $resolvedIssues = $design->accessibilityIssues()->where('status', 'resolved')->count();

        $score = 100;

        // Deduct points based on severity
        $score -= $criticalIssues * 20;
        $score -= $design->accessibilityIssues()->where('severity', 'high')->count() * 10;
        $score -= $design->accessibilityIssues()->where('severity', 'medium')->count() * 5;
        $score -= $design->accessibilityIssues()->where('severity', 'low')->count() * 2;

        // Add points for resolved issues
        $score += $resolvedIssues * 2;

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'total_issues' => $totalIssues,
            'critical_issues' => $criticalIssues,
            'resolved_issues' => $resolvedIssues,
            'grade' => $this->getGrade($score),
        ];
    }

    /**
     * Get letter grade from score.
     */
    private function getGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }
}
