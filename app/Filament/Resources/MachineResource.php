<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MachineResource\Pages;
use App\Filament\Resources\MachineResource\RelationManagers;
use App\Filament\Resources\MachineResource\RelationManagers\OrdersRelationManager;
use App\Models\Machine;
use App\Models\MachineModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MachineResource extends Resource
{
    protected static ?string $model = Machine::class;
    protected static ?string $navigationLabel = 'الماكينات';

    protected static ?string $label = 'ماكينة';

    protected static ?string $pluralLabel = 'الماكينات';

    protected static ?string $navigationGroup = 'الدعم الفني';

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('serial_number')
                    ->label('رقم السيريال')
                    ->required()
                    ->maxLength(191),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('العميل')
                    ->required(),
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
                    ->disabled(fn(Get $get) => !$get('machine_type_id'))
                    ->required()
                    ->preload()
                    ->label('موديل الماكينة'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Machine::query()->withCount('orders')->with('customer'))
            ->defaultSort('created_at', 'DESC')
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('رقم السيريال')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('عدد الطلبات')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('machineType.name')
                    ->label('نوع الماكينة')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('machineModel.model')
                    ->label('موديل الماكينة')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMachines::route('/'),
            'create' => Pages\CreateMachine::route('/create'),
            'view' => Pages\ViewMachine::route('/{record}'),
            'edit' => Pages\EditMachine::route('/{record}/edit'),
        ];
    }
}
