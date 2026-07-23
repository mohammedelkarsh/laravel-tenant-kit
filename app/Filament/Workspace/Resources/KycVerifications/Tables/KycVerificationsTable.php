<?php

namespace App\Filament\Workspace\Resources\KycVerifications\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use KycAi\Laravel\Models\KycVerification;

class KycVerificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')->searchable()->copyable(),
                TextColumn::make('country')->badge(),
                TextColumn::make('national_id')->searchable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('passed')->boolean(),
                TextColumn::make('confidence'),
                TextColumn::make('extraction_driver'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending_review' => 'Pending review',
                        'passed' => 'Passed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->visible(fn (KycVerification $record): bool => $record->status === 'pending_review')
                    ->requiresConfirmation()
                    ->action(fn (KycVerification $record) => $record->markReviewed((int) (auth()->id() ?? 0), true)),
                Action::make('reject')
                    ->color('danger')
                    ->visible(fn (KycVerification $record): bool => $record->status === 'pending_review')
                    ->requiresConfirmation()
                    ->action(fn (KycVerification $record) => $record->markReviewed((int) (auth()->id() ?? 0), false)),
            ]);
    }
}
