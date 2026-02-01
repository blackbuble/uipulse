<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DesignVersionResource\Pages;
use App\Models\DesignVersion;
use App\Services\VersionControlService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class DesignVersionResource extends Resource
{
    protected static ?string $model = DesignVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Design System';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Versions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Version Information')
                    ->schema([
                        Forms\Components\TextInput::make('version_name')
                            ->label('Version Name')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('tags')
                            ->placeholder('Add tags (milestone, approved, etc.)')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Snapshot Data')
                    ->schema([
                        Forms\Components\KeyValue::make('snapshot')
                            ->label('Design Snapshot')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version_number')
                    ->label('Version')
                    ->formatStateUsing(fn($state) => "v{$state}")
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('version_name')
                    ->label('Name')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('design.name')
                    ->label('Design')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TagsColumn::make('tags')
                    ->separator(',')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_auto_version')
                    ->label('Auto')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->placeholder('System')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('design_id')
                    ->relationship('design', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_auto_version')
                    ->label('Version Type')
                    ->placeholder('All versions')
                    ->trueLabel('Auto versions')
                    ->falseLabel('Manual versions'),

                Tables\Filters\SelectFilter::make('tags')
                    ->options([
                        'milestone' => 'Milestone',
                        'approved' => 'Approved',
                        'production' => 'Production',
                        'rollback' => 'Rollback',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\Action::make('rollback')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Rollback to this version?')
                    ->modalDescription(fn($record) => "This will restore the design to version {$record->version_number}. A new version will be created to mark this rollback.")
                    ->action(function ($record) {
                        $service = app(VersionControlService::class);

                        try {
                            $service->rollback($record->design, $record);

                            Notification::make()
                                ->title('Rollback Successful')
                                ->body("Design rolled back to version {$record->version_number}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Rollback Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('compare')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->modalHeading('Compare Versions')
                    ->form([
                        Forms\Components\Select::make('compare_with')
                            ->label('Compare with version')
                            ->options(function ($record) {
                                return DesignVersion::where('design_id', $record->design_id)
                                    ->where('id', '!=', $record->id)
                                    ->orderBy('version_number', 'desc')
                                    ->get()
                                    ->pluck('version_string', 'id');
                            })
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $compareVersion = DesignVersion::find($data['compare_with']);
                        $service = app(VersionControlService::class);

                        $comparison = $service->compareVersions($record, $compareVersion);

                        Notification::make()
                            ->title('Version Comparison')
                            ->body("Found {$comparison['summary']['components_diff']} component changes")
                            ->info()
                            ->duration(10000)
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListDesignVersions::route('/'),
            'view' => Pages\ViewDesignVersion::route('/{record}'),
            'edit' => Pages\EditDesignVersion::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::manual()->count();
    }
}
