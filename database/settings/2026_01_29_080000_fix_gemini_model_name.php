<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->update('ai.providers', function ($providers) {
            return collect($providers)->map(function ($provider) {
                // Ensure array
                $provider = (array) $provider;

                if ($provider['id'] === 'gemini') {
                    // Fix incorrect model name
                    $provider['model'] = 'gemini-1.5-flash';
                    // Ensure supports_generation is set correctly (Gemini doesn't support image gen yet via this API in our logic)
                    $provider['supports_generation'] = false;
                    // Ensure vision is supported
                    $provider['supports_vision'] = true;
                }
                return $provider;
            })->toArray();
        });
    }
};
