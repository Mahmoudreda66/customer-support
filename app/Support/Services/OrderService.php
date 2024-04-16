<?php

namespace App\Support\Services;

class OrderService
{
    const STATUSES = [
        'created' => 'طلب جديد',
        'working' => 'جاري العمل',
        'pending' => 'طلب مُعلق',
        'cancelled' => 'تم الإلغاء',
        'finished' => 'تم الإنتهاء من الصيانة',
        'handed' => 'تم التسليم',
        'called' => 'تم الاتصال',
        'refactor' => 'مرتجع للصيانة'
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
}
