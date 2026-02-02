<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$design = \App\Models\Design::find(46);

echo "Before:\n";
echo "  File Key: {$design->figma_file_key}\n";
echo "  Node ID: {$design->figma_node_id}\n\n";

$design->figma_file_key = 'TaotTFQAgl1kzMRtRjLQNd';
$design->figma_url = 'https://www.figma.com/design/TaotTFQAgl1kzMRtRjLQNd/alumnisbipb';
$design->figma_node_id = null;
$design->save();

echo "After:\n";
echo "  File Key: {$design->figma_file_key}\n";
echo "  Node ID: " . ($design->figma_node_id ?? 'null') . "\n";
echo "  URL: {$design->figma_url}\n\n";

echo "✓ Design updated successfully!\n";
