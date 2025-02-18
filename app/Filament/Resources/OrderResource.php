<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\LogsRelationManager;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Support\Services\OrderService;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
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
                    ->label('العميل')
                    ->searchable()
                    ->dehydrated(false)
                    ->preload()
                    ->options(Customer::query()->pluck('name', 'id'))
                    ->live()
                    ->required(),
                Forms\Components\Select::make('machine_id')
                    ->label('الماكينة')
                    ->disabled(fn(Get $get) => !$get('customer_id'))
                    ->relationship(
                        'machine',
                        'serial_number',
                        fn(Get $get, Builder $query) => $query
                            ->where('serial_number', '<>', null)
                            ->when($get('customer_id'), fn($q) => $q->where('customer_id', $get('customer_id')))
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm(MachineResource::form($form)->getComponents()),
                Forms\Components\Select::make('branch_id')
                    ->label('الفرع')
                    ->default(request()->filled('customer_id') ? Customer::query()->find(request('customer_id'))?->branch_id : null)
                    ->relationship('branch', 'name')
                    ->required(),
                Forms\Components\RichEditor::make('description')
                    ->label('وصف الطلب')
                    ->required()
                    ->columnSpanFull(),
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
                            ]),
                    ]),
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
            Tables\Columns\TextColumn::make('machine.serial_number')
                ->placeholder('غير معروف')
                ->label('سيريال')
                ->copyable()
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('machine.machineModel.model')
                ->placeholder('غير معروف')
                ->label('موديل الماكينة')
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->multiple()
                    ->options(OrderService::TYPES),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('بيانات الطلب')
                ->schema([
                    TextEntry::make("user.name")
                        ->label('مُنشئ الطلب')
                        ->icon('heroicon-o-pencil')
                        ->iconColor(Color::Green),
                    TextEntry::make("branch.name")
                        ->label('الفرع')
                        ->icon('heroicon-o-tag')
                        ->iconColor(Color::Gray),
                    TextEntry::make("type")
                        ->label('نوع الطلب')
                        ->formatStateUsing(fn(string $state) => OrderService::TYPES[$state])
                        ->icon('heroicon-o-question-mark-circle')
                        ->iconColor(Color::Blue),
                    TextEntry::make("repairer_engineer.name")
                        ->label('موظف الصيانة')
                        ->default('غير معروف')
                        ->icon('heroicon-o-user')
                        ->iconColor(Color::Yellow),
                    TextEntry::make("deadline")
                        ->label('وقت الإنتهاء المُقدر')
                        ->default('غير معروف')
                        ->icon('heroicon-o-clock')
                        ->iconColor(Color::Red),
                    TextEntry::make("machine.serial_number")
                        ->default('غير معروف')
                        ->label('سيريال الماكينة')
                        ->icon('heroicon-o-hashtag')
                        ->iconColor(Color::Indigo),
                    TextEntry::make("machine.machineType.name")
                        ->default('غير معروف')
                        ->label('نوع الماكينة')
                        ->icon('heroicon-o-cog')
                        ->iconColor(Color::Purple),
                    TextEntry::make("machine.machineModel.model")
                        ->default('غير معروف')
                        ->label('موديل الماكينة')
                        ->icon('heroicon-o-numbered-list')
                        ->iconColor(Color::Teal),
                    TextEntry::make("machine.customer.name")
                        ->label('العميل')
                        ->hidden(fn() => auth()->user()->role == 'maintenance')
                        ->icon('heroicon-o-users')
                        ->iconColor(Color::Stone),
                    TextEntry::make("machine.customer.phone")
                        ->label('رقم هاتف العميل')
                        ->icon('heroicon-o-phone')
                        ->hidden(fn() => auth()->user()->role == 'maintenance')
                        ->iconColor(Color::Sky),
                    TextEntry::make('description')
                        ->label('الوصف')
                        ->html()
                ])
                ->columns(5),
            Section::make("ملحقات الماكينة")
                ->schema([
                    TextEntry::make('dorg')
                        ->icon(fn(bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->iconColor(fn(bool $state) => $state ? Color::Green : Color::Red)
                        ->formatStateUsing(fn(bool $state) => $state ? 'مرفق' : 'غير مرفق')
                        ->label('حبر'),
                    TextEntry::make('ink')
                        ->icon(fn(bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->iconColor(fn(bool $state) => $state ? Color::Green : Color::Red)
                        ->formatStateUsing(fn(bool $state) => $state ? 'مرفق' : 'غير مرفق')
                        ->label('مغناطيس'),
                    TextEntry::make('magnetic')
                        ->icon(fn(bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->iconColor(fn(bool $state) => $state ? Color::Green : Color::Red)
                        ->formatStateUsing(fn(bool $state) => $state ? 'مرفق' : 'غير مرفق')
                        ->label('دوبلكس'),
                    TextEntry::make('duplex')
                        ->icon(fn(bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->iconColor(fn(bool $state) => $state ? Color::Green : Color::Red)
                        ->formatStateUsing(fn(bool $state) => $state ? 'مرفق' : 'غير مرفق')
                        ->label('رف'),
                    TextEntry::make('shelf')
                        ->icon(fn(bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->iconColor(fn(bool $state) => $state ? Color::Green : Color::Red)
                        ->formatStateUsing(fn(bool $state) => $state ? 'مرفق' : 'غير مرفق')
                        ->label('درج'),
                ])
                ->columns(5),
            Section::make("تست الماكينة")
                ->schema([
                    Split::make([
                        Fieldset::make("تست قبل الصيانة")
                            ->schema(function (Order $order) {
                                if ($order->image_before) {
                                    $schema = [
                                        ImageEntry::make("image_before")
                                    ];
                                } else {
                                    $schema = [
                                        TextEntry::make("not_exists")
                                            ->default('لا يوجد صورة تست حتى الآن')
                                            ->label("")
                                    ];
                                }

                                return $schema;
                            }),
                        Fieldset::make("تست بعد الصيانة")
                            ->schema(function (Order $order) {
                                if ($order->image_after) {
                                    $schema = [
                                        ImageEntry::make("image_after")
                                    ];
                                } else {
                                    $schema = [
                                        TextEntry::make("not_exists")
                                            ->default('لا يوجد صورة تست حتى الآن')
                                            ->label("")
                                    ];
                                }

                                return $schema;
                            }),
                    ])
                        ->from('sm')
                ])
        ]);
    }

    public static function getRelations(): array
    {
        return [
            LogsRelationManager::class
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
