<?php

namespace App\Jobs;

use App\Models\Design;
use App\Services\DesignVersionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncDesignVersionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Design $design,
        public int $userId
    ) {
    }

    public function handle(DesignVersionService $service): void
    {
        try {
            Log::info('Starting design version sync', [
                'design_id' => $this->design->id,
                'design_name' => $this->design->name,
            ]);

            $synced = $service->syncVersionsFromFigma($this->design, $this->userId);

            Log::info('Design version sync completed', [
                'design_id' => $this->design->id,
                'versions_synced' => $synced,
            ]);

        } catch (\Exception $e) {
            Log::error('Design version sync failed', [
                'design_id' => $this->design->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
