<?php

namespace App\Filament\Workspace\Resources\KycVerifications;

use App\Filament\Workspace\Resources\KycVerifications\Pages\ListKycVerifications;
use App\Filament\Workspace\Resources\KycVerifications\Tables\KycVerificationsTable;
use App\Support\Kyc;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KycVerificationResource extends Resource
{
    protected static ?string $model = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'KYC';

    public static function getModel(): string
    {
        return \KycAi\Laravel\Models\KycVerification::class;
    }

    public static function getNavigationLabel(): string
    {
        return __('app.kyc.verifications');
    }

    public static function getModelLabel(): string
    {
        return __('app.kyc.verification');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.kyc.verifications');
    }

    public static function canAccess(): bool
    {
        if (! Kyc::ready()) {
            return false;
        }

        $user = auth()->user();

        return $user !== null && $user->hasAnyRole(['owner', 'admin']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return KycVerificationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKycVerifications::route('/'),
        ];
    }
}
