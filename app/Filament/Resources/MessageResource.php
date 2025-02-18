<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use App\Support\Services\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MessageResource extends Resource
{
    protected static ?string $navigationLabel = 'رسائل العملاء';

    protected static ?string $label = 'رسالة';

    protected static ?string $pluralLabel = 'الرسائل';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label('نوع الرسالة')
                    ->formatStateUsing(fn ($state) => OrderService::STATUSES[$state])
                    ->disabled()
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('message')
                    ->label('الرسالة')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->formatStateUsing(fn ($state) => OrderService::STATUSES[$state])
                    ->label('نوع الرسالة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('message')
                    ->label('نص الرسالة')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->searchable(false)
            ->paginated(false)
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
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
            'index' => Pages\ListMessages::route('/'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'manager';
    }
}
