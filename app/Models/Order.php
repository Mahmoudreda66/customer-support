<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SystemLog::class, 'to_id')
            ->where('to_model', self::class);
    }

    /**
     * Latest status log where the order moved to "working" (who received the machine).
     * Use with {@see Order::listQueryForAuthenticatedUser()} + eager `latestWorkingLog.user` on list screens to avoid N+1.
     */
    public function latestWorkingLog(): HasOne
    {
        return $this->hasOne(SystemLog::class, 'to_id')
            ->where('to_model', self::class)
            ->whereJsonContains('data->status', 'working')
            ->latestOfMany('id');
    }

    public function getRepairerEngineerAttribute(): ?User
    {
        return $this->latestWorkingLog?->user;
    }

    /** Same visibility rules as the main orders table (Filament). */
    public static function listQueryForAuthenticatedUser(): Builder
    {
        return static::query()->when(auth()->user()->role === 'maintenance', fn ($q) => $q->whereIn('status', ['created', 'working', 'pending', 'refactor']));
    }

    /**
     * Created at least two days ago and has no status logs except the automatic
     * "new order" log ({@see CreateOrder} writes {@code status: created}).
     */
    public function scopeStaleWithoutLogs(Builder $query): void
    {
        $query->where('created_at', '<=', now()->subDays(2))
            ->where('status', 'created')
            ->whereDoesntHave('logs', function (Builder $logQuery): void {
                $logQuery->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.status')) <> ?", ['created']);
            });
    }

    /**
     * Currently handed, and the latest log entry that recorded "handed" is at least $days old.
     */
    public function scopeHandedWithHandedLogOlderThan(Builder $query, int $days = 7): void
    {
        $query->where('status', 'handed')
            ->whereRaw(
                '(select max(sl.created_at) from system_logs as sl where sl.to_model = ? and sl.to_id = orders.id and JSON_UNQUOTE(JSON_EXTRACT(sl.data, \'$.status\')) = ?) <= ?',
                [self::class, 'handed', now()->subDays($days)]
            );
    }
}
