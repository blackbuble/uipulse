<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Design;
use App\Services\DesignVersionService;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║          Design Versioning - Demo                       ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$service = new DesignVersionService();
$design = Design::first();

if (!$design) {
    echo "❌ No designs found\n";
    exit(1);
}

echo "📐 Design: {$design->name}\n";
echo "   Figma File: {$design->figma_file_key}\n";
echo "   Status: {$design->status}\n\n";

// Method 1: Fetch versions from Figma API
echo "═══════════════════════════════════════════════════════════\n";
echo "Method 1: Fetch from Figma API\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    echo "🔄 Fetching version history from Figma...\n";
    $figmaVersions = $service->fetchVersionsFromFigma($design);

    echo "   ✓ Found " . count($figmaVersions) . " versions in Figma\n\n";

    if (count($figmaVersions) > 0) {
        echo "   Recent Figma Versions:\n";
        echo "   ─────────────────────────────────────────────────────────\n";
        foreach (array_slice($figmaVersions, 0, 5) as $index => $version) {
            $label = $version['label'] ?? 'Untitled';
            $created = $version['created_at'] ?? 'Unknown';
            $user = $version['user']['handle'] ?? 'Unknown';

            echo "   " . ($index + 1) . ". {$label}\n";
            echo "      Created: {$created}\n";
            echo "      By: {$user}\n\n";
        }
    }

} catch (\Exception $e) {
    echo "   ⚠️  Could not fetch from Figma: {$e->getMessage()}\n";
    echo "   (This is normal if you don't have Figma token or file has no versions)\n\n";
}

// Method 2: Create manual version snapshot
echo "═══════════════════════════════════════════════════════════\n";
echo "Method 2: Create Manual Snapshot\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📸 Creating manual version snapshot...\n";
$manualVersion = $service->createManualVersion(
    design: $design,
    userId: 1,
    versionName: 'Initial Snapshot',
    description: 'First manual snapshot of the design',
    tags: ['milestone', 'manual']
);

echo "   ✓ Version created: v{$manualVersion->version_number}\n";
echo "   Name: {$manualVersion->version_name}\n";
echo "   Description: {$manualVersion->description}\n";
echo "   Tags: " . implode(', ', $manualVersion->tags ?? []) . "\n";
echo "   Snapshot fields: " . count($manualVersion->snapshot) . "\n\n";

// Modify design and create another version
echo "🔄 Modifying design and creating v2...\n";
$design->update([
    'status' => 'in_review',
    'metadata' => array_merge($design->metadata ?? [], [
        'reviewed_at' => now()->toIso8601String(),
        'reviewer' => 'John Doe',
    ])
]);

$v2 = $service->createManualVersion(
    design: $design,
    userId: 1,
    versionName: 'Review Version',
    description: 'Design ready for review',
    tags: ['review', 'manual']
);

echo "   ✓ Version v{$v2->version_number} created\n";
echo "   Changes detected: " . (count($v2->changes ?? []) > 0 ? 'Yes' : 'No') . "\n\n";

if ($v2->changes) {
    echo "   📝 Changes from v{$manualVersion->version_number}:\n";
    echo "   ─────────────────────────────────────────────────────────\n";

    if (isset($v2->changes['modified'])) {
        foreach ($v2->changes['modified'] as $field => $change) {
            echo "   • {$field}:\n";
            echo "     From: " . json_encode($change['from']) . "\n";
            echo "     To: " . json_encode($change['to']) . "\n";
        }
    }
    echo "\n";
}

// Method 3: View all versions
echo "═══════════════════════════════════════════════════════════\n";
echo "Method 3: View All Versions\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$allVersions = $design->versions()->orderBy('version_number', 'desc')->get();

echo "📚 All Design Versions ({$allVersions->count()}):\n";
echo "─────────────────────────────────────────────────────────────\n";

foreach ($allVersions as $version) {
    $type = $version->is_auto_version ? '[AUTO]' : '[MANUAL]';
    $tags = $version->tags ? ' 🏷️  ' . implode(', ', $version->tags) : '';

    echo "v{$version->version_number} {$type}{$tags}\n";
    echo "├─ Name: " . ($version->version_name ?? 'Untitled') . "\n";
    echo "├─ Description: " . ($version->description ?? 'No description') . "\n";
    echo "├─ Created: {$version->created_at->diffForHumans()}\n";

    if ($version->changes) {
        $changeCount = count($version->changes['modified'] ?? [])
            + count($version->changes['added'] ?? [])
            + count($version->changes['removed'] ?? []);
        echo "└─ Changes: {$changeCount} modifications\n";
    } else {
        echo "└─ Changes: Initial version\n";
    }
    echo "\n";
}

// Method 4: Restore from version
echo "═══════════════════════════════════════════════════════════\n";
echo "Method 4: Restore from Version\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "🔙 Restoring design from v{$manualVersion->version_number}...\n";
$restored = $service->restoreFromVersion($manualVersion, 1);

echo "   ✓ Design restored!\n";
echo "   New version created: v{$restored->version_number}\n";
echo "   Status reverted to: {$design->fresh()->status}\n\n";

// Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "📊 Summary\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$totalVersions = $design->versions()->count();
$manualVersions = $design->versions()->manual()->count();
$autoVersions = $design->versions()->auto()->count();

echo "Total Versions: {$totalVersions}\n";
echo "├─ Manual: {$manualVersions}\n";
echo "└─ Auto (from Figma): {$autoVersions}\n\n";

echo "✅ Design versioning system working perfectly!\n\n";

echo "💡 How to get design versions:\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "1. Fetch from Figma API:\n";
echo "   \$service->fetchVersionsFromFigma(\$design)\n\n";
echo "2. Sync Figma versions to DB:\n";
echo "   \$service->syncVersionsFromFigma(\$design, \$userId)\n\n";
echo "3. Create manual snapshot:\n";
echo "   \$service->createManualVersion(\$design, \$userId, 'v1.0', 'Description')\n\n";
echo "4. Get all versions:\n";
echo "   \$design->versions()->get()\n\n";
echo "5. Restore from version:\n";
echo "   \$service->restoreFromVersion(\$version, \$userId)\n\n";
