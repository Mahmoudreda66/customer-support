<?php

use App\Models\Customer;
use App\Models\Machine;
use App\Models\MachineModel;
use App\Models\MachineType;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        foreach (Order::query()->where('serial_number', '<>', null)->get() as $order) {
            $machine = Machine::query()->firstOrCreate([
                'serial_number' => $order->getAttribute('serial_number')
            ], [
                'customer_id' => $order->customer_id,
                'machine_type_id' => $order->machine_type_id,
                'machine_model_id' => $order->machine_model_id,
            ]);

            $order->update([
                'machine_id' => $machine->id
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
                'machine_model_id'
            ]);
        });
    }
}
