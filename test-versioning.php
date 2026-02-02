<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Component;
use App\Models\Design;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       Component Versioning System - Demo                ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Get first component
$component = Component::first();

if (!$component) {
    echo "❌ No components found. Run ComponentSeeder first.\n";
    exit(1);
}

echo "📦 Original Component:\n";
echo "   Name: {$component->name}\n";
echo "   Version: {$component->version}\n";
echo "   Type: {$component->type}\n";
echo "   Properties: " . json_encode($component->properties) . "\n\n";

// Create version 1.1 with property changes
echo "🔄 Creating version 1.1 with updated properties...\n";
$v1_1 = $component->createNewVersion(
    changes: [
        'properties' => array_merge($component->properties, [
            'backgroundColor' => '#10B981', // Changed to green
            'borderRadius' => 12, // Increased radius
        ])
    ],
    changelog: 'Updated background color to green and increased border radius',
    userId: 1
);

echo "   ✓ Version 1.1 created\n";
echo "   ID: {$v1_1->id}\n";
echo "   Version: {$v1_1->version}\n";
echo "   Changelog: {$v1_1->changelog}\n";
echo "   Is Latest: " . ($v1_1->is_latest_version ? 'Yes' : 'No') . "\n\n";

// Create version 1.2 with more changes
echo "🔄 Creating version 1.2 with description update...\n";
$v1_2 = $v1_1->createNewVersion(
    changes: [
        'description' => 'Enhanced primary button with improved accessibility',
        'properties' => array_merge($v1_1->properties, [
            'fontSize' => '18px', // Larger font
        ])
    ],
    changelog: 'Improved accessibility and increased font size',
    userId: 1
);

echo "   ✓ Version 1.2 created\n";
echo "   Version: {$v1_2->version}\n";
echo "   Changelog: {$v1_2->changelog}\n\n";

// Show version history
echo "📜 Version History:\n";
echo "─────────────────────────────────────────────────────────────\n";
$allVersions = $component->getAllVersions();

foreach ($allVersions as $version) {
    $latest = $version->is_latest_version ? ' [LATEST]' : '';
    $created = $version->version_created_at ? $version->version_created_at->format('Y-m-d H:i') : 'N/A';

    echo "   v{$version->version}{$latest}\n";
    echo "   └─ Created: {$created}\n";
    if ($version->changelog) {
        echo "   └─ Changes: {$version->changelog}\n";
    }
    echo "\n";
}

// Test restore functionality
echo "🔙 Restoring version 1.0 as latest...\n";
$restored = $component->restoreAsLatest(
    changelog: 'Reverted to original design based on user feedback',
    userId: 1
);

echo "   ✓ Version {$restored->version} created (restored from 1.0)\n";
echo "   Changelog: {$restored->changelog}\n";
echo "   Is Latest: " . ($restored->is_latest_version ? 'Yes' : 'No') . "\n\n";

// Final version count
$totalVersions = $component->getAllVersions()->count();
echo "📊 Summary:\n";
echo "   Total Versions: {$totalVersions}\n";
echo "   Latest Version: v{$restored->version}\n";
echo "   Original Component ID: {$component->id}\n\n";

echo "✅ Versioning system working perfectly!\n";
