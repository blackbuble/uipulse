<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotificationService
{
    private ?string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('services.slack.webhook_url');
    }

    /**
     * Send a design update notification.
     */
    public function notifyDesignUpdate(string $designName, string $designUrl, string $userName): void
    {
        $this->send([
            'text' => "Design Updated: {$designName}",
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🎨 Design Updated',
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*{$designName}* has been updated by *{$userName}*",
                    ],
                ],
                [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'View Design',
                            ],
                            'url' => $designUrl,
                            'style' => 'primary',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Send analysis complete notification.
     */
    public function notifyAnalysisComplete(
        string $designName,
        int $componentsFound,
        int $issuesFound,
        string $designUrl
    ): void {
        $issueEmoji = $issuesFound > 0 ? '⚠️' : '✅';

        $this->send([
            'text' => "Analysis Complete: {$designName}",
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🤖 AI Analysis Complete',
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*{$designName}*\n\n" .
                            "• Components detected: *{$componentsFound}*\n" .
                            "• Accessibility issues: {$issueEmoji} *{$issuesFound}*",
                    ],
                ],
                [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'View Results',
                            ],
                            'url' => $designUrl,
                            'style' => 'primary',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Send comment mention notification.
     */
    public function notifyCommentMention(
        string $designName,
        string $commenterName,
        string $comment,
        string $commentUrl
    ): void {
        $this->send([
            'text' => "You were mentioned in a comment",
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '💬 New Mention',
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*{$commenterName}* mentioned you in *{$designName}*:\n\n> {$comment}",
                    ],
                ],
                [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'View Comment',
                            ],
                            'url' => $commentUrl,
                            'style' => 'primary',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Send component added to library notification.
     */
    public function notifyComponentAddedToLibrary(
        string $componentName,
        string $componentType,
        string $componentUrl
    ): void {
        $this->send([
            'text' => "Component Added to Library: {$componentName}",
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '📚 Component Library Updated',
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "New *{$componentType}* component added:\n*{$componentName}*",
                    ],
                ],
                [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'View Component',
                            ],
                            'url' => $componentUrl,
                            'style' => 'primary',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Send GitHub PR created notification.
     */
    public function notifyPRCreated(
        string $componentName,
        string $prUrl,
        string $repository
    ): void {
        $this->send([
            'text' => "Pull Request Created: {$componentName}",
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🚀 Pull Request Created',
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "Component *{$componentName}* has been pushed to *{$repository}*",
                    ],
                ],
                [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'Review PR',
                            ],
                            'url' => $prUrl,
                            'style' => 'primary',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Send a message to Slack.
     */
    private function send(array $payload): void
    {
        if (!$this->webhookUrl) {
            Log::warning('Slack webhook URL not configured');
            return;
        }

        try {
            $response = Http::post($this->webhookUrl, $payload);

            if (!$response->successful()) {
                Log::error('Failed to send Slack notification', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Slack notification error', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Test Slack connection.
     */
    public function testConnection(): bool
    {
        try {
            $this->send([
                'text' => '✅ UIPulse Slack integration is working!',
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => '✅ *UIPulse Slack Integration*\n\nYour Slack integration is configured correctly!',
                        ],
                    ],
                ],
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Slack connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
