<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComponentResource\Pages;
use App\Models\Component;
use App\Services\CodeGenerationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ComponentResource extends Resource
{
    protected static ?string $model = Component::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Design System';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Component Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'button' => 'Button',
                                'input' => 'Input',
                                'select' => 'Select',
                                'checkbox' => 'Checkbox',
                                'radio' => 'Radio',
                                'card' => 'Card',
                                'modal' => 'Modal',
                                'tooltip' => 'Tooltip',
                                'menu' => 'Menu',
                                'tab' => 'Tab',
                                'icon' => 'Icon',
                                'other' => 'Other',
                            ]),

                        Forms\Components\Select::make('category')
                            ->options([
                                'navigation' => 'Navigation',
                                'form' => 'Form',
                                'layout' => 'Layout',
                                'overlay' => 'Overlay',
                                'typography' => 'Typography',
                                'media' => 'Media',
                                'other' => 'Other',
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Properties')
                    ->schema([
                        Forms\Components\KeyValue::make('properties')
                            ->label('Component Properties')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\Toggle::make('is_in_library')
                            ->label('Add to Library')
                            ->helperText('Make this component available in the component library'),

                        Forms\Components\TextInput::make('usage_count')
                            ->numeric()
                            ->disabled()
                            ->default(1),

                        Forms\Components\TextInput::make('variant_count')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('Preview')
                    ->circular()
                    ->defaultImageUrl(fn($record) => $record->thumbnail_url),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->description),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'button',
                        'success' => 'input',
                        'warning' => 'card',
                        'danger' => 'modal',
                        'secondary' => 'other',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('category')
                    ->colors([
                        'primary' => 'navigation',
                        'success' => 'form',
                        'info' => 'layout',
                        'warning' => 'overlay',
                        'secondary' => 'other',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Used')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('variant_count')
                    ->label('Variants')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_in_library')
                    ->label('In Library')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('design.name')
                    ->label('Design')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'button' => 'Button',
                        'input' => 'Input',
                        'card' => 'Card',
                        'modal' => 'Modal',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'navigation' => 'Navigation',
                        'form' => 'Form',
                        'layout' => 'Layout',
                        'overlay' => 'Overlay',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_in_library')
                    ->label('In Library')
                    ->placeholder('All components')
                    ->trueLabel('Library only')
                    ->falseLabel('Not in library'),
            ])
            ->actions([
                Tables\Actions\Action::make('add_to_library')
                    ->icon('heroicon-o-bookmark')
                    ->color('success')
                    ->visible(fn($record) => !$record->is_in_library)
                    ->action(function ($record) {
                        $record->addToLibrary();

                        Notification::make()
                            ->title('Added to Library')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('remove_from_library')
                    ->icon('heroicon-o-bookmark-slash')
                    ->color('danger')
                    ->visible(fn($record) => $record->is_in_library)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->removeFromLibrary();

                        Notification::make()
                            ->title('Removed from Library')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('generate_code')
                    ->icon('heroicon-o-code-bracket')
                    ->color('primary')
                    ->modalHeading('Generate Component Code')
                    ->modalDescription('Generate production-ready code for this component')
                    ->modalSubmitActionLabel('Generate')
                    ->form([
                        Forms\Components\Select::make('framework')
                            ->label('Framework')
                            ->options([
                                'react' => 'React + TypeScript',
                                'vue' => 'Vue 3 + TypeScript',
                                'html' => 'HTML + Tailwind CSS',
                            ])
                            ->default('react')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $service = app(\App\Services\CodeGenerationService::class);

                        try {
                            $result = $service->generateCode($record, $data['framework']);

                            // Copy to clipboard (would need JS integration)
                            Notification::make()
                                ->title('Code Generated!')
                                ->body("Generated {$result['filename']} - Copy the code below")
                                ->success()
                                ->duration(10000)
                                ->send();

                            // In production, show code in modal or download file
            
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Generation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('create_pr')
                    ->icon('heroicon-o-code-bracket-square')
                    ->color('success')
                    ->modalHeading('Create GitHub Pull Request')
                    ->modalDescription('Push this component to a GitHub repository')
                    ->form([
                        Forms\Components\Select::make('repository')
                            ->label('Repository')
                            ->options(function () {
                                try {
                                    $service = app(\App\Services\GitHubIntegrationService::class);
                                    $repos = $service->listRepositories();
                                    return collect($repos)->pluck('name', 'name');
                                } catch (\Exception $e) {
                                    return [];
                                }
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('branch')
                            ->label('Base Branch')
                            ->options(['main' => 'main', 'master' => 'master', 'develop' => 'develop'])
                            ->default('main')
                            ->required(),

                        Forms\Components\Select::make('framework')
                            ->label('Framework')
                            ->options([
                                'react' => 'React + TypeScript',
                                'vue' => 'Vue 3 + TypeScript',
                                'html' => 'HTML + Tailwind CSS',
                            ])
                            ->default('react')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            \App\Jobs\CreateComponentPRJob::dispatch(
                                $record,
                                $data['repository'],
                                $data['branch'],
                                $data['framework']
                            );

                            Notification::make()
                                ->title('PR Creation Started')
                                ->body('GitHub pull request is being created in the background')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('PR Creation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('add_to_library')
                        ->icon('heroicon-o-bookmark')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->addToLibrary();
                            }

                            Notification::make()
                                ->title('Added to Library')
                                ->body(count($records) . ' components added to library')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComponents::route('/'),
            'create' => Pages\CreateComponent::route('/create'),
            'view' => Pages\ViewComponent::route('/{record}'),
            'edit' => Pages\EditComponent::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::inLibrary()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
