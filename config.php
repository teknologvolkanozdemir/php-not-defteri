<?php
// Veritabanı ayarları (ortam değişkenleriyle değiştirilebilir)
return [
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_name' => getenv('DB_NAME') ?: 'notdefteri',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') ?: '',
    'per_page' => 10,
];
