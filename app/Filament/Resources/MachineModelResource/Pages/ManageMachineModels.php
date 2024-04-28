<?php

namespace App\Filament\Resources\MachineModelResource\Pages;

use App\Filament\Resources\MachineModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMachineModels extends ManageRecords
{
    protected static string $resource = MachineModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
