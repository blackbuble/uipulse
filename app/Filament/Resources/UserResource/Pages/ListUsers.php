<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => ListRecords\Tab::make('All Users'),

            'active' => ListRecords\Tab::make('Active')
                ->modifyQueryUsing(fn($query) => $query->where('is_active', true))
                ->badge(fn() => static::getResource()::getModel()::active()->count()),

            'inactive' => ListRecords\Tab::make('Inactive')
                ->modifyQueryUsing(fn($query) => $query->where('is_active', false)),

            'admins' => ListRecords\Tab::make('Admins')
                ->modifyQueryUsing(fn($query) => $query->byRole('admin')),

            'managers' => ListRecords\Tab::make('Managers')
                ->modifyQueryUsing(fn($query) => $query->byRole('manager')),

            'designers' => ListRecords\Tab::make('Designers')
                ->modifyQueryUsing(fn($query) => $query->byRole('designer')),
        ];
    }
}
