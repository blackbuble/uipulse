<?php

namespace App\Filament\Pages;

use App\Settings\AiSettings;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageAiSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 1;

    protected static string $settings = AiSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Global Configuration')
                    ->schema([
                        Select::make('default_provider')
                            ->options(fn(AiSettings $settings) => collect($settings->providers)->pluck('name', 'id'))
                            ->required(),
                    ]),

                Section::make('AI Providers')
                    ->description('Manage your AI models and vision capabilities.')
                    ->schema([
                        Repeater::make('providers')
                            ->schema([
                                TextInput::make('id')
                                    ->required()
                                    ->rules(['alpha_dash']),
                                TextInput::make('name')
                                    ->required(),
                                TextInput::make('url')
                                    ->label('Base API URL')
                                    ->required()
                                    ->url(),
                                TextInput::make('key')
                                    ->label('API Key')
                                    ->password()
                                    ->revealable(),
                                TextInput::make('model')
                                    ->required(),
                                Toggle::make('supports_vision')
                                    ->label('Supports Vision (Images)')
                                    ->default(false),
                            ])
                            ->columns(2)
                            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null),
                    ]),
            ]);
    }
}
