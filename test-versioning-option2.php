<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Component;
use App\Models\ComponentVersion;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Component Versioning Option 2 - Advanced Demo          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Get a component
$component = Component::where('name', 'Primary Button')->first();

if (!$component) {
    echo "❌ Primary Button not found\n";
    exit(1);
}

echo "📦 Component: {$component->name}\n";
echo "   Type: {$component->type}\n";
echo "   Current properties: " . json_encode($component->properties) . "\n\n";

// Create initial version (1.0)
echo "🎬 Creating initial version 1.0...\n";
$v1_0 = $component->createDetailedVersion(
    versionNumber: '1.0',
    changelog: 'Initial release of Primary Button component',
    branch: 'main',
    userId: 1
);
$v1_0->publish(1);
echo "   ✓ Version 1.0 created and published\n";
echo "   Snapshot saved: " . count($v1_0->snapshot) . " fields\n\n";

// Modify component and create version 1.1
echo "🔄 Updating component properties...\n";
$component->update([
    'properties' => array_merge($component->properties, [
        'backgroundColor' => '#10B981',
        'borderRadius' => 12,
        'padding' => '16px 32px',
    ])
]);

$v1_1 = $component->createDetailedVersion(
    versionNumber: '1.1',
    changelog: 'Updated background color to green, increased border radius and padding',
    branch: 'main',
    userId: 1
);
$v1_1->publish(1);

echo "   ✓ Version 1.1 created\n";
echo "   Diff calculated: " . json_encode($v1_1->diff) . "\n\n";

// Create experimental branch
echo "🌿 Creating experimental branch version...\n";
$component->update([
    'properties' => array_merge($component->properties, [
        'boxShadow' => '0 10px 15px rgba(0,0,0,0.2)',
        'transform' => 'scale(1.05)',
    ])
]);

$experimental = $component->createDetailedVersion(
    versionNumber: '2.0',
    changelog: 'Experimental: Added shadow and hover transform',
    branch: 'experimental',
    tag: 'beta',
    userId: 1
);

echo "   ✓ Version 2.0-beta (experimental) created\n";
echo "   Branch: {$experimental->branch}\n";
echo "   Tag: {$experimental->version_tag}\n\n";

// Show all versions
echo "📜 All Versions:\n";
echo "─────────────────────────────────────────────────────────────\n";
$allVersions = $component->detailedVersions;

foreach ($allVersions as $version) {
    $published = $version->is_published ? '✓' : ' ';
    $approved = $version->is_approved ? '✓' : ' ';

    echo "   [{$published}] {$version->display_name}\n";
    echo "       Branch: {$version->branch}\n";
    echo "       Changelog: {$version->changelog}\n";
    echo "       Published: " . ($version->is_published ? 'Yes' : 'No') . "\n";

    if ($version->diff) {
        $changeCount = count($version->diff['changes'] ?? []);
        echo "       Changes: {$changeCount} modifications\n";
    }
    echo "\n";
}

// Demonstrate diff viewing
echo "🔍 Detailed Diff (v1.0 → v1.1):\n";
echo "─────────────────────────────────────────────────────────────\n";
if ($v1_1->diff && isset($v1_1->diff['changes']['properties'])) {
    $propChanges = $v1_1->diff['changes']['properties'];

    if (!empty($propChanges['modified'])) {
        echo "   Modified Properties:\n";
        foreach ($propChanges['modified'] as $prop => $change) {
            echo "     • {$prop}: {$change['from']} → {$change['to']}\n";
        }
    }

    if (!empty($propChanges['added'])) {
        echo "   Added Properties:\n";
        foreach ($propChanges['added'] as $prop => $value) {
            echo "     + {$prop}: {$value}\n";
        }
    }
}
echo "\n";

// Restore from version
echo "🔙 Restoring from version 1.0...\n";
$restored = $component->restoreFromVersion($v1_0, 1);
$restored->publish(1);

echo "   ✓ Component restored to v1.0 state\n";
echo "   New version created: {$restored->version_number}\n";
echo "   Changelog: {$restored->changelog}\n\n";

// Summary
echo "📊 Summary:\n";
echo "   Total Versions: " . $component->detailedVersions->count() . "\n";
echo "   Published: " . $component->detailedVersions()->published()->count() . "\n";
echo "   Main Branch: " . $component->versionsOnBranch('main')->count() . "\n";
echo "   Experimental: " . $component->versionsOnBranch('experimental')->count() . "\n\n";

echo "✅ Option 2 versioning system working perfectly!\n";
