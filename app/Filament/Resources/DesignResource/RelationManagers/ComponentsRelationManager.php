<?php

namespace App\Filament\Resources\DesignResource\RelationManagers;

use App\Models\Component;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'components';

    protected static ?string $title = 'Detected Components';

    protected static ?string $icon = 'heroicon-o-squares-2x2';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('type')
                    ->options([
                        'button' => 'Button',
                        'input' => 'Input',
                        'card' => 'Card',
                        'navigation' => 'Navigation',
                        'modal' => 'Modal',
                        'form' => 'Form',
                        'other' => 'Other',
                    ])
                    ->required(),

                Forms\Components\Select::make('category')
                    ->options([
                        'navigation' => 'Navigation',
                        'form' => 'Form',
                        'layout' => 'Layout',
                        'overlay' => 'Overlay',
                        'typography' => 'Typography',
                        'media' => 'Media',
                        'other' => 'Other',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->rows(3),

                Forms\Components\KeyValue::make('properties')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'button',
                        'success' => 'input',
                        'warning' => 'card',
                        'info' => 'navigation',
                    ])
                    ->searchable(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->searchable(),

                Tables\Columns\IconColumn::make('in_library')
                    ->label('Library')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Usage')
                    ->sortable(),

                Tables\Columns\TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'button' => 'Button',
                        'input' => 'Input',
                        'card' => 'Card',
                        'navigation' => 'Navigation',
                        'modal' => 'Modal',
                        'form' => 'Form',
                        'other' => 'Other',
                    ]),

                Tables\Filters\TernaryFilter::make('in_library')
                    ->label('In Library')
                    ->placeholder('All components')
                    ->trueLabel('Library components')
                    ->falseLabel('Not in library'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('detect_components')
                    ->label('Re-detect Components')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function () {
                        \App\Jobs\DetectComponentsJob::dispatch($this->getOwnerRecord());

                        \Filament\Notifications\Notification::make()
                            ->title('Component Detection Started')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('add_to_library')
                    ->icon('heroicon-o-bookmark')
                    ->color('success')
                    ->action(fn(Component $record) => $record->addToLibrary())
                    ->visible(fn(Component $record) => !$record->in_library)
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('remove_from_library')
                    ->icon('heroicon-o-bookmark-slash')
                    ->color('danger')
                    ->action(fn(Component $record) => $record->update(['in_library' => false]))
                    ->visible(fn(Component $record) => $record->in_library)
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('generate_code')
                    ->icon('heroicon-o-code-bracket')
                    ->color('info')
                    ->url(fn(Component $record) => \App\Filament\Resources\ComponentResource::getUrl('view', ['record' => $record])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('add_to_library')
                        ->icon('heroicon-o-bookmark')
                        ->color('success')
                        ->action(fn($records) => $records->each->addToLibrary())
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
