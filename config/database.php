<?php

return [
    'default' => env('DB_SQL_CONNECTION', 'mysql'),
    'connections' => [
        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host'     => env('DB_SQL_HOST', 'tu_servidor_sql_server'),
            'database' => env('DB_SQL_DATABASE', 'tu_base_de_datos'),
            'username' => env('DB_SQL_USERNAME', 'tu_usuario'),
            'password' => env('DB_SQL_PASSWORD', 'tu_contraseña'),
            'charset'  => 'utf8',
            'prefix'   => '',
        ],
        'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'forge'),
                'username' => env('DB_USERNAME', 'forge'),
                'password' => env('DB_PASSWORD', ''),
                'unix_socket' => env('DB_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
        ],
    ],

];
