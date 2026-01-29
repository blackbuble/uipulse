<?php

namespace App\Jobs;

use App\Models\Design;
use App\Models\User;
use App\Services\AiService;
use App\Models\AiAnalysis;
use App\Filament\Resources\AiAnalysisResource;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDesignAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Design $design,
        public string $type = 'accessibility',
        public ?string $provider = null,
        public array $options = [],
        public ?int $userId = null
    ) {
        if (!$this->userId) {
            $this->userId = auth()->id();
        }
    }

    /**
     * Execute the job.
     */
    public function handle(AiService $aiService): void
    {
        $this->design->update(['status' => 'processing']);

        try {
            $analysis = $aiService->analyzeDesign($this->design, $this->type, $this->provider, $this->options);
            $this->design->update(['status' => 'completed']);

            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    Notification::make()
                        ->title('Analysis Completed')
                        ->body("The {$this->type} analysis for '{$this->design->name}' is ready.")
                        ->success()
                        ->actions([
                            Action::make('view')
                                ->button()
                                ->url(AiAnalysisResource::getUrl('view', ['record' => $analysis])),
                        ])
                        ->sendToDatabase($user);
                }
            }
        } catch (\Exception $e) {
            Log::error("Analysis Job Failed: " . $e->getMessage(), [
                'design_id' => $this->design->id,
                'type' => $this->type,
                'provider' => $this->provider,
                'options' => $this->options,
            ]);

            $this->design->update(['status' => 'failed']);

            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    Notification::make()
                        ->title('Analysis Failed')
                        ->body("The {$this->type} analysis for '{$this->design->name}' failed: " . $e->getMessage())
                        ->danger()
                        ->sendToDatabase($user);
                }
            }
            throw $e;
        }
    }
}
