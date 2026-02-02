<?php

namespace App\Services;

use App\Models\Design;
use App\Models\AiAnalysis;
use App\Settings\AiSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    public function analyzeDesign(Design $design, string $type = 'accessibility', ?string $providerId = null, array $options = []): AiAnalysis
    {
        $settings = app(AiSettings::class);
        $providerId = $providerId ?? $settings->default_provider;

        $provider = collect($settings->providers)->firstWhere('id', $providerId);

        if (!$provider) {
            throw new \Exception("AI Provider [{$providerId}] not found in settings.");
        }

        $prompt = $this->getDetailedPrompt($type, $design, $options);

        Log::info("Starting AI analysis for design: {$design->name}", [
            'type' => $type,
            'provider' => $provider['id'],
            'model' => $provider['model'],
            'has_image' => !empty($design->image_data),
        ]);

        if ($provider['id'] === 'gemini') {
            return $this->analyzeWithGemini($design, $type, $provider, $prompt);
        }

        // Default to OpenAI-compatible driver
        return $this->analyzeWithOpenAi($design, $type, $provider, $prompt);
    }

    protected function analyzeWithOpenAi(Design $design, string $type, array $provider, string $prompt): AiAnalysis
    {
        if ($provider['supports_vision'] && !empty($design->image_data)) {
            $content = [['type' => 'text', 'text' => $prompt]];
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => str_starts_with($design->image_data, 'data:')
                        ? $design->image_data
                        : "data:image/png;base64,{$design->image_data}"
                ]
            ];
            $userMessage = ['role' => 'user', 'content' => $content];
        } else {
            $userMessage = ['role' => 'user', 'content' => $prompt];
        }

        $messages = [
            ['role' => 'system', 'content' => $this->getSystemPrompt()],
            $userMessage,
        ];

        $response = Http::withToken($provider['key'])
            ->timeout(60)
            ->post($provider['url'] . '/chat/completions', [
                'model' => $provider['model'],
                'messages' => $messages,
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.7,
                'stream' => false,
            ]);

        if ($response->successful()) {
            return $this->processAndSaveAnalysis($design, $type, $provider, $prompt, $response->json('choices.0.message.content'));
        }

        throw new \Exception("AI Analysis ({$provider['id']}) failed: " . $response->json('error.message', $response->body()));
    }

    protected function analyzeWithGemini(Design $design, string $type, array $provider, string $prompt): AiAnalysis
    {
        // Google Gemini API Structure
        // Endpoint: https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={API_KEY}

        $contents = [
            'parts' => [
                ['text' => $this->getSystemPrompt() . "\n\n" . $prompt]
            ]
        ];

        // Handle Image for Gemini Vision
        if (!empty($design->image_data)) {
            // Ensure base64 is clean
            $imageData = $design->image_data;
            if (str_starts_with($imageData, 'data:image')) {
                $imageData = explode(',', $imageData)[1];
            }

            $contents['parts'][] = [
                'inline_data' => [
                    'mime_type' => 'image/png', // Assuming PNG for now from Figma
                    'data' => $imageData
                ]
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$provider['model']}:generateContent?key={$provider['key']}";

        $response = Http::timeout(60)
            ->post($url, [
                'contents' => [$contents],
                'generationConfig' => [
                    'response_mime_type' => 'application/json'
                ]
            ]);

        if ($response->successful()) {
            $content = $response->json('candidates.0.content.parts.0.text');
            return $this->processAndSaveAnalysis($design, $type, $provider, $prompt, $content);
        }

        throw new \Exception("Gemini Analysis Failed: " . $response->body());
    }

    protected function processAndSaveAnalysis(Design $design, string $type, array $provider, string $prompt, ?string $content): AiAnalysis
    {
        Log::info("AI Response received successfully", [
            'content_preview' => is_string($content) ? substr($content, 0, 100) . '...' : 'non-string content'
        ]);

        $decodedResults = $this->cleanJsonResponse($content);

        return AiAnalysis::create([
            'design_id' => $design->id,
            'type' => $type,
            'provider' => $provider['id'],
            'model_name' => $provider['model'],
            'status' => 'completed',
            'results' => $decodedResults,
            'prompt' => $prompt,
        ]);
    }


    public function generateImageSuggestion(Design $design, string $prompt, ?string $providerId = null): AiAnalysis
    {
        $settings = app(AiSettings::class);
        $providerId = $providerId ?? $settings->default_provider;
        $provider = collect($settings->providers)->firstWhere('id', $providerId);

        if (!$provider || empty($provider['supports_generation'])) {
            throw new \Exception("The selected provider ({$providerId}) does not support image generation.");
        }

        Log::info("Generating visual mockup for design: {$design->name}", [
            'provider' => $providerId,
        ]);

        if ($provider['id'] === 'gemini') {
            return $this->generateImageWithGemini($design, $prompt, $provider);
        }

        return $this->generateImageWithOpenAi($design, $prompt, $provider);
    }

    protected function generateImageWithOpenAi(Design $design, string $prompt, array $provider): AiAnalysis
    {
        $baseUrl = rtrim($provider['url'], '/');

        $response = Http::withToken($provider['key'])
            ->timeout(60)
            ->post("{$baseUrl}/images/generations", [
                'model' => 'dall-e-3',
                'prompt' => "Create a high-fidelity UI mockup fix for the following issue: {$prompt}. Existing design context: {$design->name}. Use modern aesthetics, clean typography, and a premium UX feel.",
                'n' => 1,
                'size' => '1024x1024',
                'response_format' => 'url',
            ]);

        if ($response->successful()) {
            return AiAnalysis::create([
                'design_id' => $design->id,
                'type' => 'visual_mockup',
                'provider' => $provider['id'],
                'model_name' => 'dall-e-3',
                'status' => 'completed',
                'results' => [
                    'generated_image_url' => $response->json('data.0.url'),
                    'summary' => "Visual mockup generated to address: {$prompt}",
                    'score' => 100,
                ],
                'prompt' => $prompt,
            ]);
        }

        throw new \Exception("Image Generation failed: " . $response->json('error.message', $response->body()));
    }

    protected function generateImageWithGemini(Design $design, string $prompt, array $provider): AiAnalysis
    {
        // Google Imagen 3 API via Gemini
        // Endpoint: https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-001:predict?key={API_KEY}

        $url = "https://generativelanguage.googleapis.com/v1beta/models/imagen-4.0-generate-001:predict?key={$provider['key']}";

        $finalPrompt = "Create a high-fidelity UI mockup fix for the following issue: {$prompt}. Existing design context: {$design->name}. Use modern aesthetics, clean typography, and a premium UX feel.";

        $response = Http::timeout(60)
            ->post($url, [
                'instances' => [
                    ['prompt' => $finalPrompt]
                ],
                'parameters' => [
                    'sampleCount' => 1,
                    // 'aspectRatio' => '1:1',
                ]
            ]);

        if ($response->successful()) {
            // Imagen returns base64 string
            $base64Image = $response->json('predictions.0.bytesBase64Encoded');

            if (!$base64Image) {
                throw new \Exception("Gemini Image Generation succeeded but returned no image data.");
            }

            // In a real app, we would upload this to storage.
            // For now, we'll use a data URI, though it might be large for the DB.
            // Ideally, we'd handle storage upload here.
            $imageUrl = "data:image/png;base64,{$base64Image}";

            return AiAnalysis::create([
                'design_id' => $design->id,
                'type' => 'visual_mockup',
                'provider' => $provider['id'],
                'model_name' => 'imagen-3.0',
                'status' => 'completed',
                'results' => [
                    'generated_image_url' => $imageUrl,
                    'summary' => "Visual mockup generated (Imagen 3) to address: {$prompt}",
                    'score' => 100,
                ],
                'prompt' => $prompt,
            ]);
        }

        throw new \Exception("Gemini Image Generation Failed: " . $response->body());
    }

    protected function getSystemPrompt(): string
    {
        return <<<PROMPT
        You are a World-Class UI/UX Design Auditor. 
        Your goal is to provide deep, actionable insights that WOW the user.
        Always return a valid JSON object with the following structure:
        {
            "score": number (0-100),
            "summary": "concise overview",
            "findings": [
                {"severity": "high|medium|low", "issue": "desc", "recommendation": "fix"}
            ],
            "wow_factor": "a unique creative suggestion"
        }
        PROMPT;
    }

    protected function getDetailedPrompt(string $type, Design $design, array $options): string
    {
        $depth = $options['depth'] ?? 'standard';
        $focusAreas = $options['focus_areas'] ?? [];
        $customContext = $options['custom_context'] ?? '';

        $basePrompt = $this->getPromptForType($type, $design);

        $detailedInstructions = "";

        if ($depth === 'deep_dive') {
            $detailedInstructions .= "\n- PERFORM A DEEP DIVE: Be extremely critical and exhaustive. Don't overlook micro-interactions or subtle visual inconsistencies.";
        }

        if (!empty($focusAreas)) {
            $areas = implode(', ', $focusAreas);
            $detailedInstructions .= "\n- TARGETED FOCUS: Pay special attention to: {$areas}.";
        }

        if (!empty($customContext)) {
            $detailedInstructions .= "\n- USER CONTEXT: The user added these specific instructions: \"{$customContext}\"";
        }

        return "{$basePrompt}\n\nAdditional Requirements:{$detailedInstructions}";
    }

    protected function getPromptForType(string $type, Design $design): string
    {
        $metadata = json_encode($design->metadata);

        return match ($type) {
            'accessibility' => "Analyze this component for accessibility (WCAG 2.2). Focus on contrast, hit targets, and screen reader info. Design: {$design->name}. Metadata: {$metadata}",
            'responsiveness' => "Audit how this design scales. Look for structural risks in mobile/tablet views. Design: {$design->name}. Metadata: {$metadata}",
            'visual_polish' => "Evaluate the visual aesthetics, spacing, and typography consistency. Design: {$design->name}. Metadata: {$metadata}",
            'code_gen' => "Generate modern Tailwind CSS + React code for this component. Focus on clean code. Design: {$design->name}. Metadata: {$metadata}",
            default => "Analyze this UI design: {$design->name}. Metadata: {$metadata}",
        };
    }

    private function cleanJsonResponse(?string $content): array
    {
        if (empty($content)) {
            return [];
        }

        // Remove markdown code blocks if present (e.g. ```json ... ```)
        $cleanContent = preg_replace('/^```json\s*|```$/m', '', trim($content));

        $decoded = json_decode($cleanContent, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->validateAndFlattenFindings($decoded);
        }

        // Fallback: search for first { and last } if it's still not valid JSON
        if (preg_match('/\{.*\}/s', $cleanContent, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->validateAndFlattenFindings($decoded);
            }
        }

        return ['raw_content' => $content];
    }

    private function validateAndFlattenFindings(array $data): array
    {
        if (isset($data['findings']) && is_array($data['findings'])) {
            foreach ($data['findings'] as &$finding) {
                if (is_array($finding)) {
                    foreach (['severity', 'issue', 'recommendation'] as $key) {
                        if (isset($finding[$key]) && !is_string($finding[$key])) {
                            // If it's an object/array, flatten it to string
                            $finding[$key] = json_encode($finding[$key]);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Analyze Figma nodes to detect UI components.
     */
    public function analyzeComponents(array $nodes): array
    {
        $settings = app(AiSettings::class);
        $provider = collect($settings->providers)->firstWhere('id', $settings->default_provider);

        if (!$provider) {
            throw new \Exception("No AI provider configured");
        }

        $prompt = $this->getComponentDetectionPrompt($nodes);

        Log::info("Starting component detection with AI", [
            'provider' => $provider['id'],
            'node_count' => count($nodes),
        ]);

        if ($provider['id'] === 'gemini') {
            return $this->detectComponentsWithGemini($nodes, $provider, $prompt);
        }

        return $this->detectComponentsWithOpenAi($nodes, $provider, $prompt);
    }

    /**
     * Get prompt for component detection.
     */
    private function getComponentDetectionPrompt(array $nodes): string
    {
        // Minimal summary to stay within token limits
        $summarizedNodes = array_map(function ($nodeData) {
            $children = array_map(function ($child) {
                return $child['name'] ?? 'Unnamed';
            }, array_slice($nodeData['children'] ?? [], 0, 5)); // Only first 5 child names

            return [
                'page' => $nodeData['page'] ?? 'Unknown',
                'frame' => $nodeData['frame'] ?? 'Unknown',
                'type' => $nodeData['node']['type'] ?? 'FRAME',
                'children' => $children,
                'children_count' => count($nodeData['children'] ?? []),
            ];
        }, $nodes);

        $nodesJson = json_encode($summarizedNodes, JSON_PRETTY_PRINT);

        return <<<PROMPT
        Analyze these Figma frames and identify common UI components based on naming patterns.
        
        For each component type you identify, provide:
        - **type**: Component type (button, input, card, navigation, form, etc.)
        - **name**: Descriptive name based on the frame/element names
        - **description**: Brief description
        - **properties**: Basic properties object (can be minimal)
        
        Figma Frames:
        {$nodesJson}
        
        Return JSON:
        {
          "components": [
            {"type": "button", "name": "Primary Button", "description": "Main CTA", "properties": {}}
          ]
        }
        
        Identify 3-10 common component types based on the naming patterns.
        PROMPT;
    }

    /**
     * Detect components using Gemini.
     */
    private function detectComponentsWithGemini(array $nodes, array $provider, string $prompt): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$provider['model']}:generateContent?key={$provider['key']}";

        $response = Http::timeout(60)
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json'
                ]
            ]);

        if ($response->successful()) {
            $content = $response->json('candidates.0.content.parts.0.text');
            $components = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($components)) {
                return $components;
            }
        }

        Log::error("Component detection with Gemini failed", [
            'response' => $response->body()
        ]);

        return [];
    }

    /**
     * Detect components using OpenAI.
     */
    private function detectComponentsWithOpenAi(array $nodes, array $provider, string $prompt): array
    {
        $response = Http::withToken($provider['key'])
            ->timeout(60)
            ->post($provider['url'] . '/chat/completions', [
                'model' => $provider['model'],
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert UI component analyzer. Return only valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.3,
            ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');
            $data = json_decode($content, true);

            Log::info("Component detection with OpenAI completed", [
                'components_found' => count($data['components'] ?? []),
            ]);

            return $data['components'] ?? [];
        }

        Log::error("Component detection with OpenAI failed", [
            'response' => $response->body()
        ]);

        return [];
    }
}
