<?php

namespace App\Filament\Resources\DesignVersionResource\Pages;

use App\Filament\Resources\DesignVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDesignVersion extends EditRecord
{
    protected static string $resource = DesignVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
