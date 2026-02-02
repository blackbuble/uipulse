<div class="space-y-2">
    @if(isset($label))
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    @endif

    @if($data)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4">
            <pre
                class="text-xs font-mono text-gray-700 dark:text-gray-300 overflow-x-auto whitespace-pre-wrap break-words">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">No data available</p>
    @endif
</div>