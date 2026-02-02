# Design Snapshot - Quick Reference

## 📸 What is a Design Snapshot?

A snapshot is a complete capture of a design's state at a specific point in time, stored in the `snapshot` field of a `DesignVersion` record.

## 🗂️ Snapshot Structure

```json
{
  "name": "Revamp Dashboard v3.0",
  "status": "completed",
  "metadata": {
    "theme": "light",
    "platform": "web",
    "viewport": "1440x900",
    "framework": "react",
    "complexity": "medium",
    "last_detection_at": "2026-02-01T15:11:46.443945Z",
    "components_detected": 0
  },
  "figma_url": "https://www.figma.com/design/TaotTFQAgl1kzMRtRjLQNd/alumnisbipb",
  "figma_file_key": "TaotTFQAgl1kzMRtRjLQNd",
  "figma_node_id": null,
  "components_count": 11,
  "created_at": "2026-02-01T15:41:26+00:00"
}
```

## 🔍 Accessing Snapshots

### Get Latest Snapshot
```php
$design = Design::find(46);
$latestVersion = $design->latestVersion();
$snapshot = $latestVersion->snapshot;

echo $snapshot['name'];              // "Revamp Dashboard v3.0"
echo $snapshot['status'];            // "completed"
echo $snapshot['components_count'];  // 11
```

### Get Specific Version Snapshot
```php
$version = $design->versions()->where('version_number', 1)->first();
$snapshot = $version->snapshot;

// Access nested data
$metadata = $snapshot['metadata'];
echo $metadata['theme'];      // "light"
echo $metadata['platform'];   // "web"
echo $metadata['framework'];  // "react"
```

### Compare Snapshots
```php
$v1 = $design->versions()->where('version_number', 1)->first();
$v2 = $design->versions()->where('version_number', 2)->first();

$snapshot1 = $v1->snapshot;
$snapshot2 = $v2->snapshot;

// Compare component counts
$componentDiff = $snapshot2['components_count'] - $snapshot1['components_count'];
echo "Components changed: {$componentDiff}";

// Compare status
if ($snapshot1['status'] !== $snapshot2['status']) {
    echo "Status changed: {$snapshot1['status']} → {$snapshot2['status']}";
}
```

## 📊 Snapshot Fields

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `name` | string | Design name | "Revamp Dashboard v3.0" |
| `status` | string | Design status | "completed", "in_progress", "in_review" |
| `metadata` | object | Design metadata | `{"theme": "light", ...}` |
| `figma_url` | string | Figma file URL | "https://www.figma.com/design/..." |
| `figma_file_key` | string | Figma file key | "TaotTFQAgl1kzMRtRjLQNd" |
| `figma_node_id` | string\|null | Specific node ID | null or "123:456" |
| `components_count` | integer | Number of components | 11 |
| `created_at` | datetime | Snapshot timestamp | "2026-02-01T15:41:26+00:00" |

## 💡 Common Use Cases

### 1. Track Component Growth
```php
$versions = $design->versions()->orderBy('version_number')->get();

echo "Component Growth:\n";
foreach ($versions as $version) {
    $count = $version->snapshot['components_count'];
    echo "v{$version->version_number}: {$count} components\n";
}

// Output:
// v1: 8 components
// v2: 11 components
// v3: 11 components
```

### 2. Monitor Status Changes
```php
$versions = $design->versions()->orderBy('version_number')->get();

echo "Status Timeline:\n";
foreach ($versions as $version) {
    $status = $version->snapshot['status'];
    $date = $version->created_at->format('Y-m-d H:i');
    echo "{$date}: {$status}\n";
}

// Output:
// 2026-02-01 15:41: completed
// 2026-02-01 15:42: in_review
// 2026-02-01 15:43: completed
```

### 3. Metadata Evolution
```php
$v1 = $design->versions()->first();
$latest = $design->latestVersion();

$oldMeta = $v1->snapshot['metadata'];
$newMeta = $latest->snapshot['metadata'];

echo "Metadata Changes:\n";
foreach ($newMeta as $key => $value) {
    if (!isset($oldMeta[$key])) {
        echo "+ {$key}: {$value}\n";
    } elseif ($oldMeta[$key] !== $value) {
        echo "• {$key}: {$oldMeta[$key]} → {$value}\n";
    }
}
```

### 4. Restore to Snapshot
```php
use App\Services\DesignVersionService;

$service = new DesignVersionService();
$oldVersion = $design->versions()->where('version_number', 1)->first();

// Restore design to snapshot state
$restored = $service->restoreFromVersion($oldVersion, auth()->id());

echo "Restored to:\n";
echo "Name: {$design->fresh()->name}\n";
echo "Status: {$design->fresh()->status}\n";
echo "Components: {$design->fresh()->components()->count()}\n";
```

## 🎯 Best Practices

### 1. Create Snapshots at Key Moments
```php
// Before major changes
$service->createManualVersion(
    $design, 
    auth()->id(), 
    'Before Redesign', 
    'Backup before homepage redesign',
    ['backup', 'milestone']
);

// After completion
$service->createManualVersion(
    $design, 
    auth()->id(), 
    'Launch Version', 
    'Final version for production',
    ['production', 'approved']
);
```

### 2. Tag Important Snapshots
```php
$version = $design->latestVersion();

// Mark as milestone
$version->addTag('milestone');

// Mark as approved
$version->addTag('approved');

// Mark for production
$version->addTag('production');

// Later, find all production snapshots
$prodVersions = $design->versions()->withTag('production')->get();
```

### 3. Document Changes
```php
$version = $service->createManualVersion(
    design: $design,
    userId: auth()->id(),
    versionName: 'v2.0 - Mobile Responsive',
    description: 'Added mobile breakpoints and responsive components. Updated color scheme to match brand guidelines.',
    tags: ['mobile', 'responsive', 'milestone']
);
```

## 🔧 Snapshot Utilities

### Extract Specific Data
```php
// Get all component counts across versions
$componentCounts = $design->versions()
    ->get()
    ->pluck('snapshot.components_count', 'version_number');

// [1 => 8, 2 => 11, 3 => 11]
```

### Find Version by Criteria
```php
// Find first version with 10+ components
$version = $design->versions()
    ->get()
    ->first(function($v) {
        return $v->snapshot['components_count'] >= 10;
    });

echo "First version with 10+ components: v{$version->version_number}";
```

### Export Snapshot
```php
$version = $design->latestVersion();
$snapshot = $version->snapshot;

// Export as JSON
file_put_contents(
    "snapshots/design-{$design->id}-v{$version->version_number}.json",
    json_encode($snapshot, JSON_PRETTY_PRINT)
);
```

## 📈 Analytics

### Version Statistics
```php
$versions = $design->versions;

$stats = [
    'total_versions' => $versions->count(),
    'avg_components' => $versions->avg(fn($v) => $v->snapshot['components_count']),
    'max_components' => $versions->max(fn($v) => $v->snapshot['components_count']),
    'status_changes' => $versions->pluck('snapshot.status')->unique()->count(),
];

print_r($stats);
```

### Timeline View
```php
foreach ($design->versions()->orderBy('created_at')->get() as $version) {
    $snap = $version->snapshot;
    
    echo "[{$version->created_at->format('Y-m-d H:i')}] ";
    echo "v{$version->version_number}: ";
    echo "{$snap['name']} ";
    echo "({$snap['status']}, {$snap['components_count']} components)\n";
}
```

## ✅ Summary

**Snapshots capture:**
- ✅ Design name and status
- ✅ Figma file information
- ✅ Component count
- ✅ Metadata (theme, platform, framework)
- ✅ Timestamp

**Use snapshots to:**
- 📊 Track design evolution
- 🔄 Restore previous states
- 📈 Analyze changes over time
- 🏷️ Mark important milestones
- 📝 Document design history

**Access via:**
```php
$snapshot = $design->latestVersion()->snapshot;
```
