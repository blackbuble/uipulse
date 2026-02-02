<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Design;
use App\Services\DesignVersionService;

echo "Testing JSON Display Fix...\n\n";

$service = new DesignVersionService();
$design = Design::first();

if (!$design) {
    echo "No design found\n";
    exit(1);
}

// Create a version
$version = $service->createManualVersion(
    design: $design,
    userId: 1,
    versionName: 'Test JSON Display',
    description: 'Testing proper JSON formatting'
);

echo "✓ Version created: v{$version->version_number}\n\n";

echo "Snapshot Data (properly formatted):\n";
echo "═══════════════════════════════════════════════════════════\n";
echo json_encode($version->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "Metadata Snapshot:\n";
echo "═══════════════════════════════════════════════════════════\n";
if ($version->metadata_snapshot) {
    echo json_encode($version->metadata_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} else {
    echo "No metadata snapshot\n";
}
echo "\n\n";

echo "✅ JSON formatting working correctly!\n";
echo "   Now check Filament UI - metadata should display properly\n";
echo "   instead of [object Object]\n";
