<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['id'] = strtolower($data['id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->domains()->create([
            'domain' => $this->record->id,
        ]);
    }
}
