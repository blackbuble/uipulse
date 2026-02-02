<div class="space-y-6">
    @if($analyses->count() > 0)
        {{-- Analysis Timeline --}}
        <div class="relative">
            @foreach($analyses as $index => $analysis)
                <div class="relative pb-8 {{ $index === $analyses->count() - 1 ? '' : 'border-l-2 border-gray-200 dark:border-gray-700' }} ml-4">
                    {{-- Timeline dot --}}
                    <div class="absolute -left-[9px] top-0">
                        <div class="h-4 w-4 rounded-full {{ $analysis->status === 'completed' ? 'bg-green-500' : 'bg-red-500' }} border-2 border-white dark:border-gray-900"></div>
                    </div>

                    {{-- Analysis card --}}
                    <div class="ml-6 rounded-lg border {{ $analysis->status === 'completed' ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' }} p-4">
                        {{-- Header --}}
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ match($analysis->type) {
                                            'component_quality' => '🎯 Quality Assessment',
                                            'component_accessibility' => '♿ Accessibility Audit',
                                            'component_best_practices' => '✨ Best Practices',
                                            'component_improvements' => '💡 Improvements',
                                            'component_code_gen' => '💻 Code Generation',
                                            default => $analysis->type
                                        } }}
                                    </h3>

                                    @if($analysis->status === 'completed')
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Completed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Failed
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $analysis->created_at->diffForHumans() }}
                                    • Provider: {{ ucfirst($analysis->provider) }}
                                    • Model: {{ $analysis->model_name }}
                                </p>
                            </div>

                            @if($analysis->status === 'completed' && isset($analysis->results['score']))
                                <div class="ml-4 flex flex-col items-center">
                                    <div class="text-3xl font-bold {{ $analysis->results['score'] >= 8 ? 'text-green-600' : ($analysis->results['score'] >= 6 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $analysis->results['score'] }}
                                    </div>
                                    <div class="text-xs text-gray-500">/ 10</div>
                                </div>
                            @endif
                        </div>

                        @if($analysis->status === 'completed')
                            {{-- Summary --}}
                            @if(isset($analysis->results['summary']))
                                <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $analysis->results['summary'] }}
                                </p>
                            @endif

                            {{-- Details --}}
                            @if(isset($analysis->results['details']))
                                <div class="mt-4 space-y-2">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Detailed Assessment:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach($analysis->results['details'] as $key => $detail)
                                            <div class="rounded-md bg-white dark:bg-gray-800 p-2 border border-gray-200 dark:border-gray-700">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                        {{ ucwords(str_replace('_', ' ', $key)) }}
                                                    </span>
                                                    @if(isset($detail['score']))
                                                        <span class="text-sm font-bold {{ $detail['score'] >= 8 ? 'text-green-600' : ($detail['score'] >= 6 ? 'text-yellow-600' : 'text-red-600') }}">
                                                            {{ $detail['score'] }}/10
                                                        </span>
                                                    @endif
                                                </div>
                                                @if(isset($detail['feedback']))
                                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                        {{ $detail['feedback'] }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Assessments (for best practices) --}}
                            @if(isset($analysis->results['assessments']))
                                <div class="mt-4 space-y-2">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Assessments:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach($analysis->results['assessments'] as $key => $assessment)
                                            <div class="rounded-md bg-white dark:bg-gray-800 p-2 border border-gray-200 dark:border-gray-700">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                        {{ ucwords(str_replace('_', ' ', $key)) }}
                                                    </span>
                                                    @if(isset($assessment['score']))
                                                        <span class="text-sm font-bold {{ $assessment['score'] >= 8 ? 'text-green-600' : ($assessment['score'] >= 6 ? 'text-yellow-600' : 'text-red-600') }}">
                                                            {{ $assessment['score'] }}/10
                                                        </span>
                                                    @endif
                                                </div>
                                                @if(isset($assessment['feedback']))
                                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                        {{ $assessment['feedback'] }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Accessibility Issues --}}
                            @if(isset($analysis->results['issues']) && count($analysis->results['issues']) > 0)
                                <div class="mt-4 space-y-2">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Accessibility Issues:</h4>
                                    @foreach($analysis->results['issues'] as $issue)
                                        <div class="rounded-md bg-{{ $issue['severity'] === 'high' ? 'red' : ($issue['severity'] === 'medium' ? 'yellow' : 'blue') }}-50 dark:bg-{{ $issue['severity'] === 'high' ? 'red' : ($issue['severity'] === 'medium' ? 'yellow' : 'blue') }}-900/20 p-3 border border-{{ $issue['severity'] === 'high' ? 'red' : ($issue['severity'] === 'medium' ? 'yellow' : 'blue') }}-200 dark:border-{{ $issue['severity'] === 'high' ? 'red' : ($issue['severity'] === 'medium' ? 'yellow' : 'blue') }}-800">
                                            <div class="flex items-start gap-2">
                                                <span class="inline-flex items-center rounded-full bg-{{ $issue['severity'] === 'high' ? 'red' : ($issue['severity'] === 'medium' ? 'yellow' : 'blue') }}-100 px-2 py-0.5 text-xs font-medium text-{{ $issue['severity'] === 'high' ? 'red' : ($issue['severity'] === 'medium' ? 'yellow' : 'blue') }}-800 dark:bg-{{ $issue['severity'] === 'high' ? 'red' : ($issue['severity'] === 'medium' ? 'yellow' : 'blue') }}-900 dark:text-{{ $issue['severity'] === 'high' ? 'red' : ($issue['severity'] === 'medium' ? 'yellow' : 'blue') }}-200">
                                                    {{ ucfirst($issue['severity']) }}
                                                </span>
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $issue['issue'] }}
                                                    </p>
                                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                        {{ $issue['recommendation'] }}
                                                    </p>
                                                    @if(isset($issue['wcag_criterion']))
                                                        <p class="mt-1 text-xs text-gray-500">
                                                            WCAG {{ $issue['wcag_criterion'] }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Recommendations --}}
                            @if(isset($analysis->results['recommendations']) && count($analysis->results['recommendations']) > 0)
                                <div class="mt-4 space-y-2">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Recommendations:</h4>
                                    <ul class="space-y-1">
                                        @foreach($analysis->results['recommendations'] as $recommendation)
                                            <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <span class="text-blue-500">•</span>
                                                @if(is_array($recommendation))
                                                    <div class="flex-1">
                                                        <span class="font-medium">{{ $recommendation['suggestion'] ?? $recommendation['title'] ?? '' }}</span>
                                                        @if(isset($recommendation['priority']))
                                                            <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                                                {{ ucfirst($recommendation['priority']) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span>{{ $recommendation }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Action Items --}}
                            @if(isset($analysis->results['action_items']) && count($analysis->results['action_items']) > 0)
                                <div class="mt-4 space-y-2">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Action Items:</h4>
                                    <ul class="space-y-1">
                                        @foreach($analysis->results['action_items'] as $item)
                                            <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <span class="text-green-500">✓</span>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Code Block for Code Generation --}}
                            @if($analysis->type === 'component_code_gen' && isset($analysis->results['code']))
                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Generated {{ $analysis->results['framework'] ?? '' }} Code:</h4>
                                        <button 
                                            onclick="copyAnalysisCode('{{ addslashes($analysis->results['code']) }}')"
                                            class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-primary-700 bg-primary-100 hover:bg-primary-200 dark:bg-primary-900/40 dark:text-primary-300"
                                        >
                                            Copy Code
                                        </button>
                                    </div>
                                    <div class="relative group">
                                        <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-xs leading-relaxed border border-gray-700 custom-scrollbar" style="max-height: 400px;"><code class="language-{{ $analysis->results['framework'] ?? 'tsx' }}">{{ $analysis->results['code'] }}</code></pre>
                                    </div>
                                </div>
                            @endif

                            <script>
                                function copyAnalysisCode(text) {
                                    navigator.clipboard.writeText(text).then(() => {
                                        new FilamentNotification()
                                            .title('Copied to clipboard!')
                                            .success()
                                            .send();
                                    });
                                }
                            </script>

                        @else
                            {{-- Error message --}}
                            <div class="mt-3 rounded-md bg-red-50 dark:bg-red-900/20 p-3">
                                <p class="text-sm text-red-800 dark:text-red-200">
                                    <strong>Error:</strong> {{ $analysis->results['error'] ?? 'Analysis failed' }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No AI analysis results yet</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">Click "AI Analyze" to start analyzing this component</p>
        </div>
    @endif
</div>
