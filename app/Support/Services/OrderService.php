<?php

namespace App\Support\Services;

use App\Events\OrderStatusChangedEvent;
use App\Models\Order;
use App\Models\SystemLog;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
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

    const TYPES = ['maintenance' => 'صيانة', 'preparation' => 'تجهيز'];

    public static function statusesByRole(string $role): array
    {
        return match ($role) {
            'maintenance' => ['working' => 'جاري العمل', 'pending' => 'طلب مُعلق', 'finished' => 'إنتهت الصيانة', 'cancelled' => 'تم الإلغاء',],
            'data_entry' => ['refactor' => 'مرتجع للصيانة', 'handed' => 'تم التسليم', 'cancelled' => 'تم الإلغاء', 'called' => 'تم الاتصال'],
            'customer_support' => ['refactor' => 'مرتجع للصيانة', 'called' => 'تم الاتصال'],
            default => self::STATUSES,
        };
    }

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
                    ->notIn(fn(Order $order) => [$order->status])
                    ->default(fn(Order $order) => $order->status)
                    ->live()
                    ->options(OrderService::statusesByRole(auth()->user()->role)),
                Textarea::make('description')
                    ->required(fn(Get $get) => in_array($get('status'), ['pending', 'finished', 'refactor', 'cancelled', 'called']))
                    ->label('وصف العملية')
                    ->rows(8)
                    ->string(),
                Grid::make(3)
                    ->schema([
                        TextInput::make('serial_number')
                            ->required()
                            ->default(fn(Order $order) => $order->getAttribute('serial_number'))
                            ->string()
                            ->maxLength('191')
                            ->label('سيريال الماكينة'),
                        FileUpload::make('image_before')
                            ->image()
                            ->directory('orders/tests-images')
                            ->disk('public')
                            ->label('تست الماكينة قبل الصيانة')
                            ->maxSize(8 * 1024),
                        FileUpload::make('image_after')
                            ->label('تست الماكينة بعد الصيانة')
                            ->image()
                            ->required()
                            ->directory('orders/tests-images')
                            ->disk('public')
                            ->maxSize(8 * 1024),
                    ])
                    ->visible(fn(Get $get) => $get('status') === 'finished'),
            ])
            ->action(function (array $data, Order $order) {
                try {
                    $orderData = [
                        'status' => $data['status'],
                    ];

                    if (!empty($data['image_after'])) {
                        $orderData['image_after'] = $data['image_after'];
                    }

                    if (!empty($data['image_before'])) {
                        $orderData['image_before'] = $data['image_before'];
                    }

                    DB::beginTransaction();

                    $order->update($orderData);

                    $order->machine()->update(['serial_number' => $data['serial_number']]);

                    SystemLog::query()->create([
                        'user_id' => auth()->id(),
                        'to_model' => Order::class,
                        'to_id' => $order->id,
                        'data' => ['status' => $data['status'], 'description' => $data['description']],
                    ]);

                    event(new OrderStatusChangedEvent($data['status'], $order, $data['description']));

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

    public function orderQueue(Order $order): int
    {
        return Order::query()->latest()
            ->where([
                ['id', '<', $order->getAttribute('id')],
                ['branch_id', $order->getAttribute('branch_id')]
            ])
            ->whereIn('status', ['working', 'created'])
            ->count();
    }
}
