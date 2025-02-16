<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\MachinesRelationManager;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerResource extends Resource
{
    protected static ?string $navigationLabel = 'العملاء';

    protected static ?string $label = 'عميل';

    protected static ?string $pluralLabel = 'العملاء';

    protected static ?string $navigationGroup = 'الدعم الفني';

    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم العميل')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->maxLength(15),
                        Forms\Components\TextInput::make('whatsapp')
                            ->label('رقم الواتساب')
                            ->tel()
                            ->maxLength(15),
                    ]),
                Forms\Components\TextInput::make('address')
                    ->label('العنوان')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
                Forms\Components\RichEditor::make('description')
                    ->label('بيانات إضافية')
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('serial_number')
                    ->label('رقم سيريال العميل')
                    ->maxLength(191),
                Forms\Components\Select::make('branch_id')
                    ->label('الفرع التابع')
                    ->required()
                    ->default(auth()->user()->branch_id)
                    ->relationship('branch', 'name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم العميل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label('رقم الهاتف'),
                Tables\Columns\TextColumn::make('address')
                    ->label('العنوان')
                    ->formatStateUsing(fn(Customer $customer) => str($customer->address)->limit())
                    ->searchable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->state(fn(Customer $customer) => $customer->serial_number ?? 'لا يوجد')
                    ->label('رقم السيريال')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('الفرع')
                    ->relationship('branch', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make("بيانات العميل")
                ->schema([
                    TextEntry::make("name")
                        ->icon('heroicon-o-user')
                        ->iconColor(Color::Cyan)
                        ->label("اسم العميل"),
                    TextEntry::make("phone")
                        ->icon('heroicon-o-phone')
                        ->iconColor(Color::Yellow)
                        ->label("رقم الهاتف"),
                    TextEntry::make("whatsapp")
                        ->icon('heroicon-o-device-phone-mobile')
                        ->iconColor(Color::Green)
                        ->label("رقم الهاتف"),
                    TextEntry::make("address")
                        ->icon('heroicon-o-map')
                        ->iconColor(Color::Pink)
                        ->label("العنوان"),
                    TextEntry::make("serial_number")
                        ->icon('heroicon-o-hashtag')
                        ->iconColor(Color::Blue)
                        ->label("رقم السيريال")
                        ->default('لا يوجد'),
                    TextEntry::make("branch.name")
                        ->icon('heroicon-o-tag')
                        ->iconColor(Color::Gray)
                        ->label("الفرع"),
                    TextEntry::make("orders_count")
                        ->icon('heroicon-o-numbered-list')
                        ->default(fn(Customer $customer) => $customer->machines()->count())
                        ->iconColor(Color::Indigo)
                        ->label("عدد الماكينات"),
                    TextEntry::make("description")
                        ->icon('heroicon-o-document-text')
                        ->iconColor(Color::Red)
                        ->default('لا يوجد')
                        ->html()
                        ->columnSpanFull()
                        ->label("وصف العميل"),
                ])
                ->columns(4)
        ]);
    }

    public static function getRelations(): array
    {
        return [
            MachinesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()->role, ['manager', 'data_entry', 'customer_support']);
    }
}
