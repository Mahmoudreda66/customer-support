<?php

namespace App\Listeners;

use App\Events\OrderStatusChangedEvent;
use App\Models\Message;
use App\Models\Order;
use App\Support\Notify\SMS;
use App\Support\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderStatusChangedListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChangedEvent $event): void
    {
        if (!in_array($event->status, ['working', 'pending', 'handed', 'created', 'finished'])) {
            return;
        }

        SMS::send(
            $this->handleMessage($event->status, $event->order, $event->description),
            $event->order->customer->phone
        );
    }

    private function handleMessage(string $status, Order $order, ?string $description = null): string
    {
        $message = Message::query()->where('key', $status)->value('message');

        if (str_contains($message, '[order_id]'))
            $message = str_replace('[order_id]', $order->getAttribute('id'), $message);

        if (str_contains($message, '[message]'))
            $message = str_replace('[message]', $description ?? '', $message);

        if (str_contains($message, '[serial]'))
            $message = str_replace('[serial]', $order->getAttribute('serial_number'), $message);

        if (str_contains($message, '[machines_queue]'))
            $message = str_replace('[machines_queue]', (new OrderService())->orderQueue($order), $message);

        return $message;
    }
}
