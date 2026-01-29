<?php

namespace App\Filament\Resources\AiAnalysisResource\Pages;

use App\Filament\Resources\AiAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAiAnalysis extends ViewRecord
{
    protected static string $resource = AiAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn() => route('ai-analysis.pdf', $this->record))
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
