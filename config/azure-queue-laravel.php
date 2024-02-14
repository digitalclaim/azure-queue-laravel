<?php

// config for DigitalClaim/AzureQueue
return [
    'worker' => [
        'backoff' => env('DIGITALCLAIM_AZURE_QUEUE_LARAVEL_BACKOFF', 60 * 5),
        'maxTries' => env('DIGITALCLAIM_AZURE_QUEUE_LARAVEL_MAXTRIES', 3),
    ],
];
