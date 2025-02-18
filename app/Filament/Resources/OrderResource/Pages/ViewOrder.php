<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Support\Services\OrderService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return in_array(auth()->user()->role, ['manager', 'data_entry', 'customer_support']) ? [
            OrderService::changeStatusAction(false),
            EditAction::make()
                ->url(route('filament.admin.resources.orders.edit', $this->record->id))
                ->icon('heroicon-o-pencil-square'),
            DeleteAction::make()
                ->after(function () {
                    return to_route('filament.admin.resources.orders.index');
                })
                ->icon('heroicon-o-trash'),
        ] : [OrderService::changeStatusAction(false)];
    }
}
