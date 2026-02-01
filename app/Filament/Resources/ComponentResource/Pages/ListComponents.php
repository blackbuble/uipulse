<?php

namespace App\Filament\Resources\ComponentResource\Pages;

use App\Filament\Resources\ComponentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListComponents extends ListRecords
{
    protected static string $resource = ComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => ListRecords\Tab::make('All Components'),

            'library' => ListRecords\Tab::make('Library')
                ->modifyQueryUsing(fn($query) => $query->where('is_in_library', true))
                ->badge(fn() => static::getResource()::getModel()::inLibrary()->count()),

            'navigation' => ListRecords\Tab::make('Navigation')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'navigation')),

            'form' => ListRecords\Tab::make('Form')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'form')),

            'layout' => ListRecords\Tab::make('Layout')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'layout')),
        ];
    }
}
