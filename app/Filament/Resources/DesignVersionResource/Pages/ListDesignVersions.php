<?php

namespace App\Filament\Resources\DesignVersionResource\Pages;

use App\Filament\Resources\DesignVersionResource;
use Filament\Resources\Pages\ListRecords;

class ListDesignVersions extends ListRecords
{
    protected static string $resource = DesignVersionResource::class;

    public function getTabs(): array
    {
        return [
            'all' => ListRecords\Tab::make('All Versions'),

            'manual' => ListRecords\Tab::make('Manual')
                ->modifyQueryUsing(fn($query) => $query->where('is_auto_version', false))
                ->badge(fn() => static::getResource()::getModel()::manual()->count()),

            'auto' => ListRecords\Tab::make('Auto-saved')
                ->modifyQueryUsing(fn($query) => $query->where('is_auto_version', true)),

            'milestones' => ListRecords\Tab::make('Milestones')
                ->modifyQueryUsing(fn($query) => $query->whereJsonContains('tags', 'milestone')),

            'approved' => ListRecords\Tab::make('Approved')
                ->modifyQueryUsing(fn($query) => $query->whereJsonContains('tags', 'approved')),
        ];
    }
}
