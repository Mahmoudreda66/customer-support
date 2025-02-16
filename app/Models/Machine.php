<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function machineType(): BelongsTo
    {
        return $this->belongsTo(MachineType::class);
    }

    public function machineModel(): BelongsTo
    {
        return $this->belongsTo(MachineModel::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
