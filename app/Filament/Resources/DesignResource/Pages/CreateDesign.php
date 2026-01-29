<?php

namespace App\Filament\Resources\DesignResource\Pages;

use App\Filament\Resources\DesignResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDesign extends CreateRecord
{
    protected static string $resource = DesignResource::class;

    protected function afterCreate(): void
    {
        if ($this->data['auto_analyze'] ?? false) {
            $settings = app(\App\Settings\AiSettings::class);

            \App\Jobs\ProcessDesignAnalysis::dispatch(
                $this->record,
                'accessibility',
                $settings->default_provider,
                [
                    'depth' => 'standard',
                    'focus_areas' => ['typography', 'colors', 'accessibility', 'ux_logic', 'consistency'],
                    'custom_context' => 'Automatic audit triggered during design creation.',
                ]
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        if ($this->data['auto_analyze'] ?? false) {
            return \App\Filament\Resources\AiAnalysisResource::getUrl('index');
        }

        return parent::getRedirectUrl();
    }
}
