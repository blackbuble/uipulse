<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->update('ai.providers', function ($providers) {
            return collect($providers)->map(function ($provider) {
                // Ensure array to avoid stdClass error
                $provider = (array) $provider;

                if ($provider['id'] === 'gemini') {
                    $provider['supports_generation'] = true;
                }
                return $provider;
            })->toArray();
        });
    }
};
