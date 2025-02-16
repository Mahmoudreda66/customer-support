<?php

namespace App\Filament\Resources\MachineResource\RelationManagers;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Support\Services\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'الطلبات';

    public function form(Form $form): Form
    {
        return $form
            ->schema(OrderResource::form($form)->getColumns());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->html(),
                Tables\Columns\TextColumn::make('repairer_name')
                    ->state(fn(Order $order) => $order->repairer_engineer?->name)
                    ->placeholder('لم يتم الاستلام')
                    ->label('موظف الصيانة'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn(Order $order) => OrderService::TYPES[$order->type])
                    ->color(fn(string $state): string => $state === 'preparation' ? 'info' : 'success')
                    ->label('نوع الطلب'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(Order $order) => OrderService::STATUSES[$order->status])
                    ->color(fn(string $state): string => OrderService::colors($state))
                    ->label('الحالة'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('show_machine')
                    ->label('عرض الماكينة')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Order $order) => route('filament.admin.resources.orders.view', $order->id)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
