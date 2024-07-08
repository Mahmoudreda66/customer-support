<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Customer;
use App\Models\MachineModel;
use App\Models\Order;
use App\Models\User;
use App\Support\Services\OrderService;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'الطلبات';

    protected static ?string $label = 'طلب';

    protected static ?string $pluralLabel = 'الطلبات';

    protected static ?string $navigationGroup = 'الدعم الفني';

    protected static ?int $navigationSort = -2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->searchable()
                    ->preload()
                    ->default(request('customer_id'))
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->required(),
                Forms\Components\Select::make('branch_id')
                    ->label('الفرع')
                    ->default(request()->filled('customer_id') ? Customer::query()->find(request('customer_id'))?->branch_id : null)
                    ->relationship('branch', 'name')
                    ->required(),
                Forms\Components\RichEditor::make('description')
                    ->label('وصف الطلب')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Fieldset::make('بيانات الماكينة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('machine_type_id')
                                    ->searchable()
                                    ->nullable()
                                    ->relationship('machineType', 'name')
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->label('نوع الماكينة'),
                                Forms\Components\Select::make('machine_model_id')
                                    ->searchable()
                                    ->options(fn(Forms\Get $get) => MachineModel::query()->where('machine_type_id', $get('machine_type_id'))->pluck('model', 'id'))
                                    ->nullable()
                                    ->required()
                                    ->preload()
                                    ->label('موديل الماكينة'),
                            ]),
                    ]),
                Forms\Components\Select::make('type')
                    ->required()
                    ->label('نوع الطلب')
                    ->options(OrderService::TYPES),
                Forms\Components\DateTimePicker::make('deadline')
                    ->label('وقت الانتهاء الأقصى / Deadline'),
                Forms\Components\Fieldset::make('ملحقات الماكينة')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Checkbox::make('dorg')
                                    ->label('درج'),
                                Forms\Components\Checkbox::make('ink')
                                    ->label('حبر'),
                                Forms\Components\Checkbox::make('magnetic')
                                    ->label('مغناطيس'),
                                Forms\Components\Checkbox::make('duplex')
                                    ->label('دوبلكس'),
                                Forms\Components\Checkbox::make('shelf')
                                    ->label('رف'),
                            ])
                    ])
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        $columns = [
            Tables\Columns\TextColumn::make('id')
                ->label('#')
                ->searchable()
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('serial_number')
                ->placeholder('غير معروف')
                ->label('سيريال')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('repairer_name')
                ->state(fn(Order $order) => $order->repairer_engineer?->name)
                ->placeholder('لم يتم الاستلام')
                ->label('موظف الصيانة'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
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
            Tables\Columns\TextColumn::make('deadline')
                ->state(fn(Order $order) => $order->deadline ? $order->deadline->format('Y-m-d h:i A') : 'لا يوجد')
                ->label('وقت الانتهاء')
                ->sortable(),
        ];

        if (auth()->user()->role != 'maintenance') {
            array_push(
                $columns,
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->state(fn(Order $order) => $order->user?->name ?? 'غير معروف')
                    ->label('مٌنشئ الطلب')
                    ->numeric()
                    ->sortable()
            );
        }

        $tableData = $table
            ->query(Order::query()->when(auth()->user()->role === 'maintenance', fn($q) => $q->whereIn('status', ['created', 'working', 'pending'])))
            ->columns($columns)
            ->defaultSort('id', 'DESC')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->multiple()
                    ->options(OrderService::STATUSES),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('المستخدم')
                    ->searchable()
                    ->options(User::query()->where('role', 'maintenance')->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        $query->when($data['value'], function ($builder) use ($data) {
                            $builder->whereHas('logs', function (Builder $query) use ($data) {
                                $query->where('user_id', $data)
                                    ->whereRaw("JSON_EXTRACT(data, '$.status') = 'working'");
                            });
                        });
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
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
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
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
