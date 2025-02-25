<?php

namespace App\Listeners;

use App\Events\OrderStatusChangedEvent;
use App\Models\Message;
use App\Models\Order;
use App\Support\Services\OrderService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

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
     *
     * @throws Exception
     */
    public function handle(OrderStatusChangedEvent $event): void
    {
        if (! in_array($event->status, ['working', 'pending', 'handed', 'created', 'finished'])) {
            return;
        }

        (new \App\Support\Notify\Whatsapp)->send(
            $event->order->machine->customer->phone,
            $this->handleMessage($event->status, $event->order, $event->description),
        );
    }

    private function handleMessage(string $status, Order $order, ?string $description = null): string
    {
        $message = Message::query()->where('key', $status)->value('message');

        if (str_contains($message, '[order_id]')) {
            $message = str_replace('[order_id]', $order->getAttribute('id'), $message);
        }

        if (str_contains($message, '[message]')) {
            $message = str_replace('[message]', $description ?? '', $message);
        }

        if (str_contains($message, '[serial]')) {
            $message = str_replace('[serial]', $order->machine->serial_number, $message);
        }

        if (str_contains($message, '[machines_queue]')) {
            $message = str_replace('[machines_queue]', (new OrderService)->orderQueue($order), $message);
        }

        return $message;
    }
}
