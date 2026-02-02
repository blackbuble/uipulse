<div class="p-4">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $filename }}
        </h3>
        <button onclick="copyToClipboard('{{ addslashes($code) }}')"
            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            Copy Code
        </button>
    </div>

    <div class="relative group">
        <pre class="bg-gray-950 text-gray-100 p-6 rounded-xl overflow-x-auto text-sm leading-relaxed border border-gray-800 shadow-2xl custom-scrollbar"
            style="max-height: 500px;"><code class="language-{{ $framework == 'react' ? 'tsx' : ($framework == 'vue' ? 'vue' : 'html') }}">{{ $code }}</code></pre>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                new FilamentNotification()
                    .title('Copied to clipboard!')
                    .success()
                    .send();
            });
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #111827;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #4b5563;
        }
    </style>
</div>