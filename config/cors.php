<?php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['POST', 'GET', 'OPTIONS', 'PUT', 'DELETE'],
    'allowed_origins' => [env('APP_URL_FRONTEND', 'https://p2tlanalisa.web.id')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false
];
