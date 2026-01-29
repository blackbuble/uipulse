<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiAnalysisResource\Pages;
use App\Filament\Resources\AiAnalysisResource\RelationManagers;
use App\Models\AiAnalysis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AiAnalysisResource extends Resource
{
    protected static ?string $model = AiAnalysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Intelligence';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('design_id')
                            ->relationship('design', 'name')
                            ->required(),
                        \Filament\Forms\Components\Select::make('type')
                            ->options([
                                'accessibility' => 'Accessibility Audit',
                                'responsiveness' => 'Responsiveness Check',
                                'visual_polish' => 'Visual Polish & WoW Factor',
                                'code_gen' => 'Code Generation',
                                'visual_mockup' => 'Visual Mockup (DALL-E)',
                            ])
                            ->required(),
                        \Filament\Forms\Components\Select::make('provider')
                            ->options(fn() => collect(app(\App\Settings\AiSettings::class)->providers)->pluck('name', 'id'))
                            ->reactive()
                            ->afterStateUpdated(
                                fn($state, callable $set) =>
                                $set('model_name', collect(app(\App\Settings\AiSettings::class)->providers)->firstWhere('id', $state)['model'] ?? null)
                            )
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('model_name')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Textarea::make('prompt')
                            ->columnSpanFull()
                            ->disabled(),

                        Forms\Components\Section::make('Analysis Results')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('results.score')
                                            ->numeric()
                                            ->suffix('%')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('results.summary')
                                            ->disabled(),
                                    ]),
                                Forms\Components\Repeater::make('results.findings')
                                    ->schema([
                                        Forms\Components\Select::make('severity')
                                            ->options([
                                                'high' => 'High',
                                                'medium' => 'Medium',
                                                'low' => 'Low',
                                            ])
                                            ->disabled(),
                                        Forms\Components\TextInput::make('issue')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('recommendation')
                                            ->disabled(),
                                    ])
                                    ->columns(3)
                                    ->disabled()
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false),
                                Forms\Components\TextInput::make('results.wow_factor')
                                    ->label('The WOW Factor')
                                    ->disabled(),
                            ])
                            ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Analysis Overview')
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(3)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('type')
                                    ->badge()
                                    ->color('primary'),
                                \Filament\Infolists\Components\TextEntry::make('results.score')
                                    ->label('Score')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->color(fn($state) => $state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger'))
                                    ->suffix('%'),
                                \Filament\Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'pending' => 'gray',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                    }),
                            ]),
                        \Filament\Infolists\Components\TextEntry::make('results.summary')
                            ->label('Summary')
                            ->prose(),
                    ]),

                \Filament\Infolists\Components\Section::make('Findings & Recommendations')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('results.findings')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('severity')
                                    ->label('Severity')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'high' => 'danger',
                                        'medium' => 'warning',
                                        'low' => 'info',
                                        default => 'gray',
                                    }),
                                \Filament\Infolists\Components\TextEntry::make('issue')
                                    ->label('Issue Identified'),
                                \Filament\Infolists\Components\TextEntry::make('recommendation')
                                    ->label('Fix Recommendation')
                                    ->color('success'),
                            ])->columns(3),
                    ]),

                \Filament\Infolists\Components\Section::make('The WOW Factor')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('results.wow_factor')
                            ->label('Creative Suggestion')
                            ->color('warning')
                            ->icon('heroicon-m-sparkles'),
                    ])->collapsible(),

                \Filament\Infolists\Components\Section::make('Generated Visual Mockup')
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('results.generated_image_url')
                            ->label('AI Suggested Design Fix')
                            ->disk('public') // In case of local storage, for now URL
                            ->extraImgAttributes(['class' => 'rounded-xl shadow-lg w-full h-auto']),
                    ])
                    ->visible(fn($record) => $record->type === 'visual_mockup'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('design.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'completed' => 'success',
                        'failed' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('results.score')
                    ->label('Score')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn(AiAnalysis $record) => route('ai-analysis.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListAiAnalyses::route('/'),
            'create' => Pages\CreateAiAnalysis::route('/create'),
            'view' => Pages\ViewAiAnalysis::route('/{record}'),
            'edit' => Pages\EditAiAnalysis::route('/{record}/edit'),
        ];
    }
}
