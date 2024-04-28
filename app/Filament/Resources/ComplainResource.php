<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComplainResource\Pages;
use App\Filament\Resources\ComplainResource\RelationManagers;
use App\Models\Complain;
use App\Models\MachineModel;
use App\Support\Services\ComplainService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ComplainResource extends Resource
{
    protected static ?string $model = Complain::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'الشكاوي';

    protected static ?string $label = 'شكوى';

    protected static ?string $pluralLabel = 'الشكاوي';

    protected static ?string $navigationGroup = 'الدعم الفني';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->searchable()
                            ->label('العميل')
                            ->preload()
                            ->relationship('customer', 'name')
                            ->required(),
                        Forms\Components\Select::make('machine_type_id')
                            ->searchable()
                            ->label('نوع الماكينة')
                            ->preload()
                            ->relationship('machineType', 'name')
                            ->required(),
                        Forms\Components\Select::make('machine_model_id')
                            ->searchable()
                            ->options(fn(Forms\Get $get) => MachineModel::query()->where('machine_type_id', $get('machine_type_id'))->pluck('model', 'id'))
                            ->nullable()
                            ->preload()
                            ->label('موديل الماكينة'),
                    ]),
                Forms\Components\Grid::make(2)
                    ->visibleOn('create')
                    ->schema([
                        Forms\Components\RichEditor::make('complain')
                            ->label('الشكوى')
                            ->required(),
                        Forms\Components\RichEditor::make('solution')
                            ->label('الحل')
                            ->required(),
                    ])
            ]);
    }

    /**
     * @throws \Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->searchable()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(Complain $complain) => ['open' => 'success', 'closed' => 'danger'][$complain->type])
                    ->formatStateUsing(fn(Complain $complain) => $complain->type === 'open' ? 'مفتوحة' : 'تم الإغلاق')
                    ->label('حالة الشكوى'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->placeholder('غير معروف')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('حالة الشكوى')
                    ->options([
                        'open' => 'مفتوحة',
                        'closed' => 'تم الإغلاق',
                    ])
            ])
            ->actions([
                ComplainService::addNoteAction(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComplains::route('/'),
            'create' => Pages\CreateComplain::route('/create'),
            'view' => Pages\ViewComplain::route('/{record}'),
            'edit' => Pages\EditComplain::route('/{record}/edit'),
        ];
    }
}
