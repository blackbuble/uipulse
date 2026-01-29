<?php

namespace App\Filament\Resources\AiAnalysisResource\Pages;

use App\Filament\Resources\AiAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAiAnalyses extends ListRecords
{
    protected static string $resource = AiAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
