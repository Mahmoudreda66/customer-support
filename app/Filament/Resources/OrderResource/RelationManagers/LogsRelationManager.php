<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\SystemLog;
use App\Models\User;
use App\Support\Services\OrderService;
use App\Support\Services\UserService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'حالات الطلب';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('data')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'DESC')
            ->recordTitleAttribute('data')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('تم بواسطة'),
                Tables\Columns\TextColumn::make('user.role')
                    ->formatStateUsing(fn(User $user) => $user->role ? UserService::JOBS[$user->role] : 'موظف عام')
                    ->label('نوع الموظف'),
                Tables\Columns\TextColumn::make('user.branch.name')
                    ->placeholder('موظف عام')
                    ->label('فرع الموظف'),
                Tables\Columns\TextColumn::make('data.status')
                    ->formatStateUsing(fn($record) => OrderService::STATUSES[$record->data['status']])
                    ->badge()
                    ->color(fn(string $state): string => OrderService::colors($state))
                    ->label('حالة الطلب'),
                Tables\Columns\TextColumn::make('data.description')
                    ->limit(50)
                    ->html()
                    ->placeholder('لا يوجد')
                    ->label('وصف الطلب'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('تاريخ العملية'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
