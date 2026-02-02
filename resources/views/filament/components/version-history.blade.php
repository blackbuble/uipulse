<div class="space-y-4">
    @if($versions->count() > 0)
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($versions as $version)
                        <div class="flex items-start space-x-4 rounded-lg border p-4 {{ $version->is_latest_version ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <div class="flex-shrink-0">
                                @if($version->is_latest_version)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Latest
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        v{{ $version->version }}
                                    </span>
                                @endif
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        Version {{ $version->version }}
                                        @if($version->is_latest_version)
                                            <span class="ml-2 text-green-600 dark:text-green-400">(Current)</span>
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $version->version_created_at ? $version->version_created_at->diffForHumans() : $version->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                
                                @if($version->changelog)
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $version->changelog }}
                                    </p>
                                @endif
                                
                                @if($version->versionCreator)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Created by {{ $version->versionCreator->name }}
                                    </p>
                                @endif
                                
                                <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span>
                                        <svg class="inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        {{ $version->type }}
                                    </span>
                                    <span>
                                        <svg class="inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                        </svg>
                                        {{ count($version->properties ?? []) }} properties
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No version history available</p>
        </div>
    @endif
</div>
