<?php

namespace App\Support\Notify;

use Illuminate\Support\Facades\Http;

class SMS
{
    public static function send(string $message, string $to, ?string $sender = null, int $language = 2)
    {
        $sender = $sender ?? config('sms.sender');

        $response = Http::post(config('sms.endpoint'), [
            'environment' => config('sms.environment'),
            'username' => config('sms.username'),
            'password' => config('sms.password'),
            'sender' => $sender,
            'language' => $language,
            'message' => $message,
            'mobile' => $to,
        ])->body();

        return json_decode($response, true);
    }
}
