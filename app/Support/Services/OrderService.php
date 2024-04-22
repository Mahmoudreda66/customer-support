<?php

namespace App\Support\Services;

use App\Models\Order;
use App\Models\SystemLog;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class OrderService
{
    const STATUSES = [
        'created' => 'طلب جديد',
        'working' => 'جاري العمل',
        'pending' => 'طلب مُعلق',
        'cancelled' => 'تم الإلغاء',
        'finished' => 'إنتهت الصيانة',
        'handed' => 'تم التسليم',
        'called' => 'تم الاتصال',
        'refactor' => 'مرتجع للصيانة',
    ];

    public static function colors($state): string
    {
        return match ($state) {
            'created', 'refactor' => 'warning',
            'working' => 'info',
            'pending', 'cancelled' => 'danger',
            'handed', 'finished', 'called' => 'success',
        };
    }

    public static function changeStatusAction(bool $for_table = true): Action|\Filament\Tables\Actions\Action
    {
        if ($for_table) {
            $action = \Filament\Tables\Actions\Action::make('updateStatus');
        } else {
            $action = Action::make('updateStatus');
        }

        return $action
            ->label('حالة الطلب')
            ->icon('heroicon-m-arrow-path')
            ->color('success')
            ->form([
                Select::make('status')
                    ->label('حالة الطلب')
                    ->required()
                    ->notIn(fn (Order $order) => [$order->status])
                    ->default(fn (Order $order) => $order->status)
                    ->options(OrderService::STATUSES),
                RichEditor::make('description')
                    ->label('وصف العملية')
                    ->string(),
            ])
            ->action(function (array $data, Order $order) {
                try {
                    DB::beginTransaction();

                    $order->update(['status' => $data['status']]);

                    SystemLog::query()->create([
                        'user_id' => auth()->id(),
                        'to_model' => Order::class,
                        'to_id' => $order->id,
                        'data' => ['status' => $data['status'], 'description' => $data['description']],
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
            });
    }
}
