<?php

namespace App\Support\Notify;

use Exception;
use Illuminate\Support\Facades\Http;

class Whatsapp
{
    /**
     * @throws Exception
     */
    public function send($phone, $body, $image = null): void
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'token' => config('services.whatsapp.token'),
            'Accept' => 'application/json',
        ])
            ->post(
                config('services.whatsapp.url'),
                $this->message_template($phone, $body, $image)
            );

        if (! $response->json('success')) {
            throw new Exception('session not connected');
        }
    }

    private function message_template($phone, $body, $image = null): array
    {
        $phone = $this->handle_phone_area_code($phone);

        return [
            'phones' => [$phone],
            'phone' => $phone,
            'message' => $this->handleMessage($body),
            'img' => 'data:image/png;base64,'.$image,
        ];
    }

    private function handleMessage(string $message): string
    {
        $message .= "\n\n".date('Y-m-d h:i:s A');

        $suffix = "\n\nBrain Inkjet 💻";

        return $message.$suffix;
    }

    private function handle_phone_area_code($phone): string
    {
        return '2'.$phone;
    }
}
