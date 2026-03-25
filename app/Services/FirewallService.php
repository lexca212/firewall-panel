<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FirewallService
{
    /**
     * Jalankan perintah sistem dengan sudo.
     * www-data harus dikonfigurasi di /etc/sudoers.d/firewall
     */
    private function exec(string $command): array
    {
        $fullCommand = "sudo {$command} 2>&1";
        exec($fullCommand, $output, $returnCode);

        Log::channel('firewall')->info("CMD: {$command}", [
            'output'      => implode("\n", $output),
            'return_code' => $returnCode,
        ]);

        return [
            'success' => $returnCode === 0,
            'output'  => implode("\n", $output),
            'code'    => $returnCode,
        ];
    }

    // =========================================================
    //  STATUS FIREWALL
    // =========================================================

    public function getStatus(): array
    {
        $result = $this->exec('iptables -L -n --line-numbers -v');

        $inputPolicy   = $this->getChainPolicy('INPUT');
        $outputPolicy  = $this->getChainPolicy('OUTPUT');
        $forwardPolicy = $this->getChainPolicy('FORWARD');

        return [
            'active'          => $result['success'],
            'input_policy'    => $inputPolicy,
            'output_policy'   => $outputPolicy,
            'forward_policy'  => $forwardPolicy,
            'raw_output'      => $result['output'],
        ];
    }

    private function getChainPolicy(string $chain): string
    {
        $result = $this->exec("iptables -L {$chain} -n | head -1");
        if (preg_match('/policy\s+(\w+)/', $result['output'], $m)) {
            return strtoupper($m[1]);
        }
        return 'UNKNOWN';
    }

    public function enable(): bool
    {
        // Terapkan rules yang tersimpan
        $result = $this->exec('iptables-restore < /etc/iptables/rules.v4');
        if ($result['success']) {
            $this->notifyTelegram('🔒 *Firewall DIAKTIFKAN* pada server `' . gethostname() . '`');
        }
        return $result['success'];
    }

    public function disable(): bool
    {
        // Flush semua rules dan set default ACCEPT
        $this->exec('iptables -F');
        $this->exec('iptables -X');
        $this->exec('iptables -P INPUT ACCEPT');
        $this->exec('iptables -P OUTPUT ACCEPT');
        $this->exec('iptables -P FORWARD ACCEPT');

        $this->notifyTelegram('⚠️ *Firewall DINONAKTIFKAN* pada server `' . gethostname() . '`');
        return true;
    }

    // =========================================================
    //  RULES
    // =========================================================

    public function listRules(string $chain = 'INPUT'): array
    {
        $chain  = strtoupper($chain);
        $result = $this->exec("iptables -L {$chain} -n --line-numbers -v");

        return $this->parseRules($result['output'], $chain);
    }

    public function listAllRules(): array
    {
        $chains = ['INPUT', 'OUTPUT', 'FORWARD'];
        $all    = [];
        foreach ($chains as $chain) {
            $all[$chain] = $this->listRules($chain);
        }
        return $all;
    }

    private function parseRules(string $output, string $chain): array
    {
        $rules = [];
        $lines = explode("\n", $output);
        $parsing = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Lewati header
            if (str_starts_with($line, 'Chain') || str_starts_with($line, 'pkts') || $line === '') {
                $parsing = str_starts_with($line, 'pkts');
                continue;
            }

            if ($parsing || preg_match('/^\d+\s+/', $line)) {
                $parts = preg_split('/\s+/', $line, 10);
                if (count($parts) >= 9) {
                    $rules[] = [
                        'num'     => (int)   $parts[0],
                        'pkts'    =>         $parts[1],
                        'bytes'   =>         $parts[2],
                        'target'  => strtoupper($parts[3]),
                        'proto'   =>         $parts[4],
                        'opt'     =>         $parts[5],
                        'in'      =>         $parts[6],
                        'out'     =>         $parts[7],
                        'source'  =>         $parts[8],
                        'dest'    => isset($parts[9]) ? explode(' ', $parts[9])[0] : '0.0.0.0/0',
                        'extra'   => isset($parts[9]) ? substr($parts[9], strpos($parts[9],' ')+1) : '',
                        'chain'   => $chain,
                    ];
                }
            }
        }
        return $rules;
    }

    public function addRule(array $data): array
    {
        $chain  = strtoupper($data['chain']  ?? 'INPUT');
        $proto  = strtolower($data['proto']  ?? 'tcp');
        $src    = $data['src']    ?? '0.0.0.0/0';
        $dst    = $data['dst']    ?? '0.0.0.0/0';
        $port   = $data['port']   ?? null;
        $action = strtoupper($data['action'] ?? 'ACCEPT');
        $comment= $data['comment'] ?? '';

        // Build command
        $cmd = "iptables -A {$chain}";
        $cmd .= " -p {$proto}";

        if ($src !== '0.0.0.0/0') {
            $cmd .= " -s " . escapeshellarg($src);
        }
        if ($dst !== '0.0.0.0/0') {
            $cmd .= " -d " . escapeshellarg($dst);
        }
        if ($port && $port !== 'any' && in_array($proto, ['tcp', 'udp'])) {
            $cmd .= " --dport " . (int)$port;
        }
        if ($comment) {
            $safeComment = escapeshellarg(substr(preg_replace('/[^a-zA-Z0-9 _\-]/', '', $comment), 0, 50));
            $cmd .= " -m comment --comment {$safeComment}";
        }
        $cmd .= " -j {$action}";

        $result = $this->exec($cmd);

        if ($result['success']) {
            $this->notifyTelegram(
                "➕ *Rule Ditambahkan*\n" .
                "Chain: `{$chain}` | Proto: `{$proto}` | Port: `{$port}` | Action: `{$action}`\n" .
                "Keterangan: {$comment}"
            );
        }

        return $result;
    }

    public function deleteRule(string $chain, int $lineNumber): array
    {
        $chain = strtoupper($chain);
        $result = $this->exec("iptables -D {$chain} {$lineNumber}");

        if ($result['success']) {
            $this->notifyTelegram("🗑️ *Rule Dihapus*\nChain: `{$chain}` | Line: `{$lineNumber}`");
        }
        return $result;
    }

    public function flushChain(string $chain = 'all'): array
    {
        if ($chain === 'all') {
            $r = $this->exec('iptables -F');
            $this->notifyTelegram('🧹 *Semua rules di-FLUSH* pada server `' . gethostname() . '`');
        } else {
            $r = $this->exec('iptables -F ' . strtoupper($chain));
            $this->notifyTelegram("🧹 *Flush Chain* `{$chain}`");
        }
        return $r;
    }

    public function setChainPolicy(string $chain, string $policy): array
    {
        $chain  = strtoupper($chain);
        $policy = strtoupper($policy);

        if (!in_array($policy, ['ACCEPT', 'DROP'])) {
            return ['success' => false, 'output' => 'Policy tidak valid. Gunakan ACCEPT atau DROP.'];
        }

        $result = $this->exec("iptables -P {$chain} {$policy}");
        if ($result['success']) {
            $this->notifyTelegram("⚙️ *Policy Chain Diubah*\nChain: `{$chain}` → Policy: `{$policy}`");
        }
        return $result;
    }

    // =========================================================
    //  GANTI PORT
    // =========================================================

    /**
     * Ubah port layanan (misal SSH 22 → 2222)
     * Hapus rule lama kemudian tambahkan rule baru
     */
    public function changePort(array $data): array
    {
        $proto   = strtolower($data['proto']  ?? 'tcp');
        $oldPort = (int)($data['old_port'] ?? 0);
        $newPort = (int)($data['new_port'] ?? 0);
        $chain   = strtoupper($data['chain'] ?? 'INPUT');
        $action  = strtoupper($data['action'] ?? 'ACCEPT');
        $comment = $data['comment'] ?? "Port {$newPort}";

        if ($oldPort < 1 || $newPort < 1 || $oldPort > 65535 || $newPort > 65535) {
            return ['success' => false, 'output' => 'Port tidak valid (1-65535).'];
        }

        // Hapus rule lama yang cocok
        $this->exec("iptables -D {$chain} -p {$proto} --dport {$oldPort} -j {$action}");

        // Tambah rule baru
        $addResult = $this->addRule([
            'chain'   => $chain,
            'proto'   => $proto,
            'port'    => $newPort,
            'action'  => $action,
            'comment' => $comment,
        ]);

        if ($addResult['success']) {
            $this->notifyTelegram(
                "🔄 *Port Diubah*\n" .
                "Proto: `{$proto}` | `{$oldPort}` → `{$newPort}`\n" .
                "Chain: `{$chain}` | Action: `{$action}`"
            );
        }

        return $addResult;
    }

    // =========================================================
    //  SAVE & RESTORE
    // =========================================================

    public function saveRules(): array
    {
        $this->exec('mkdir -p /etc/iptables');
        $result = $this->exec('iptables-save > /etc/iptables/rules.v4');

        if ($result['success']) {
            $this->notifyTelegram('💾 *Rules Disimpan* ke `/etc/iptables/rules.v4`');
        }
        return $result;
    }

    public function restoreRules(): array
    {
        $result = $this->exec('iptables-restore < /etc/iptables/rules.v4');
        if ($result['success']) {
            $this->notifyTelegram('♻️ *Rules Dipulihkan* dari `/etc/iptables/rules.v4`');
        }
        return $result;
    }

    public function exportRules(): string
    {
        $result = $this->exec('iptables-save');
        return $result['output'];
    }

    // =========================================================
    //  LIVE LOGS
    // =========================================================

    /**
     * Baca N baris terakhir dari log kernel (journalctl / /var/log/kern.log)
     */
    public function getLogs(int $lines = 100): array
    {
        // Coba journalctl dulu, fallback ke kern.log
        $result = $this->exec("journalctl -k --no-pager -n {$lines} 2>/dev/null | grep -i 'iptables\\|firewall\\|DROP\\|REJECT\\|ACCEPT' | tail -n {$lines}");

        if (empty(trim($result['output']))) {
            $result = $this->exec("tail -n {$lines} /var/log/kern.log 2>/dev/null | grep -i 'iptables\\|DROP\\|REJECT'");
        }

        return $this->parseLogs($result['output']);
    }

    private function parseLogs(string $raw): array
    {
        $logs  = [];
        $lines = array_filter(explode("\n", $raw));

        foreach ($lines as $line) {
            $log = [
                'raw'     => $line,
                'time'    => '',
                'action'  => 'INFO',
                'src'     => '',
                'dst'     => '',
                'proto'   => '',
                'spt'     => '',
                'dpt'     => '',
                'in'      => '',
                'out'     => '',
            ];

            // Waktu
            if (preg_match('/^(\w{3}\s+\d+\s+[\d:]+)/', $line, $m)) {
                $log['time'] = $m[1];
            }

            // Action
            if (str_contains($line, 'DROP'))   $log['action'] = 'DROP';
            elseif (str_contains($line, 'REJECT')) $log['action'] = 'REJECT';
            elseif (str_contains($line, 'ACCEPT')) $log['action'] = 'ACCEPT';

            // Fields
            if (preg_match('/SRC=([\d\.]+)/',  $line, $m)) $log['src']   = $m[1];
            if (preg_match('/DST=([\d\.]+)/',  $line, $m)) $log['dst']   = $m[1];
            if (preg_match('/PROTO=(\w+)/',    $line, $m)) $log['proto'] = $m[1];
            if (preg_match('/SPT=(\d+)/',      $line, $m)) $log['spt']   = $m[1];
            if (preg_match('/DPT=(\d+)/',      $line, $m)) $log['dpt']   = $m[1];
            if (preg_match('/IN=(\S*)/',        $line, $m)) $log['in']    = $m[1];
            if (preg_match('/OUT=(\S*)/',       $line, $m)) $log['out']   = $m[1];

            $logs[] = $log;
        }

        return array_reverse($logs); // terbaru di atas
    }

    // =========================================================
    //  STATISTIK
    // =========================================================

    public function getStats(): array
    {
        $totalRules = 0;
        $accepted   = 0;
        $dropped    = 0;

        foreach (['INPUT', 'OUTPUT', 'FORWARD'] as $chain) {
            $rules = $this->listRules($chain);
            $totalRules += count($rules);
            foreach ($rules as $r) {
                if ($r['target'] === 'ACCEPT') $accepted++;
                if (in_array($r['target'], ['DROP', 'REJECT'])) $dropped++;
            }
        }

        // Koneksi aktif
        $connResult = $this->exec('ss -tn state established | wc -l');
        $connections = max(0, (int)trim($connResult['output']) - 1);

        // Packets dari iptables -L -v
        $pktResult = $this->exec('iptables -L -v -n | awk \'{sum+=$1} END{print sum}\'');
        $totalPkts = (int)trim($pktResult['output']);

        return [
            'total_rules'  => $totalRules,
            'accepted'     => $accepted,
            'dropped'      => $dropped,
            'connections'  => $connections,
            'total_packets'=> $totalPkts,
        ];
    }

    // =========================================================
    //  TELEGRAM NOTIFICATION
    // =========================================================

    public function notifyTelegram(string $message): void
    {
        $botToken = config('firewall.telegram_bot_token');
        $chatId   = config('firewall.telegram_chat_id');

        if (empty($botToken) || empty($chatId)) {
            return;
        }

        try {
            Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => "🖥 *FirePanel — " . gethostname() . "*\n\n" . $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable $e) {
            Log::channel('firewall')->error('Telegram notification gagal: ' . $e->getMessage());
        }
    }

    public function testTelegram(): bool
    {
        try {
            $this->notifyTelegram('✅ *Test Notifikasi Berhasil!*\nFirePanel terhubung ke Telegram.');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
