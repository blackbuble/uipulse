<?php

namespace App\Filament\Resources\AiAnalysisResource\Pages;

use App\Filament\Resources\AiAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAiAnalysis extends EditRecord
{
    protected static string $resource = AiAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
