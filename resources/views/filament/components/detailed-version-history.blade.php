<div class="space-y-6">
    @if($versions->count() > 0)
        <!-- Version Timeline -->
        <div class="relative">
            @foreach($versions as $index => $version)
                <div
                    class="relative pb-8 {{ $index === $versions->count() - 1 ? '' : 'border-l-2 border-gray-200 dark:border-gray-700' }} ml-4">
                    <!-- Timeline dot -->
                    <div class="absolute -left-[9px] top-0">
                        <div
                            class="h-4 w-4 rounded-full {{ $version->is_published ? 'bg-green-500' : 'bg-gray-400' }} border-2 border-white dark:border-gray-900">
                        </div>
                    </div>

                    <!-- Version card -->
                    <div
                        class="ml-6 rounded-lg border {{ $version->is_published ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }} p-4">
                        <!-- Header -->
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $version->display_name }}
                                    </h3>

                                    @if($version->is_published)
                                        <span
                                            class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Published
                                        </span>
                                    @endif

                                    @if($version->is_approved)
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            Approved
                                        </span>
                                    @endif

                                    @if($version->hasBreakingChanges())
                                        <span
                                            class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Breaking
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $version->created_at->diffForHumans() }}
                                    @if($version->creator)
                                        by {{ $version->creator->name }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Changelog -->
                        @if($version->changelog)
                            <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $version->changelog }}
                            </p>
                        @endif

                        <!-- Breaking changes -->
                        @if($version->breaking_changes)
                            <div class="mt-3 rounded-md bg-red-50 p-3 dark:bg-red-900/20">
                                <div class="flex">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-red-800 dark:text-red-200">Breaking Changes</h4>
                                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $version->breaking_changes }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Diff summary -->
                        @if($version->diff && isset($version->diff['changes']))
                            <div class="mt-3 space-y-2">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Changes:</h4>

                                @if(isset($version->diff['changes']['properties']['modified']) && count($version->diff['changes']['properties']['modified']) > 0)
                                    <div class="space-y-1">
                                        @foreach($version->diff['changes']['properties']['modified'] as $prop => $change)
                                            <div class="flex items-center gap-2 text-xs">
                                                <span
                                                    class="rounded bg-yellow-100 px-2 py-0.5 font-mono text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                    {{ $prop }}
                                                </span>
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    <span
                                                        class="line-through">{{ is_array($change['from']) ? json_encode($change['from']) : $change['from'] }}</span>
                                                    →
                                                    <span
                                                        class="font-medium text-gray-900 dark:text-gray-100">{{ is_array($change['to']) ? json_encode($change['to']) : $change['to'] }}</span>
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if(isset($version->diff['changes']['properties']['added']) && count($version->diff['changes']['properties']['added']) > 0)
                                    <div class="space-y-1">
                                        @foreach($version->diff['changes']['properties']['added'] as $prop => $value)
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="text-green-600 dark:text-green-400">+</span>
                                                <span
                                                    class="rounded bg-green-100 px-2 py-0.5 font-mono text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    {{ $prop }}
                                                </span>
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    {{ is_array($value) ? json_encode($value) : $value }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if(isset($version->diff['changes']['properties']['removed']) && count($version->diff['changes']['properties']['removed']) > 0)
                                    <div class="space-y-1">
                                        @foreach($version->diff['changes']['properties']['removed'] as $prop => $value)
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="text-red-600 dark:text-red-400">-</span>
                                                <span
                                                    class="rounded bg-red-100 px-2 py-0.5 font-mono text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    {{ $prop }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No detailed version history available</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">Create your first detailed version to start tracking changes
            </p>
        </div>
    @endif
</div>