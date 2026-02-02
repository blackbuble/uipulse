<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DesignResource\Pages;
use App\Filament\Resources\DesignResource\RelationManagers;
use App\Models\Design;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DesignResource extends Resource
{
    protected static ?string $model = Design::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Design Hub';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('figma_url')
                            ->url()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $state, callable $set) {
                                if (empty($state))
                                    return;

                                // Parse File Key
                                // https://www.figma.com/file/FILE_KEY/name...
                                // https://www.figma.com/design/FILE_KEY/name...
                                // https://www.figma.com/proto/FILE_KEY/name...
                                if (preg_match('/(file|design|proto)\/([a-zA-Z0-9]+)/', $state, $matches)) {
                                    $set('figma_file_key', $matches[2]);
                                }

                                // Parse Node ID
                                // ...?node-id=123-456
                                $query = parse_url($state, PHP_URL_QUERY);
                                if ($query) {
                                    parse_str($query, $params);
                                    if (isset($params['node-id'])) {
                                        $set('figma_node_id', $params['node-id']);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('figma_file_key')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('figma_node_id')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\KeyValue::make('metadata')
                            ->default([
                                'platform' => 'web',
                                'viewport' => '1440x900',
                                'theme' => 'light',
                                'framework' => 'react',
                                'complexity' => 'medium',
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('auto_analyze')
                            ->label('Auto-analyze after saving')
                            ->helperText('Trigger a quick audit automatically as soon as this design is saved.')
                            ->default(true)
                            ->dehydrated(false),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('ai_analyses_count')
                    ->counts('aiAnalyses')
                    ->label('Analyses'),
                Tables\Columns\TextColumn::make('components_count')
                    ->counts('components')
                    ->label('Components')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('analyze')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Analyze Design')
                    ->modalDescription('Specify the type of AI analysis to run on this design.')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->options([
                                'accessibility' => 'Accessibility Audit',
                                'responsiveness' => 'Responsiveness Check',
                                'visual_polish' => 'Visual Polish & WoW Factor',
                                'code_gen' => 'Code Generation',
                            ])
                            ->default('accessibility')
                            ->required(),
                        Forms\Components\Select::make('provider')
                            ->options(fn() => collect(app(\App\Settings\AiSettings::class)->providers)->pluck('name', 'id'))
                            ->default(fn() => app(\App\Settings\AiSettings::class)->default_provider)
                            ->required(),
                        Forms\Components\Select::make('depth')
                            ->options([
                                'standard' => 'Standard (Quick Audit)',
                                'deep_dive' => 'Deep Dive (Exhaustive Analysis)',
                            ])
                            ->default('standard')
                            ->required(),
                        Forms\Components\CheckboxList::make('focus_areas')
                            ->options([
                                'typography' => 'Typography & Hierarchy',
                                'colors' => 'Colors & Branding',
                                'accessibility' => 'Accessibility & Inclusivity',
                                'ux_logic' => 'UX Logic & Flow',
                                'consistency' => 'Design System Consistency',
                            ])
                            ->columns(2),
                        Forms\Components\Textarea::make('custom_context')
                            ->label('Custom Instructions')
                            ->placeholder('e.g. Focus on conversion or mobile-first approach...')
                            ->rows(3),
                    ])
                    ->action(function (Design $record, array $data) {
                        \App\Jobs\ProcessDesignAnalysis::dispatch(
                            $record,
                            $data['type'],
                            $data['provider'],
                            [
                                'depth' => $data['depth'],
                                'focus_areas' => $data['focus_areas'],
                                'custom_context' => $data['custom_context'],
                            ]
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Analysis Started')
                            ->body("The AI analysis job targeting {$data['provider']} has been dispatched.")
                            ->success()
                            ->send();

                        return redirect(\App\Filament\Resources\AiAnalysisResource::getUrl('index'));
                    }),
                Tables\Actions\Action::make('generate_visual_fix')
                    ->label('Generate Visual Fix')
                    ->icon('heroicon-m-photo')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('prompt')
                            ->label('What should the AI fix visually?')
                            ->placeholder('e.g. Make the hero section more exciting and modern')
                            ->required(),
                        Forms\Components\Select::make('provider')
                            ->options(fn() => collect(app(\App\Settings\AiSettings::class)->providers)
                                ->filter(fn($p) => !empty($p['supports_generation'])) // Dynamic check
                                ->pluck('name', 'id'))
                            ->default(fn() => collect(app(\App\Settings\AiSettings::class)->providers)
                                ->firstWhere('supports_generation', true)['id'] ?? 'openai')
                            ->required(),
                    ])
                    ->action(function (Design $record, array $data, \App\Services\AiService $aiService) {
                        try {
                            $aiService->generateImageSuggestion($record, $data['prompt'], $data['provider']);

                            \Filament\Notifications\Notification::make()
                                ->title('Mockup Generated')
                                ->body('The visual AI mockup has been created and is available in Analyses.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Generation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('quick_audit')
                    ->label('Quick Audit')
                    ->icon('heroicon-m-bolt')
                    ->color('info')
                    ->tooltip('Run full audit with default settings in one click')
                    ->action(function (Design $record) {
                        $settings = app(\App\Settings\AiSettings::class);

                        \App\Jobs\ProcessDesignAnalysis::dispatch(
                            $record,
                            'accessibility',
                            $settings->default_provider,
                            [
                                'depth' => 'standard',
                                'focus_areas' => ['typography', 'colors', 'accessibility', 'ux_logic', 'consistency'],
                                'custom_context' => 'Quick automated audit.',
                            ]
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Quick Audit Started')
                            ->success()
                            ->send();

                        return redirect(\App\Filament\Resources\AiAnalysisResource::getUrl('index'));
                    }),

                Tables\Actions\Action::make('detect_components')
                    ->label('Detect Components')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('info')
                    ->action(function (Design $record) {
                        \App\Jobs\DetectComponentsJob::dispatch($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Component Detection Started')
                            ->body('AI is analyzing the design to detect components')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Design $record) => $record->status === 'completed'),

                Tables\Actions\Action::make('view_components')
                    ->label('View Components')
                    ->icon('heroicon-o-cube')
                    ->color('success')
                    ->url(fn(Design $record) => \App\Filament\Resources\ComponentResource::getUrl('index', [
                        'tableFilters' => [
                            'design_id' => ['value' => $record->id],
                        ],
                    ]))
                    ->visible(fn(Design $record) => $record->components()->count() > 0)
                    ->badge(fn(Design $record) => $record->components()->count()),

                Tables\Actions\EditAction::make(),
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
            RelationManagers\ComponentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDesigns::route('/'),
            'create' => Pages\CreateDesign::route('/create'),
            'edit' => Pages\EditDesign::route('/{record}/edit'),
        ];
    }
}
