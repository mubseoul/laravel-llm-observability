<?php

namespace Mubseoul\LLMObservability\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Mubseoul\LLMObservability\Models\LLMRequest;

class LLMRequestResource extends Resource
{
    protected static ?string $model = LLMRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'LLM Requests';

    protected static ?string $navigationGroup = 'LLM Observability';

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_id')
                    ->label('Request ID')
                    ->searchable()
                    ->copyable()
                    ->limit(8),
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_tokens')
                    ->label('Tokens')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_usd')
                    ->label('Cost (USD)')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('latency_ms')
                    ->label('Latency (ms)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'success',
                        'danger' => 'error',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'openai' => 'OpenAI',
                        'anthropic' => 'Anthropic',
                        'ollama' => 'Ollama',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'error' => 'Error',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \Mubseoul\LLMObservability\Filament\Resources\LLMRequestResource\Pages\ListLLMRequests::class,
            'view' => \Mubseoul\LLMObservability\Filament\Resources\LLMRequestResource\Pages\ViewLLMRequest::class,
        ];
    }
}
