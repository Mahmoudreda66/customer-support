<?php

namespace App\Filament\Support;

use App\Filament\Resources\OrderResource;
use App\Models\MachineType;
use App\Models\Order;
use App\Models\User;
use App\Support\Services\OrderService;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderTableBuilder
{
    /**
     * @return array<int, string>
     */
    private static function eagerLoadsForOrderList(): array
    {
        $relations = [
            'machine.machineType',
            'machine.machineModel',
            'latestWorkingLog.user',
        ];

        if (auth()->check() && auth()->user()->role !== 'maintenance') {
            $relations[] = 'machine.customer';
            $relations[] = 'branch';
            $relations[] = 'user';
        }

        return $relations;
    }

    /**
     * Same order list table as {@see OrderResource}, with an optional base query.
     *
     * @param  Builder|\Closure(): Builder  $query
     */
    public static function configure(Table $table, Builder|\Closure $query): Table
    {
        $columns = [
            Tables\Columns\TextColumn::make('id')
                ->label('#')
                ->searchable()
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('machine.serial_number')
                ->placeholder('غير معروف')
                ->label('سيريال')
                ->copyable()
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('machine.machineType.name')
                ->placeholder('غير معروف')
                ->label('نوع الماكينة')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('machine.machineModel.model')
                ->placeholder('غير معروف')
                ->label('موديل الماكينة')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('latestWorkingLog.user.name')
                ->placeholder('لم يتم الاستلام')
                ->label('موظف الصيانة'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('type')
                ->badge()
                ->formatStateUsing(fn (Order $order) => OrderService::TYPES[$order->type])
                ->color(fn (string $state): string => $state === 'preparation' ? 'info' : 'success')
                ->label('نوع الطلب'),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->formatStateUsing(fn (Order $order) => OrderService::STATUSES[$order->status])
                ->color(fn (string $state): string => OrderService::colors($state))
                ->label('الحالة'),
            Tables\Columns\TextColumn::make('deadline')
                ->state(fn (Order $order) => $order->deadline ? $order->deadline->format('Y-m-d h:i A') : 'لا يوجد')
                ->label('وقت الانتهاء')
                ->sortable(),
        ];

        if (auth()->user()->role != 'maintenance') {
            array_push(
                $columns,
                Tables\Columns\TextColumn::make('machine.customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->state(fn (Order $order) => $order->user?->name ?? 'غير معروف')
                    ->label('مٌنشئ الطلب')
                    ->numeric()
                    ->sortable()
            );
        }

        $tableData = $table
            ->query($query)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(self::eagerLoadsForOrderList()))
            ->deferLoading()
            ->columns($columns)
            ->defaultSort('id', 'DESC')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->multiple()
                    ->options(OrderService::STATUSES),
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->multiple()
                    ->options(OrderService::TYPES),
                Tables\Filters\SelectFilter::make('machine_type_id')
                    ->label('نوع الماكينة')
                    ->searchable()
                    ->preload()
                    ->options(MachineType::query()->orderBy('name')->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value']),
                            fn (Builder $query): Builder => $query->whereHas(
                                'machine',
                                fn (Builder $q) => $q->where('machine_type_id', $data['value'])
                            ),
                        );
                    }),
                Tables\Filters\SelectFilter::make('maintenance_engineer_id')
                    ->label('مهندس الصيانة')
                    ->searchable()
                    ->preload()
                    ->options(
                        User::query()
                            ->where('role', 'maintenance')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value']),
                            fn (Builder $query): Builder => $query->whereHas('logs', function (Builder $logQuery) use ($data) {
                                $logQuery->where('user_id', $data['value'])
                                    ->whereRaw("JSON_EXTRACT(data, '$.status') = 'working'");
                            }),
                        );
                    }),
                Tables\Filters\SelectFilter::make('creator_user_id')
                    ->label('منشئ الطلب')
                    ->searchable()
                    ->preload()
                    ->options(
                        User::query()
                            ->where(function (Builder $q) {
                                $q->whereNull('role')->orWhere('role', '<>', 'maintenance');
                            })
                            ->orderBy('name')
                            ->pluck('name', 'id')
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value']),
                            fn (Builder $query): Builder => $query->where('user_id', $data['value']),
                        );
                    }),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('to')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                OrderService::changeStatusAction(),
            ]);

        if (auth()->user()->role != 'maintenance') {
            $tableData
                ->actions([
                    OrderService::changeStatusAction(),
                    Tables\Actions\ViewAction::make()
                        ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
                    Tables\Actions\EditAction::make()
                        ->url(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record])),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        return $tableData;
    }
}
