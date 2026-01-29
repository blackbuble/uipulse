<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DesignsRelationManager extends RelationManager
{
    protected static string $relationship = 'designs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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

                        if (preg_match('/(file|design|proto)\/([a-zA-Z0-9]+)/', $state, $matches)) {
                            $set('figma_file_key', $matches[2]);
                        }

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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function (\App\Models\Design $record, array $data) {
                        if ($data['auto_analyze'] ?? false) {
                            $settings = app(\App\Settings\AiSettings::class);
                            \App\Jobs\ProcessDesignAnalysis::dispatch(
                                $record,
                                'accessibility',
                                $settings->default_provider,
                                [
                                    'depth' => 'standard',
                                    'focus_areas' => ['typography', 'colors', 'accessibility', 'ux_logic', 'consistency'],
                                    'custom_context' => 'Automatic audit triggered during design creation from Project.',
                                ]
                            );
                        }
                    })
                    ->successRedirectUrl(fn(array $data) => ($data['auto_analyze'] ?? false) ? \App\Filament\Resources\AiAnalysisResource::getUrl('index') : null),
            ])
            ->actions([
                Tables\Actions\Action::make('analyze')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('warning')
                    ->requiresConfirmation()
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
                            ->rows(3),
                    ])
                    ->action(function (\App\Models\Design $record, array $data) {
                        \App\Jobs\ProcessDesignAnalysis::dispatch($record, $data['type'], $data['provider'], [
                            'depth' => $data['depth'],
                            'focus_areas' => $data['focus_areas'],
                            'custom_context' => $data['custom_context'],
                        ]);
                        \Filament\Notifications\Notification::make()->title('Analysis Started')->success()->send();
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
                                ->filter(fn($p) => !empty($p['supports_generation']))
                                ->pluck('name', 'id'))
                            ->default(fn() => collect(app(\App\Settings\AiSettings::class)->providers)
                                ->firstWhere('supports_generation', true)['id'] ?? 'openai')
                            ->required(),
                    ])
                    ->action(function (\App\Models\Design $record, array $data, \App\Services\AiService $aiService) {
                        try {
                            $aiService->generateImageSuggestion($record, $data['prompt'], $data['provider']);
                            \Filament\Notifications\Notification::make()->title('Mockup Generated')->success()->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()->title('Generation Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('quick_audit')
                    ->label('Quick Audit')
                    ->icon('heroicon-m-bolt')
                    ->color('info')
                    ->action(function (\App\Models\Design $record) {
                        $settings = app(\App\Settings\AiSettings::class);
                        \App\Jobs\ProcessDesignAnalysis::dispatch($record, 'accessibility', $settings->default_provider, [
                            'depth' => 'standard',
                            'focus_areas' => ['typography', 'colors', 'accessibility', 'ux_logic', 'consistency'],
                            'custom_context' => 'Quick automated audit from Project view.',
                        ]);
                        return redirect(\App\Filament\Resources\AiAnalysisResource::getUrl('index'));
                    }),
                Tables\Actions\EditAction::make()
                    ->after(function (\App\Models\Design $record, array $data) {
                        if ($data['auto_analyze'] ?? false) {
                            $settings = app(\App\Settings\AiSettings::class);
                            \App\Jobs\ProcessDesignAnalysis::dispatch($record, 'accessibility', $settings->default_provider, [
                                'depth' => 'standard',
                                'focus_areas' => ['typography', 'colors', 'accessibility', 'ux_logic', 'consistency'],
                                'custom_context' => 'Automatic audit triggered during design update from Project.',
                            ]);
                        }
                    })
                    ->successRedirectUrl(fn(array $data) => ($data['auto_analyze'] ?? false) ? \App\Filament\Resources\AiAnalysisResource::getUrl('index') : null),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
