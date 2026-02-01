<?php

namespace App\Jobs;

use App\Models\Component;
use App\Services\GitHubIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateComponentPRJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Component $component,
        public string $repository,
        public string $branch,
        public string $framework = 'react'
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(GitHubIntegrationService $service): void
    {
        Log::info("Creating GitHub PR for component: {$this->component->name}");

        try {
            $pr = $service->createComponentPR(
                $this->component,
                $this->repository,
                $this->branch,
                $this->framework
            );

            // Update component metadata with PR info
            $this->component->update([
                'properties' => array_merge($this->component->properties ?? [], [
                    'github_pr' => [
                        'number' => $pr['number'],
                        'url' => $pr['html_url'],
                        'created_at' => now()->toISOString(),
                    ],
                ]),
            ]);

            Log::info("GitHub PR created successfully", [
                'component_id' => $this->component->id,
                'pr_number' => $pr['number'],
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create GitHub PR for component: {$this->component->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("GitHub PR job failed permanently for component: {$this->component->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
