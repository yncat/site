<?php

return [
    'settings' => [
        'displayErrorDetails' => false, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        //ミドルウェアの前にマッピングを確定させる
        'determineRouteBeforeAppMiddleware' => true,

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../app/View/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // DataBase(MySQL) settings
        'doctrine' => [
            // if true, metadata caching is forcefully disabled
            'dev_mode' => true,

            // path where the compiled metadata info will be cached
            // make sure the path exists and it is writable
            'cache_dir' =>  __DIR__ . '/var/doctrine',

            // you should add any other path containing annotated entity classes
            'metadata_dirs' => [ __DIR__ . '/src/Domain'],

            'connection' => [
                'driver' => 'pdo_mysql',
                'host' => getenv("DB_HOST"),
                'port' => '3306',
                'user' => getenv("DB_USER"),
                'password' => getenv("DB_PASS"),
                'dbname' => getenv("DB_NAME"),
                'charset' => 'utf8mb4'
            ]
        ]
    ],
];
