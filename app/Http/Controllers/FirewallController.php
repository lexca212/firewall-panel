<?php

namespace App\Http\Controllers;

use App\Services\FirewallService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FirewallController extends Controller
{
    public function __construct(private FirewallService $firewall) {}

    // =========================================================
    //  AUTH (tanpa database)
    // =========================================================

    public function loginForm()
    {
        if (session('firewall_authenticated') === true) {
            return redirect()->route('firewall.index');
        }

        return view('firewall.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $validUsername = config('firewall.panel_username');
        $validPassword = config('firewall.panel_password');

        if (
            hash_equals((string) $validUsername, (string) $validated['username'])
            && hash_equals((string) $validPassword, (string) $validated['password'])
        ) {
            $request->session()->put('firewall_authenticated', true);
            $request->session()->regenerate();

            return redirect()->intended(route('firewall.index'));
        }

        return back()->withInput($request->only('username'))
            ->withErrors(['login' => 'Username atau password salah.']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('firewall_authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('firewall.login.form')
            ->with('status', 'Berhasil logout.');
    }

    public function updateCredentials(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'username'         => 'required|string|min:3|max:50',
            'password'         => 'required|string|min:6|max:100',
        ]);

        if (! hash_equals((string) config('firewall.panel_password'), (string) $validated['current_password'])) {
            return back()->withErrors(['credentials' => 'Password saat ini tidak sesuai.']);
        }

        $this->updateEnv('FIREWALL_PANEL_USERNAME', $validated['username']);
        $this->updateEnv('FIREWALL_PANEL_PASSWORD', $validated['password']);
        \Artisan::call('config:clear');

        return back()->with('status', 'Username dan password panel berhasil diperbarui.');
    }

    // =========================================================
    //  DASHBOARD
    // =========================================================

    public function index()
    {
        $stats  = $this->firewall->getStats();
        $status = $this->firewall->getStatus();
        $rules  = $this->firewall->listAllRules();

        return view('firewall.index', compact('stats', 'status', 'rules'));
    }

    // =========================================================
    //  STATUS & TOGGLE
    // =========================================================

    public function status(): JsonResponse
    {
        return response()->json($this->firewall->getStatus());
    }

    public function enable(): JsonResponse
    {
        $ok = $this->firewall->enable();
        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Firewall berhasil diaktifkan.' : 'Gagal mengaktifkan firewall.',
        ]);
    }

    public function disable(): JsonResponse
    {
        $ok = $this->firewall->disable();
        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Firewall dinonaktifkan.' : 'Gagal menonaktifkan firewall.',
        ]);
    }

    // =========================================================
    //  RULES
    // =========================================================

    public function rules(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->firewall->listAllRules(),
        ]);
    }

    public function addRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chain'   => 'required|in:INPUT,OUTPUT,FORWARD',
            'proto'   => 'required|in:tcp,udp,icmp,all',
            'src'     => 'nullable|string|max:50',
            'dst'     => 'nullable|string|max:50',
            'port'    => 'nullable|integer|min:1|max:65535',
            'action'  => 'required|in:ACCEPT,DROP,REJECT',
            'comment' => 'nullable|string|max:50',
        ]);

        $result = $this->firewall->addRule($validated);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Rule berhasil ditambahkan.' : 'Gagal: ' . $result['output'],
            'output'  => $result['output'],
        ], $result['success'] ? 200 : 422);
    }

    public function deleteRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chain'       => 'required|in:INPUT,OUTPUT,FORWARD',
            'line_number' => 'required|integer|min:1',
        ]);

        $result = $this->firewall->deleteRule($validated['chain'], $validated['line_number']);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Rule berhasil dihapus.' : 'Gagal: ' . $result['output'],
        ]);
    }

    public function flushRules(Request $request): JsonResponse
    {
        $chain  = $request->input('chain', 'all');
        $result = $this->firewall->flushChain($chain);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? "Chain {$chain} berhasil di-flush." : 'Gagal flush.',
        ]);
    }

    public function setPolicy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chain'  => 'required|in:INPUT,OUTPUT,FORWARD',
            'policy' => 'required|in:ACCEPT,DROP',
        ]);

        $result = $this->firewall->setChainPolicy($validated['chain'], $validated['policy']);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? "Policy {$validated['chain']} diset ke {$validated['policy']}."
                : 'Gagal: ' . $result['output'],
        ]);
    }

    // =========================================================
    //  GANTI PORT
    // =========================================================

    public function changePort(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chain'    => 'required|in:INPUT,OUTPUT,FORWARD',
            'proto'    => 'required|in:tcp,udp',
            'old_port' => 'required|integer|min:1|max:65535',
            'new_port' => 'required|integer|min:1|max:65535|different:old_port',
            'action'   => 'required|in:ACCEPT,DROP,REJECT',
            'comment'  => 'nullable|string|max:50',
        ]);

        $result = $this->firewall->changePort($validated);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? "Port berhasil diubah dari {$validated['old_port']} ke {$validated['new_port']}."
                : 'Gagal: ' . $result['output'],
        ]);
    }

    // =========================================================
    //  SAVE & RESTORE
    // =========================================================

    public function saveRules(): JsonResponse
    {
        $result = $this->firewall->saveRules();
        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Rules disimpan ke /etc/iptables/rules.v4' : 'Gagal menyimpan: ' . $result['output'],
        ]);
    }

    public function restoreRules(): JsonResponse
    {
        $result = $this->firewall->restoreRules();
        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Rules berhasil dipulihkan.' : 'Gagal restore: ' . $result['output'],
        ]);
    }

    public function exportRules(): \Illuminate\Http\Response
    {
        $content = $this->firewall->exportRules();
        return response($content, 200, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => 'attachment; filename="iptables-rules-' . date('Y-m-d-His') . '.txt"',
        ]);
    }

    // =========================================================
    //  LIVE LOGS
    // =========================================================

    public function logs(): JsonResponse
    {
        $lines = request()->integer('lines', 100);
        $logs  = $this->firewall->getLogs($lines);

        return response()->json([
            'success' => true,
            'data'    => $logs,
            'count'   => count($logs),
        ]);
    }

    public function logsStream(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->stream(function () {
            while (true) {
                $logs = $this->firewall->getLogs(20);
                echo 'data: ' . json_encode($logs) . "\n\n";
                ob_flush();
                flush();
                sleep(3);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    // =========================================================
    //  TELEGRAM
    // =========================================================

    public function telegramTest(): JsonResponse
    {
        $ok = $this->firewall->testTelegram();
        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Notifikasi test berhasil dikirim ke Telegram.' : 'Gagal — periksa BOT_TOKEN dan CHAT_ID.',
        ]);
    }

    public function telegramSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bot_token' => 'required|string|min:20',
            'chat_id'   => 'required|string',
        ]);

        // Simpan ke .env (production: gunakan config cache atau database)
        $this->updateEnv('TELEGRAM_BOT_TOKEN', $validated['bot_token']);
        $this->updateEnv('TELEGRAM_CHAT_ID',   $validated['chat_id']);

        // Reload config
        \Artisan::call('config:clear');

        return response()->json(['success' => true, 'message' => 'Konfigurasi Telegram disimpan.']);
    }

    private function updateEnv(string $key, string $value): void
    {
        $path    = base_path('.env');
        $content = file_get_contents($path);

        if (str_contains($content, $key . '=')) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($path, $content);
    }

    // =========================================================
    //  STATS (untuk polling dashboard)
    // =========================================================

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->firewall->getStats(),
            'status'  => $this->firewall->getStatus(),
        ]);
    }

    // =========================================================
    //  FAIL2BAN
    // =========================================================

    public function fail2banStatus(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->firewall->getFail2BanOverview(),
        ]);
    }

    public function fail2banPage()
    {
        return view('firewall.fail2ban');
    }

    public function fail2banInstall(): JsonResponse
    {
        $result = $this->firewall->installFail2Ban();
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }

    public function fail2banSetJail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jail' => 'required|string|max:80',
            'enabled' => 'required|boolean',
        ]);

        $result = $this->firewall->setFail2BanJailState($validated['jail'], (bool) $validated['enabled']);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }

    public function fail2banLogs(): JsonResponse
    {
        $result = $this->firewall->getFail2BanLogs();
        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
