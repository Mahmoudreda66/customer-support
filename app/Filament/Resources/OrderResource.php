<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\SystemLog;
use App\Models\User;
use App\Support\Services\OrderService;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

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
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->required(),
                Forms\Components\Select::make('branch_id')
                    ->label('الفرع')
                    ->relationship('branch', 'name')
                    ->required(),
                Forms\Components\RichEditor::make('description')
                    ->label('وصف الطلب')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('deadline')
                    ->label('وقت الانتهاء الأقصى / Deadline'),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(Order $order) => OrderService::STATUSES[$order->status])
                    ->color(fn(string $state): string => OrderService::colors($state))
                    ->label('الحالة'),
                Tables\Columns\TextColumn::make('deadline')
                    ->state(fn(Order $order) => $order->deadline ? $order->deadline->format('Y-m-d h:i A') : 'لا يوجد')
                    ->label('وقت الانتهاء')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->state(fn(Order $order) => $order->user?->name ?? 'غير معروف')
                    ->label('مٌنشئ الطلب')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->multiple()
                    ->options(OrderService::STATUSES),
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
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('updateStatus')
                    ->label('حالة الطلب')
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('حالة الطلب')
                            ->required()
                            ->notIn(fn(Order $order) => [$order->status])
                            ->default(fn(Order $order) => $order->status)
                            ->options(OrderService::STATUSES),
                        Forms\Components\RichEditor::make('description')
                            ->label('وصف العملية')
                            ->required()
                            ->string()
                    ])
                    ->action(function (array $data, Order $order) use ($table) {
                        try {
                            DB::beginTransaction();

                            $order->update(['status' => $data['status']]);

                            SystemLog::query()->create([
                                'user_id' => auth()->id(),
                                'to_model' => Order::class,
                                'to_id' => $order->id,
                                'data' => ['status' => $data['status'], 'description' => $data['description']]
                            ]);

                            DB::commit();

                            Notification::make()
                                ->title('تم تحديث الحالة بنجاح')
                                ->success()
                                ->send();
                        } catch (Exception) {
                            DB::rollBack();

                            Notification::make()
                                ->title('لقد حدث خطأ غير متوقع')
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
