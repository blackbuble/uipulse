<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = config('services.figma.token');
$fileKey = 'TaotTFQAgl1kzMRtRjLQNd';

echo "Testing Figma API with query parameters...\n";
echo "Token: " . ($token ? substr($token, 0, 10) . '...' : 'NOT SET') . "\n";
echo "File Key: $fileKey\n\n";

// Test with depth and geometry parameters
echo "Testing with depth=2 and geometry=paths...\n";
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'X-Figma-Token' => $token
])->timeout(30)
    ->get("https://api.figma.com/v1/files/{$fileKey}", [
        'depth' => 2,
        'geometry' => 'paths',
    ]);

echo "Status Code: " . $response->status() . "\n";
echo "Successful: " . ($response->successful() ? 'YES ✓' : 'NO ✗') . "\n\n";

if (!$response->successful()) {
    echo "Error Details:\n";
    echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
} else {
    $data = $response->json();
    echo "File Name: " . ($data['name'] ?? 'Unknown') . "\n";
    echo "Pages: " . count($data['document']['children'] ?? []) . "\n";

    if (isset($data['document']['children'])) {
        foreach ($data['document']['children'] as $page) {
            echo "  - " . $page['name'] . " (" . count($page['children'] ?? []) . " frames)\n";
        }
    }

    echo "\nResponse size: " . strlen(json_encode($data)) . " bytes\n";
}