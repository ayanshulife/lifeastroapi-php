<?php

// Add this entry to the array in your app's config/services.php.

return [
    // ...

    'lifeastro' => [
        'key' => env('LIFEASTRO_API_KEY'),
        'base_url' => env('LIFEASTRO_BASE_URL', 'https://api.lifeastroapi.com'),
        'timeout' => (float) env('LIFEASTRO_TIMEOUT', 30),
    ],
];
