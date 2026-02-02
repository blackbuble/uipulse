<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Settings\AiSettings;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       AI Settings - Configuration Check                 ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

try {
    $settings = app(AiSettings::class);

    echo "✓ AI Settings loaded successfully!\n\n";

    echo "Default Provider: " . ($settings->default_provider ?? 'Not set') . "\n\n";

    echo "Configured Providers:\n";
    echo "═══════════════════════════════════════════════════════════\n";

    if (empty($settings->providers)) {
        echo "⚠️  No providers configured!\n\n";
        echo "Please configure AI providers in Filament:\n";
        echo "1. Go to Settings → AI Settings\n";
        echo "2. Add providers (OpenAI, DeepSeek, etc.)\n";
        echo "3. Set default provider\n\n";
    } else {
        foreach ($settings->providers as $provider) {
            $hasKey = !empty($provider['key']);
            $status = $hasKey ? '✓' : '❌';

            echo "{$status} {$provider['name']}\n";
            echo "   ID: {$provider['id']}\n";
            echo "   URL: {$provider['url']}\n";
            echo "   Model: {$provider['model']}\n";
            echo "   API Key: " . ($hasKey ? 'Configured (' . substr($provider['key'], 0, 10) . '...)' : 'Not set') . "\n";

            if (isset($provider['supports_vision'])) {
                echo "   Vision Support: " . ($provider['supports_vision'] ? 'Yes' : 'No') . "\n";
            }

            echo "\n";
        }
    }

    // Test service initialization
    echo "Testing Service Initialization:\n";
    echo "═══════════════════════════════════════════════════════════\n";

    try {
        $service = new \App\Services\AiComponentAnalysisService();
        echo "✓ Service initialized successfully\n";
        echo "  Using provider: {$settings->default_provider}\n\n";
    } catch (\Exception $e) {
        echo "❌ Service initialization failed:\n";
        echo "   {$e->getMessage()}\n\n";
    }

    echo "✅ Configuration check complete!\n";

} catch (\Exception $e) {
    echo "❌ Error loading AI Settings:\n";
    echo "   {$e->getMessage()}\n\n";
    echo "This might mean settings haven't been initialized yet.\n";
    echo "Please configure AI Settings in Filament admin panel.\n";
}
