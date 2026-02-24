<?php

use App\Models;

return [
    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    'queue' => [
        'queue' => env('SCOUT_QUEUE', 'scout'),
        'connection' => env('SCOUT_CONNECTION', 'rabbitmq'),
    ],

    'after_commit' => true,

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            Models\Contact::class => [
                'searchableAttributes' => [
                    'first_name',
                    'last_name',
                    'email',
                ],
            ],
        ],
    ],
];
