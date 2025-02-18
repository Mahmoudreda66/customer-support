<?php

use App\Models\Customer;
use App\Models\Machine;
use App\Models\MachineModel;
use App\Models\MachineType;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateMachineDataToMachinesTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignIdFor(Machine::class)
                ->nullable()
                ->constrained()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Order::query()->where('serial_number', '0000000000')->update(['serial_number' => null]);

        foreach (Order::query()->get() as $order) {
            if ($order->serial_number) {
                $machine = Machine::query()->firstOrCreate([
                    'serial_number' => $order->getAttribute('serial_number'),
                ], [
                    'machine_type_id' => $order->machine_type_id,
                    'machine_model_id' => $order->machine_model_id,
                    'customer_id' => $order->customer_id,
                ]);
            } else {
                $machine = Machine::query()->create([
                    'serial_number' => $order->getAttribute('serial_number'),
                    'machine_type_id' => $order->machine_type_id,
                    'machine_model_id' => $order->machine_model_id,
                    'customer_id' => $order->customer_id,
                ]);
            }

            $order->update([
                'machine_id' => $machine->id,
            ]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeignIdFor(MachineType::class);
            $table->dropForeignIdFor(MachineModel::class);
            $table->dropForeignIdFor(Customer::class);
            $table->dropColumn([
                'serial_number',
                'customer_id',
                'machine_type_id',
                'machine_model_id',
            ]);
        });
    }
}
