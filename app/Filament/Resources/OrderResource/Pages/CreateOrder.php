<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\SystemLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            DB::beginTransaction();

            $order = Order::query()->create($data);

            SystemLog::query()->create([
                'user_id' => auth()->id(),
                'to_id' => $order->id,
                'data' => ['status' => 'created'],
                'to_model' => Order::class,
            ]);

            DB::commit();

            return $order;
        } catch (\Exception) {
            DB::rollBack();
            abort(500);
        }
    }
}
