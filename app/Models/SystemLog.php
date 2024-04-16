<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'data' => 'json',
    ];

    public function orders(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'to_id')
            ->where('to_model', Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
