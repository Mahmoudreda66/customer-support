<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Branch;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (auth()->user()->role != 'maintenance') {
            $actions[] = Actions\CreateAction::make();
        }

        return $actions;
    }

    public function getTabs(): array
    {
        $tabs = [
            Tab::make('جميع الطلبات')
                ->badge(Order::query()->count()),
        ];

        foreach (Branch::query()->get() as $branch) {
            $tabs[] = Tab::make($branch->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('branch_id', $branch->id))
                ->badge(Order::query()->where('branch_id', $branch->id)->count());
        }

        return $tabs;
    }
}
