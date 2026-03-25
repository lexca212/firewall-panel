<?php

// routes/web.php

use App\Http\Controllers\FirewallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FirePanel — Internal Routes (tanpa auth)
| Lindungi dengan firewall iptables: hanya akses dari IP tepercaya
|--------------------------------------------------------------------------
*/

Route::prefix('firewall')->name('firewall.')->group(function () {

    // Dashboard
    Route::get('/',       [FirewallController::class, 'index'])->name('index');

    // Status & Toggle
    Route::get('/status',  [FirewallController::class, 'status'])->name('status');
    Route::post('/enable', [FirewallController::class, 'enable'])->name('enable');
    Route::post('/disable',[FirewallController::class, 'disable'])->name('disable');

    // Rules
    Route::get('/rules',         [FirewallController::class, 'rules'])->name('rules');
    Route::post('/rules',        [FirewallController::class, 'addRule'])->name('rules.add');
    Route::delete('/rules',      [FirewallController::class, 'deleteRule'])->name('rules.delete');
    Route::post('/rules/flush',  [FirewallController::class, 'flushRules'])->name('rules.flush');
    Route::post('/rules/policy', [FirewallController::class, 'setPolicy'])->name('rules.policy');

    // Ganti Port
    Route::post('/change-port',  [FirewallController::class, 'changePort'])->name('change-port');

    // Save & Restore
    Route::post('/save',    [FirewallController::class, 'saveRules'])->name('save');
    Route::post('/restore', [FirewallController::class, 'restoreRules'])->name('restore');
    Route::get('/export',   [FirewallController::class, 'exportRules'])->name('export');

    // Live Logs
    Route::get('/logs',        [FirewallController::class, 'logs'])->name('logs');
    Route::get('/logs/stream', [FirewallController::class, 'logsStream'])->name('logs.stream');

    // Stats (polling)
    Route::get('/stats',   [FirewallController::class, 'stats'])->name('stats');

    // Telegram
    Route::post('/telegram/save', [FirewallController::class, 'telegramSave'])->name('telegram.save');
    Route::post('/telegram/test', [FirewallController::class, 'telegramTest'])->name('telegram.test');
});

// Redirect root ke dashboard
Route::redirect('/', '/firewall');

