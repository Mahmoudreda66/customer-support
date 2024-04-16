<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Branch;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        foreach (Branch::query()->get() as $branch) {
            $tabs[] = Tab::make($branch->name)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('branch_id', $branch->id))
                ->badge(Customer::query()->where('branch_id', $branch->id)->count());
        }

        return $tabs;
    }
}
