<?php

namespace App\Filament\Resources\DesignVersionResource\Pages;

use App\Filament\Resources\DesignVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDesignVersion extends ViewRecord
{
    protected static string $resource = DesignVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
