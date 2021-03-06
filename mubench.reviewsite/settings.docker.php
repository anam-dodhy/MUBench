<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        'db' => [
            'driver' => 'sqlite',
            'host' => 'localhost',
            'database' => __DIR__ . '/../mubench-reviewsite-data/standalone.sqlite',
            'username' => 'admin',
            'password' => 'admin',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'mubench',
            'path' => __DIR__ . '/../mubench-reviewsite-data/logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'site_base_url' => '/',
        'upload' => __DIR__ . '/../mubench-reviewsite-data/upload',
        'default_ex2_review_size' => '20'
    ],
    'users' => [
        "reviewer1" => "standalone",
        "reviewer2" => "standalone"
    ]
];
