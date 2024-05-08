<?php

namespace Database\Seeders;

use App\Models\Message;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $messages = [
            [
                'key' => 'created',
                'message' => 'تم إستلام الماكينة، أمامك [machines_queue] ماكينة'
            ],
            [
                'key' => 'working',
                'message' => 'جاري العمل على الماكينة. رقم الطلب: [order_id]',
            ],
            [
                'key' => 'pending',
                'message' => ' الطلب رقم [order_id] قيد التعليق' . "\n" . '[message]',
            ],
            [
                'key' => 'finished',
                'message' => "تم الإنتهاء من الماكينة\nرقم الطلب: [order_id]\nسيريال: [serial]"
            ],
            [
                'key' => 'handed',
                'message' => 'تم تسليم الماكينة للتو، سيريال: [serial]. رقم الطلب: [order_id]',
            ],
        ];

        Message::query()->insert($messages);
    }
}
