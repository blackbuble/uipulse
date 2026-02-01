<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateDesignFromFigma;
use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FigmaWebhookController extends Controller
{
    /**
     * Handle incoming Figma webhooks.
     */
    public function handle(Request $request)
    {
        Log::info('Figma webhook received', [
            'event_type' => $request->input('event_type'),
            'file_key' => $request->input('file_key'),
        ]);

        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            Log::warning('Invalid Figma webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $eventType = $request->input('event_type');
        $fileKey = $request->input('file_key');
        $timestamp = $request->input('timestamp');

        // Route to appropriate handler
        match ($eventType) {
            'FILE_UPDATE' => $this->handleFileUpdate($fileKey, $request->all()),
            'FILE_VERSION_UPDATE' => $this->handleVersionUpdate($fileKey, $request->all()),
            'FILE_COMMENT' => $this->handleComment($fileKey, $request->all()),
            'LIBRARY_PUBLISH' => $this->handleLibraryPublish($fileKey, $request->all()),
            default => Log::info("Unhandled event type: {$eventType}"),
        };

        return response()->json(['status' => 'success']);
    }

    /**
     * Verify webhook signature.
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Figma-Signature');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $secret = config('services.figma.webhook_secret');

        if (!$secret) {
            Log::warning('Figma webhook secret not configured');
            return true; // Allow in development
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle file update event.
     */
    private function handleFileUpdate(string $fileKey, array $data): void
    {
        Log::info("Handling file update for: {$fileKey}");

        // Find all designs with this file key
        $designs = Design::where('figma_file_key', $fileKey)->get();

        foreach ($designs as $design) {
            // Dispatch job to update design
            UpdateDesignFromFigma::dispatch($design);
        }
    }

    /**
     * Handle version update event.
     */
    private function handleVersionUpdate(string $fileKey, array $data): void
    {
        Log::info("Handling version update for: {$fileKey}");

        $designs = Design::where('figma_file_key', $fileKey)->get();

        foreach ($designs as $design) {
            // Create automatic version snapshot
            \App\Jobs\CreateVersionSnapshotJob::dispatch(
                $design,
                null,
                "Figma Update - " . now()->format('Y-m-d H:i'),
                "Automatic version created from Figma update",
                ['figma-sync'],
                true
            );
        }
    }

    /**
     * Handle comment event.
     */
    private function handleComment(string $fileKey, array $data): void
    {
        Log::info("Handling comment for: {$fileKey}", $data);

        // Could sync Figma comments to design comments
        // Implementation depends on requirements
    }

    /**
     * Handle library publish event.
     */
    private function handleLibraryPublish(string $fileKey, array $data): void
    {
        Log::info("Handling library publish for: {$fileKey}");

        // Could trigger component library updates
        // Implementation depends on requirements
    }
}
