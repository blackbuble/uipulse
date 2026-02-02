<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DesignVersion;

echo "Testing Snapshot Display Fix...\n\n";

$version = DesignVersion::latest()->first();

if (!$version) {
    echo "No version found\n";
    exit(1);
}

echo "Version: v{$version->version_number}\n";
echo "Name: {$version->version_name}\n\n";

echo "Snapshot Data:\n";
echo "═══════════════════════════════════════════════════════════\n";
if ($version->snapshot) {
    echo json_encode($version->snapshot, JSON_PRETTY_PRINT);
    echo "\n\n";
    echo "✅ Snapshot has " . count($version->snapshot) . " fields\n";
} else {
    echo "❌ No snapshot data\n";
}

echo "\n";

echo "Metadata Snapshot:\n";
echo "═══════════════════════════════════════════════════════════\n";
if ($version->metadata_snapshot) {
    echo json_encode($version->metadata_snapshot, JSON_PRETTY_PRINT);
    echo "\n\n";
    echo "✅ Metadata has " . count($version->metadata_snapshot) . " fields\n";
} else {
    echo "ℹ️  No metadata snapshot\n";
}

echo "\n✅ Data exists in database!\n";
echo "Now check Filament UI - it should display properly.\n";
