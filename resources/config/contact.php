<?php

return [
    'email' => env('CONTACT_EMAIL', env('ADMIN_EMAIL')),

    'throttle' => [
        'attempts' => 5,
        'decay'    => 600,
    ],
];
