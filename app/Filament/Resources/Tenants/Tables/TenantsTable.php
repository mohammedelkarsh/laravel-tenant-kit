<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
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
                TextColumn::make('url')
                    ->label(__('app.filament.url'))
                    ->state(fn (Tenant $record): string => $record->id.'.'.config('app.central_domain'))
                    ->url(fn (Tenant $record): string => $record->url())
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
