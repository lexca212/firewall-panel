<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\FirewallService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('firewall:backup-run {type} {payload}', function (string $type, string $payload) {
    /** @var FirewallService $service */
    $service = app(FirewallService::class);
    $data = json_decode(base64_decode($payload), true) ?: [];

    $result = match ($type) {
        'mysql' => $service->backupMysql($data),
        'zip'   => $service->backupFolderZip($data['source'] ?? '', $data['destination'] ?? '/var/backups/firepanel'),
        'rsync' => $service->backupRsync($data),
        default => ['success' => false, 'message' => "Type backup tidak dikenal: {$type}"],
    };

    $this->info($result['message'] ?? 'Backup selesai.');
})->purpose('Run scheduled backup task for FirePanel');
