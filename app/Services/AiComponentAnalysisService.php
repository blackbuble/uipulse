<?php

namespace App\Services;

use App\Models\AiAnalysis;
use App\Models\Component;
use App\Settings\AiSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiComponentAnalysisService
{
  protected string $provider;
  protected string $apiUrl;
  protected string $apiKey;
  protected string $model;
  protected AiSettings $settings;

  public function __construct(?string $provider = null)
  {
    $this->settings = app(AiSettings::class);
    $this->provider = $provider ?? $this->settings->default_provider;

    // Find provider config from settings
    $providerConfig = collect($this->settings->providers)
      ->firstWhere('id', $this->provider);

    if (!$providerConfig) {
      throw new \Exception("AI provider '{$this->provider}' not found in settings. Please configure providers in AI Settings.");
    }

    $this->apiUrl = $providerConfig['url'];
    $this->apiKey = $providerConfig['key'];
    $this->model = $providerConfig['model'];
  }

  /**
   * Analyze component with specified analysis type.
   */
  public function analyzeComponent(
    Component $component,
    string $analysisType,
    ?string $provider = null
  ): AiAnalysis {
    if ($provider) {
      $this->switchProvider($provider);
    }

    $prompt = $this->buildPrompt($component, $analysisType);

    try {
      $response = $this->callAiApi($prompt);
      $results = $this->parseResponse($response, $analysisType);

      return AiAnalysis::create([
        'component_id' => $component->id,
        'design_id' => $component->design_id,
        'type' => $analysisType,
        'provider' => $this->provider,
        'model_name' => $this->model,
        'status' => 'completed',
        'results' => $results,
        'prompt' => $prompt,
      ]);

    } catch (\Exception $e) {
      Log::error('AI Component Analysis failed', [
        'component_id' => $component->id,
        'type' => $analysisType,
        'error' => $e->getMessage(),
      ]);

      return AiAnalysis::create([
        'component_id' => $component->id,
        'design_id' => $component->design_id,
        'type' => $analysisType,
        'provider' => $this->provider,
        'model_name' => $this->model,
        'status' => 'failed',
        'results' => ['error' => $e->getMessage()],
        'prompt' => $prompt,
      ]);
    }
  }

  /**
   * Analyze component quality.
   */
  public function analyzeQuality(Component $component): array
  {
    $analysis = $this->analyzeComponent($component, 'component_quality');
    return $analysis->results;
  }

  /**
   * Analyze component accessibility.
   */
  public function analyzeAccessibility(Component $component): array
  {
    $analysis = $this->analyzeComponent($component, 'component_accessibility');
    return $analysis->results;
  }

  /**
   * Analyze component best practices.
   */
  public function analyzeBestPractices(Component $component): array
  {
    $analysis = $this->analyzeComponent($component, 'component_best_practices');
    return $analysis->results;
  }

  /**
   * Suggest improvements for component.
   */
  public function suggestImprovements(Component $component): array
  {
    $analysis = $this->analyzeComponent($component, 'component_improvements');
    return $analysis->results;
  }

  /**
   * Generate code recommendations.
   */
  public function generateCodeRecommendations(Component $component): array
  {
    $analysis = $this->analyzeComponent($component, 'component_code_gen');
    return $analysis->results;
  }

  /**
   * Build prompt based on analysis type.
   */
  protected function buildPrompt(Component $component, string $analysisType): string
  {
    $baseInfo = $this->getComponentInfo($component);

    return match ($analysisType) {
      'component_quality' => $this->buildQualityPrompt($baseInfo),
      'component_accessibility' => $this->buildAccessibilityPrompt($baseInfo),
      'component_best_practices' => $this->buildBestPracticesPrompt($baseInfo),
      'component_improvements' => $this->buildImprovementsPrompt($baseInfo),
      'component_code_gen' => $this->buildCodeGenPrompt($baseInfo),
      default => throw new \InvalidArgumentException("Unknown analysis type: {$analysisType}"),
    };
  }

  /**
   * Get component information for prompts.
   */
  protected function getComponentInfo(Component $component): array
  {
    return [
      'name' => $component->name,
      'type' => $component->type,
      'category' => $component->category,
      'description' => $component->description,
      'properties' => $component->properties,
      'usage_count' => $component->usage_count,
      'is_in_library' => $component->is_in_library,
      'variants_count' => $component->variants()->count(),
    ];
  }

  /**
   * Build quality assessment prompt.
   */
  protected function buildQualityPrompt(array $info): string
  {
    $properties = json_encode($info['properties'], JSON_PRETTY_PRINT);

    return <<<PROMPT
Analyze this UI component for quality and consistency. Provide a detailed assessment.

Component Details:
- Name: {$info['name']}
- Type: {$info['type']}
- Category: {$info['category']}
- Description: {$info['description']}
- Properties: {$properties}
- Usage Count: {$info['usage_count']}
- In Library: {$info['is_in_library']}
- Variants: {$info['variants_count']}

Please provide a comprehensive quality assessment with:

1. **Overall Quality Score** (1-10): Rate the overall quality
2. **Naming Convention**: Evaluate naming clarity and consistency
3. **Property Structure**: Assess property organization and completeness
4. **Consistency**: Check alignment with design system standards
5. **Reusability**: Evaluate component reusability potential
6. **Documentation**: Assess description quality

Return response as JSON with this structure:
{
  "score": 8.5,
  "summary": "Brief overall assessment",
  "details": {
    "naming": {"score": 9, "feedback": "Clear and descriptive"},
    "properties": {"score": 8, "feedback": "Well-structured"},
    "consistency": {"score": 8, "feedback": "Aligns with standards"},
    "reusability": {"score": 9, "feedback": "Highly reusable"},
    "documentation": {"score": 7, "feedback": "Could be more detailed"}
  },
  "recommendations": [
    {"priority": "high", "category": "properties", "suggestion": "Add validation rules"},
    {"priority": "medium", "category": "documentation", "suggestion": "Add usage examples"}
  ],
  "action_items": [
    "Add property constraints",
    "Improve documentation",
    "Add usage examples"
  ]
}
PROMPT;
  }

  /**
   * Build accessibility audit prompt.
   */
  protected function buildAccessibilityPrompt(array $info): string
  {
    $properties = json_encode($info['properties'], JSON_PRETTY_PRINT);

    return <<<PROMPT
Perform a comprehensive accessibility audit for this UI component based on WCAG 2.1 guidelines.

Component: {$info['name']}
Type: {$info['type']}
Properties: {$properties}

Analyze for:
1. **Color Contrast**: If colors are defined, check contrast ratios
2. **Text Sizing**: Evaluate font sizes for readability
3. **Interactive Elements**: Check for proper sizing and spacing
4. **ARIA Attributes**: Identify needed ARIA labels and roles
5. **Keyboard Navigation**: Assess keyboard accessibility
6. **Screen Reader**: Evaluate screen reader compatibility
7. **Focus Indicators**: Check for visible focus states

Return JSON response:
{
  "wcag_level": "AA",
  "compliance_score": 85,
  "summary": "Generally accessible with some improvements needed",
  "issues": [
    {
      "severity": "high",
      "category": "color_contrast",
      "issue": "Text color may not meet contrast requirements",
      "recommendation": "Use darker text color or lighter background",
      "wcag_criterion": "1.4.3"
    }
  ],
  "recommendations": [
    "Add aria-label for icon buttons",
    "Ensure minimum touch target size of 44x44px",
    "Add visible focus indicator"
  ],
  "action_items": [
    "Review color contrast ratios",
    "Add ARIA attributes",
    "Test with screen reader"
  ]
}
PROMPT;
  }

  /**
   * Build best practices prompt.
   */
  protected function buildBestPracticesPrompt(array $info): string
  {
    $properties = json_encode($info['properties'], JSON_PRETTY_PRINT);

    return <<<PROMPT
Evaluate this UI component against modern UI/UX best practices and design system standards.

Component: {$info['name']}
Type: {$info['type']}
Category: {$info['category']}
Properties: {$properties}

Assess:
1. **Design System Compliance**: Alignment with design system principles
2. **Responsive Design**: Mobile-first and responsive considerations
3. **State Management**: Proper handling of different states (hover, active, disabled, etc.)
4. **Performance**: Potential performance implications
5. **Maintainability**: Code organization and reusability
6. **Scalability**: Ability to handle different use cases

Return JSON:
{
  "overall_score": 8,
  "summary": "Follows most best practices with room for improvement",
  "assessments": {
    "design_system": {"score": 9, "feedback": "Excellent alignment"},
    "responsive": {"score": 7, "feedback": "Consider mobile breakpoints"},
    "states": {"score": 8, "feedback": "Most states covered"},
    "performance": {"score": 9, "feedback": "Lightweight and efficient"},
    "maintainability": {"score": 8, "feedback": "Well organized"},
    "scalability": {"score": 7, "feedback": "Could support more variants"}
  },
  "recommendations": [
    "Add responsive property variants",
    "Document all component states",
    "Consider dark mode support"
  ],
  "action_items": [
    "Add mobile-specific properties",
    "Create state documentation",
    "Test in different themes"
  ]
}
PROMPT;
  }

  /**
   * Build improvements prompt.
   */
  protected function buildImprovementsPrompt(array $info): string
  {
    $properties = json_encode($info['properties'], JSON_PRETTY_PRINT);

    return <<<PROMPT
Suggest specific improvements for this UI component to enhance its quality, usability, and maintainability.

Component: {$info['name']}
Type: {$info['type']}
Properties: {$properties}
Usage Count: {$info['usage_count']}

Provide actionable improvement suggestions in these areas:
1. **Properties**: Missing or redundant properties
2. **Variants**: Additional variants that would be useful
3. **Accessibility**: Accessibility enhancements
4. **Performance**: Performance optimizations
5. **Documentation**: Documentation improvements
6. **Flexibility**: Ways to make it more flexible/reusable

Return JSON:
{
  "priority_improvements": [
    {
      "priority": "high",
      "category": "properties",
      "title": "Add size variants",
      "description": "Add small, medium, large size options",
      "impact": "Increases flexibility and reusability",
      "effort": "low"
    }
  ],
  "quick_wins": [
    "Add hover state documentation",
    "Include usage examples in description"
  ],
  "long_term": [
    "Create interactive documentation",
    "Build variant generator tool"
  ],
  "action_items": [
    "Add size property with sm/md/lg options",
    "Document all available states",
    "Create usage examples"
  ]
}
PROMPT;
  }

  /**
   * Build code generation prompt.
   */
  protected function buildCodeGenPrompt(array $info): string
  {
    $properties = json_encode($info['properties'], JSON_PRETTY_PRINT);

    return <<<PROMPT
Generate code implementation recommendations for this UI component in React/Vue/HTML.

Component: {$info['name']}
Type: {$info['type']}
Properties: {$properties}

Provide:
1. **React Implementation**: Modern React component code
2. **Props Interface**: TypeScript interface for props
3. **Styling Approach**: CSS/Tailwind recommendations
4. **Usage Examples**: Code examples showing different use cases
5. **Best Practices**: Implementation best practices

Return JSON:
{
  "react_code": "import React from 'react'...",
  "typescript_interface": "interface ButtonProps {...}",
  "styling": {
    "approach": "CSS Modules",
    "code": ".button { ... }"
  },
  "usage_examples": [
    {
      "title": "Primary Button",
      "code": "<Button variant='primary'>Click me</Button>"
    }
  ],
  "recommendations": [
    "Use forwardRef for ref forwarding",
    "Implement proper TypeScript types",
    "Add loading state support"
  ]
}
PROMPT;
  }

  /**
   * Call AI API.
   */
  protected function callAiApi(string $prompt): array
  {
    $response = Http::withHeaders([
      'Authorization' => "Bearer {$this->apiKey}",
      'Content-Type' => 'application/json',
    ])->timeout(60)->post("{$this->apiUrl}/chat/completions", [
          'model' => $this->model,
          'messages' => [
            [
              'role' => 'system',
              'content' => 'You are an expert UI/UX designer and accessibility specialist. Provide detailed, actionable analysis in valid JSON format.'
            ],
            [
              'role' => 'user',
              'content' => $prompt
            ]
          ],
          'temperature' => 0.7,
          'response_format' => ['type' => 'json_object'],
        ]);

    if (!$response->successful()) {
      throw new \Exception("AI API request failed: " . $response->body());
    }

    return $response->json();
  }

  /**
   * Parse AI response.
   */
  protected function parseResponse(array $response, string $analysisType): array
  {
    $content = $response['choices'][0]['message']['content'] ?? '';

    try {
      $parsed = json_decode($content, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('Invalid JSON response from AI');
      }

      return $parsed;

    } catch (\Exception $e) {
      Log::warning('Failed to parse AI response', [
        'type' => $analysisType,
        'content' => $content,
        'error' => $e->getMessage(),
      ]);

      return [
        'error' => 'Failed to parse AI response',
        'raw_content' => $content,
      ];
    }
  }

  /**
   * Switch AI provider.
   */
  protected function switchProvider(string $provider): void
  {
    $this->provider = $provider;
    $config = config("services.ai.providers.{$provider}");

    $this->apiUrl = $config['url'];
    $this->apiKey = $config['key'];
    $this->model = $config['model'];
  }
}
