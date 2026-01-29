<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('ai.default_provider', 'openai');
        $this->migrator->add('ai.providers', [
            [
                'id' => 'openai',
                'name' => 'OpenAI (GPT-4o)',
                'url' => 'https://api.openai.com/v1',
                'key' => config('services.ai.providers.openai.key'),
                'model' => 'gpt-4o',
                'supports_vision' => true,
            ],
            [
                'id' => 'deepseek',
                'name' => 'DeepSeek v3',
                'url' => 'https://api.deepseek.com/v1',
                'key' => config('services.ai.providers.deepseek.key'),
                'model' => 'deepseek-chat',
                'supports_vision' => false,
            ],
            [
                'id' => 'anthropic',
                'name' => 'Anthropic (Claude 3.5 Sonnet)',
                'url' => 'https://api.anthropic.com/v1',
                'key' => '',
                'model' => 'claude-3-5-sonnet-20240620',
                'supports_vision' => true,
            ],
        ]);
    }
};
