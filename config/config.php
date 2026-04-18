<?php
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'hokm_db',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        // مثال: '' برای ریشه دامنه یا '/hokm/public' برای اجرای پروژه داخل زیرمسیر
        'base_url' => '',
        'poll_interval_ms' => 1500,
        'session_name' => 'hokm_session',
    ],
];
