<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MachineModelResource\Pages;
use App\Models\MachineModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MachineModelResource extends Resource
{
    protected static ?string $model = MachineModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationLabel = 'موديلات الماكينات';

    protected static ?string $label = 'موديل';

    protected static ?string $pluralLabel = 'الموديلات';

    protected static ?string $navigationGroup = 'البيانات الأساسية';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('machine_type_id')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->relationship('machineType', 'name')
                    ->label('نوع الماكينة'),
                Forms\Components\TextInput::make('model')
                    ->label('الموديل')
                    ->required()
                    ->maxLength(191),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('machineType.name')
                    ->label('نوع الماكينة')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('الموديل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التعديل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMachineModels::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'manager';
    }
}
