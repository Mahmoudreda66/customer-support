<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\LogsRelationManager;
use App\Filament\Support\OrderTableBuilder;
use App\Models\Customer;
use App\Models\Order;
use App\Support\Services\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
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
                    ->disabled(fn (Get $get) => ! $get('customer_id'))
                    ->relationship(
                        'machine',
                        'serial_number',
                        fn (Get $get, Builder $query) => $query
                            ->where('serial_number', '<>', null)
                            ->when($get('customer_id'), fn ($q) => $q->where('customer_id', $get('customer_id')))
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

    public static function table(Table $table): Table
    {
        return OrderTableBuilder::configure(
            $table,
            Order::listQueryForAuthenticatedUser()
        );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('بيانات الطلب')
                ->schema([
                    TextEntry::make('id')
                        ->label('رقم الطلب')
                        ->icon('heroicon-o-hashtag')
                        ->iconColor(Color::Gray),
                    TextEntry::make('user.name')
                        ->label('مُنشئ الطلب')
                        ->icon('heroicon-o-pencil')
                        ->iconColor(Color::Green),
                    TextEntry::make('branch.name')
                        ->label('الفرع')
                        ->icon('heroicon-o-tag')
                        ->iconColor(Color::Gray),
                    TextEntry::make('type')
                        ->label('نوع الطلب')
                        ->formatStateUsing(fn (string $state) => OrderService::TYPES[$state])
                        ->icon('heroicon-o-question-mark-circle')
                        ->iconColor(Color::Blue),
                    TextEntry::make('repairer_engineer.name')
                        ->label('موظف الصيانة')
                        ->default('غير معروف')
                        ->icon('heroicon-o-user')
                        ->iconColor(Color::Yellow),
                    TextEntry::make('deadline')
                        ->label('وقت الإنتهاء المُقدر')
                        ->default('غير معروف')
                        ->icon('heroicon-o-clock')
                        ->iconColor(Color::Red),
                    TextEntry::make('machine.serial_number')
                        ->default('غير معروف')
                        ->label('سيريال الماكينة')
                        ->icon('heroicon-o-hashtag')
                        ->iconColor(Color::Indigo),
                    TextEntry::make('machine.machineType.name')
                        ->default('غير معروف')
                        ->label('نوع الماكينة')
                        ->icon('heroicon-o-cog')
                        ->iconColor(Color::Purple),
                    TextEntry::make('machine.machineModel.model')
                        ->default('غير معروف')
                        ->label('موديل الماكينة')
                        ->icon('heroicon-o-numbered-list')
                        ->iconColor(Color::Teal),
                    TextEntry::make('machine.customer.name')
                        ->label('العميل')
                        ->hidden(fn () => auth()->user()->role == 'maintenance')
                        ->icon('heroicon-o-users')
                        ->iconColor(Color::Stone),
                    TextEntry::make('machine.customer.phone')
                        ->label('رقم هاتف العميل')
                        ->icon('heroicon-o-phone')
                        ->hidden(fn () => auth()->user()->role == 'maintenance')
                        ->iconColor(Color::Sky),
                    TextEntry::make('description')
                        ->label('الوصف')
                        ->html(),
                ])
                ->columns(5),
            Section::make('ملحقات الماكينة')
                ->schema([
                    TextEntry::make('dorg')
                        ->icon(fn (bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->iconColor(fn (bool $state) => $state ? Color::Green : Color::Red)
                        ->formatStateUsing(fn (bool $state) => $state ? 'مرفق' : 'غير مرفق')
                        ->label('حبر'),
                    TextEntry::make('ink')
                        ->icon(fn (bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->iconColor(fn (bool $state) => $state ? Color::Green : Color::Red)
                        ->formatStateUsing(fn (bool $state) => $state ? 'مرفق' : 'غير مرفق')
                        ->label('مغناطيس'),
                    TextEntry::make('magnetic')
                        ->icon(fn (bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->iconColor(fn (bool $state) => $state ? Color::Green : Color::Red)
                        ->formatStateUsing(fn (bool $state) => $state ? 'مرفق' : 'غير مرفق')
                        ->label('دوبلكس'),
                    TextEntry::make('duplex')
                        ->icon(fn (bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->iconColor(fn (bool $state) => $state ? Color::Green : Color::Red)
                        ->formatStateUsing(fn (bool $state) => $state ? 'مرفق' : 'غير مرفق')
                        ->label('رف'),
                    TextEntry::make('shelf')
                        ->icon(fn (bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->iconColor(fn (bool $state) => $state ? Color::Green : Color::Red)
                        ->formatStateUsing(fn (bool $state) => $state ? 'مرفق' : 'غير مرفق')
                        ->label('درج'),
                ])
                ->columns(5),
            Section::make('تست الماكينة')
                ->schema([
                    Split::make([
                        Fieldset::make('تست قبل الصيانة')
                            ->schema(function (Order $order) {
                                if ($order->image_before) {
                                    $schema = [
                                        ImageEntry::make('image_before')
                                            ->url(asset('storage/'.$order->image_before), true),
                                    ];
                                } else {
                                    $schema = [
                                        TextEntry::make('not_exists')
                                            ->default('لا يوجد صورة تست حتى الآن')
                                            ->label(''),
                                    ];
                                }

                                return $schema;
                            }),
                        Fieldset::make('تست بعد الصيانة')
                            ->schema(function (Order $order) {
                                if ($order->image_after) {
                                    $schema = [
                                        ImageEntry::make('image_after')
                                            ->url(asset('storage/'.$order->image_after), true),
                                    ];
                                } else {
                                    $schema = [
                                        TextEntry::make('not_exists')
                                            ->default('لا يوجد صورة تست حتى الآن')
                                            ->label(''),
                                    ];
                                }

                                return $schema;
                            }),
                    ])
                        ->from('sm'),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            LogsRelationManager::class,
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
