<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('app.filament.domains');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('domain')
                    ->label(__('app.filament.domain'))
                    ->required()
                    ->maxLength(255)
                    ->helperText(__('app.filament.domain_helper')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('domain')
            ->columns([
                TextColumn::make('domain')
                    ->label(__('app.filament.domain'))
                    ->searchable(),
                TextColumn::make('domain_type')
                    ->label(__('app.filament.type'))
                    ->getStateUsing(fn ($record): string => str_contains($record->domain, '.')
                        ? __('app.filament.custom_domain_type')
                        : __('app.filament.subdomain_type')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
