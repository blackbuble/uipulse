<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->update('ai.providers', function ($providers) {
            return collect($providers)->map(function ($provider) {
                // Ensure provider is an array
                $provider = (array) $provider;

                // Default openai to true, others to false
                $provider['supports_generation'] = ($provider['id'] === 'openai');
                return $provider;
            })->toArray();
        });
    }
};
