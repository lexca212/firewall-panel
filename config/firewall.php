<?php

// config/firewall.php

return [
    /*
    |--------------------------------------------------------------------------
    | Panel Login (tanpa database)
    |--------------------------------------------------------------------------
    | Default:
    |   Username: DevSecOps
    |   Password: SysAdmin
    |
    | Bisa diganti dari UI, dan nilainya disimpan ke .env.
    */

    'panel_username' => env('FIREWALL_PANEL_USERNAME', 'DevSecOps'),
    'panel_password' => env('FIREWALL_PANEL_PASSWORD', 'SysAdmin'),

    /*
    |--------------------------------------------------------------------------
    | Telegram Notification
    |--------------------------------------------------------------------------
    | Isi di .env:
    |   TELEGRAM_BOT_TOKEN=12345:ABCxxx
    |   TELEGRAM_CHAT_ID=-100xxxxxxxxx
    */

    'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'telegram_chat_id'   => env('TELEGRAM_CHAT_ID',   ''),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    | Semua perintah iptables dicatat di storage/logs/firewall.log
    */

    'log_channel' => 'firewall',

    /*
    |--------------------------------------------------------------------------
    | Live Log — baris default
    |--------------------------------------------------------------------------
    */

    'log_lines_default' => 100,
    'log_lines_max'     => 500,

    /*
    |--------------------------------------------------------------------------
    | Sudoers path (untuk referensi setup)
    |--------------------------------------------------------------------------
    */

    'sudoers_file' => '/etc/sudoers.d/firewall-panel',
];
