<?php

namespace App\Filament\Resources\DesignResource\Pages;

use App\Filament\Resources\DesignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDesign extends EditRecord
{
    protected static string $resource = DesignResource::class;

    protected function afterSave(): void
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
                    'custom_context' => 'Automatic audit triggered during design update.',
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
