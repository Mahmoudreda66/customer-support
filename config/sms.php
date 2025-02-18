<?php

return [
    'environment' => env('SMSMISR_ENVIRONMENT'),
    'username' => env('SMSMISR_USERNAME'),
    'password' => env('SMSMISR_PASSWORD'),
    'sender' => env('SMSMISR_SENDER'),
    'endpoint' => env('SMSMISR_ENDPOINT', 'https://smsmisr.com/api/SMS'),
];
