<?php

return [

    'driver'=>env('MAIL_DRIVER', 'smtp'),
    'host'=>env('MAIL_HOST', 'smtp.gmail.com'),
    'port' => env('MAIL_PORT' , 465),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'gamr130898@gmail.com'),
        'name' => env('MAIL_FROM_NAME', 'Comercial Gusmor'),
        ],
        'encryption' => env('MAIL_ENCRYPTION', 'ssl'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'sendmail' => '/usr/sbin/sendmail -bs',
        'markdown' =>[
            'theme' => 'default',

            'paths' => [
                resource_path('views/vendor/mail'),
            ],
        ],
        'log_channel' => env('MAIL_LOG_CHANNEL'),

];
