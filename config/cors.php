<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS
    |--------------------------------------------------------------------------
    |
    | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
    | to accept any value.
    |
    */
    'supportsCredentials' => false,
    'allowedOrigins' => [
        // 'http://api.funinatl.test',
        // 'https://api.funinatl.com',
        // 'http://localhost'
        '*'
    ],
    'allowedHeaders' => ['Accepts', 'Authorization', 'Content-Type', 'Content-Disposition', 'X-Filename', 'X-Timezone', 'X-User'],
    'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'exposedHeaders' => ['Content-Type','Content-Disposition', 'X-Filename', 'X-Timezone', 'X-User'],
    'maxAge' => 0,
];
