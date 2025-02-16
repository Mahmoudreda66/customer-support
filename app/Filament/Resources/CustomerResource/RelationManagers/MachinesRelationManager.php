<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Filament\Resources\MachineResource;
use App\Models\Machine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MachinesRelationManager extends RelationManager
{
    protected static ?string $title = 'الماكينات';

    protected static string $relationship = 'machines';

    public function form(Form $form): Form
    {
        return $form
            ->schema(MachineResource::form($form)->getColumns());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Machine::query()->where('customer_id', $this->ownerRecord->id)->with('customer')->withCount('orders'))
            ->recordTitleAttribute('serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('رقم السيريال')
                    ->searchable(),
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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('show_machine')
                    ->label('عرض الماكينة')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Machine $machine) => route('filament.admin.resources.machines.view', $machine->id)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
