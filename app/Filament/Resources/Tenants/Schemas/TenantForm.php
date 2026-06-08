<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')
                    ->label('Subdomain')
                    ->required()
                    ->alphaDash()
                    ->minLength(3)
                    ->maxLength(63)
                    ->unique(ignoreRecord: true)
                    ->helperText(fn (?string $state): string => $state
                        ? 'Workspace URL: http://'.$state.'.'.config('app.central_domain')
                        : 'Used as the tenant ID and workspace URL prefix.'),
                TextInput::make('name')
                    ->label('Workspace name')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
