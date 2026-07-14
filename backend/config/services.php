<?php

return [

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    // DDD microservice (ReactPHP simulator)
    'simulator' => [
        'url' => env('SIMULATOR_URL', 'http://simulator:8001'),
    ],

];
