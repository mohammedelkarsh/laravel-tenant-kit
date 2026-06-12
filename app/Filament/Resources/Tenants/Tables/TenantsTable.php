<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('app.filament.subdomain'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label(__('app.filament.workspace'))
                    ->searchable(),
                IconColumn::make('suspended_at')
                    ->label(__('app.filament.status'))
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedPauseCircle)
                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->getStateUsing(fn (Tenant $record): bool => $record->isSuspended()),
                TextColumn::make('workspace_url')
                    ->label(__('app.filament.url'))
                    ->state(fn (Tenant $record): string => $record->url())
                    ->url(fn (Tenant $record): string => $record->url())
                    ->copyable()
                    ->openUrlInNewTab()
                    ->color('primary'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('app.filament.open'))
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->url(fn (Tenant $record): string => $record->url())
                    ->openUrlInNewTab(),
                Action::make('suspend')
                    ->label(__('app.filament.suspend'))
                    ->icon(Heroicon::OutlinedPauseCircle)
                    ->color('warning')
                    ->visible(fn (Tenant $record): bool => ! $record->isSuspended())
                    ->requiresConfirmation()
                    ->action(fn (Tenant $record) => $record->update(['suspended_at' => now()])),
                Action::make('unsuspend')
                    ->label(__('app.filament.unsuspend'))
                    ->icon(Heroicon::OutlinedPlayCircle)
                    ->color('success')
                    ->visible(fn (Tenant $record): bool => $record->isSuspended())
                    ->action(fn (Tenant $record) => $record->update(['suspended_at' => null])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
