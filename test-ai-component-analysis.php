<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Component;
use App\Services\AiComponentAnalysisService;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       AI Component Analysis - Demo                      ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Get a component to analyze
$component = Component::first();

if (!$component) {
    echo "❌ No components found. Please create a component first.\n";
    exit(1);
}

echo "📦 Component: {$component->name}\n";
echo "   Type: {$component->type}\n";
echo "   Category: {$component->category}\n";
echo "   Properties: " . count($component->properties ?? []) . " fields\n\n";

// Check if API keys are configured
$openaiKey = config('services.ai.providers.openai.key');
$deepseekKey = config('services.ai.providers.deepseek.key');

if (!$openaiKey && !$deepseekKey) {
    echo "⚠️  No AI API keys configured!\n";
    echo "   Please add OPENAI_API_KEY or DEEPSEEK_API_KEY to .env\n\n";
    echo "   Example:\n";
    echo "   OPENAI_API_KEY=sk-...\n";
    echo "   or\n";
    echo "   DEEPSEEK_API_KEY=sk-...\n\n";
    exit(1);
}

$provider = $deepseekKey ? 'deepseek' : 'openai';
echo "🤖 Using AI Provider: " . strtoupper($provider) . "\n";
echo "   Model: " . config("services.ai.providers.{$provider}.model") . "\n\n";

$service = new AiComponentAnalysisService($provider);

// Test 1: Quality Analysis
echo "Test 1: Quality Analysis\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    echo "🔄 Analyzing component quality...\n";
    $qualityAnalysis = $service->analyzeQuality($component);

    echo "   ✓ Analysis complete!\n";
    echo "   Score: " . ($qualityAnalysis['score'] ?? 'N/A') . "/10\n";
    echo "   Summary: " . ($qualityAnalysis['summary'] ?? 'No summary') . "\n";

    if (isset($qualityAnalysis['recommendations'])) {
        echo "   Recommendations: " . count($qualityAnalysis['recommendations']) . "\n";
    }

    echo "\n";

} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Accessibility Analysis
echo "Test 2: Accessibility Analysis\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    echo "🔄 Analyzing accessibility...\n";
    $accessibilityAnalysis = $service->analyzeAccessibility($component);

    echo "   ✓ Analysis complete!\n";
    echo "   WCAG Level: " . ($accessibilityAnalysis['wcag_level'] ?? 'N/A') . "\n";
    echo "   Compliance Score: " . ($accessibilityAnalysis['compliance_score'] ?? 'N/A') . "%\n";

    if (isset($accessibilityAnalysis['issues'])) {
        echo "   Issues Found: " . count($accessibilityAnalysis['issues']) . "\n";
    }

    echo "\n";

} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Best Practices
echo "Test 3: Best Practices Analysis\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    echo "🔄 Analyzing best practices...\n";
    $bestPractices = $service->analyzeBestPractices($component);

    echo "   ✓ Analysis complete!\n";
    echo "   Overall Score: " . ($bestPractices['overall_score'] ?? 'N/A') . "/10\n";
    echo "   Summary: " . ($bestPractices['summary'] ?? 'No summary') . "\n";

    echo "\n";

} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// Show all analyses for this component
echo "📊 Analysis History\n";
echo "───────────────────────────────────────────────────────────\n";

$analyses = $component->aiAnalyses;

if ($analyses->count() > 0) {
    echo "Total Analyses: {$analyses->count()}\n\n";

    foreach ($analyses as $analysis) {
        $status = $analysis->status === 'completed' ? '✓' : '❌';
        echo "{$status} {$analysis->type}\n";
        echo "   Provider: {$analysis->provider}\n";
        echo "   Status: {$analysis->status}\n";
        echo "   Created: {$analysis->created_at->diffForHumans()}\n";

        if ($analysis->status === 'completed' && isset($analysis->results['score'])) {
            echo "   Score: {$analysis->results['score']}/10\n";
        }

        echo "\n";
    }
} else {
    echo "No analyses found for this component.\n\n";
}

echo "✅ AI Component Analysis Demo Complete!\n";
echo "\n";
echo "Next Steps:\n";
echo "1. Check Filament UI for AI analysis actions\n";
echo "2. View detailed results in the admin panel\n";
echo "3. Use insights to improve components\n";
