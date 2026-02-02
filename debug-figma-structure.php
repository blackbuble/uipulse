<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = config('services.figma.token');
$fileKey = 'TaotTFQAgl1kzMRtRjLQNd';

echo "Debugging Figma file structure...\n\n";

$response = \Illuminate\Support\Facades\Http::withHeaders([
    'X-Figma-Token' => $token
])->timeout(30)
    ->get("https://api.figma.com/v1/files/{$fileKey}", [
        'depth' => 4,
        'geometry' => 'paths',
    ]);

if (!$response->successful()) {
    echo "Error: " . json_encode($response->json()) . "\n";
    exit(1);
}

$data = $response->json();

echo "File: " . $data['name'] . "\n";
echo "Pages: " . count($data['document']['children']) . "\n\n";

foreach ($data['document']['children'] as $pageIndex => $page) {
    echo "Page #{$pageIndex}: {$page['name']}\n";
    echo "  Type: {$page['type']}\n";
    echo "  Has children: " . (isset($page['children']) ? 'YES' : 'NO') . "\n";

    if (isset($page['children'])) {
        echo "  Children count: " . count($page['children']) . "\n";

        // Show first 3 children
        foreach (array_slice($page['children'], 0, 3) as $childIndex => $child) {
            echo "    Child #{$childIndex}: {$child['name']} (type: {$child['type']})\n";

            if (isset($child['children'])) {
                echo "      Has " . count($child['children']) . " sub-children\n";
            }
        }

        if (count($page['children']) > 3) {
            echo "    ... and " . (count($page['children']) - 3) . " more\n";
        }
    }

    echo "\n";
}
