<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Order;
use App\Support\Services\OrderService;
use Filament\Actions\Contracts\HasRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewCustomer extends Page implements HasTable
{
    use InteractsWithRecord, InteractsWithTable;

    protected static string $resource = CustomerResource::class;

    protected static string $view = 'filament.resources.customer-resource.pages.view-customer';

    protected static ?string $title = 'عرض العميل';

    public function mount(Customer $record): void
    {
        $this->record = $this->resolveRecord($record->id);
    }

    public function table(Table $table): Table
    {
        return $table->query(Order::query()->whereHas('customer', fn($q) => $q->where('customer_id', $this->record->id))->latest())
            ->columns([
                TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(Order $order) => OrderService::STATUSES[$order->status])
                    ->color(fn(string $state): string => OrderService::colors($state))
                    ->label('الحالة'),
                TextColumn::make('deadline')
                    ->state(fn(Order $order) => $order->deadline ? $order->deadline->format('Y-m-d h:i A') : 'لا يوجد')
                    ->label('وقت الانتهاء')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->state(fn(Order $order) => $order->user?->name ?? 'غير معروف')
                    ->label('مٌنشئ الطلب')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])->actions([
                OrderService::changeStatusAction(),
                Action::make('show-details')
                    ->url(
                        fn(Order $order) => route('filament.admin.resources.orders.view', $order->id)
                    )
                    ->openUrlInNewTab()
                    ->label('عرض التفاصيل')
                    ->color('gray')
                    ->icon('heroicon-o-eye'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->url(route('filament.admin.resources.customers.edit', $this->record->id))
                ->icon('heroicon-o-pencil-square'),
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }
}
