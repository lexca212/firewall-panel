@extends('layouts.firewall')

@section('title', 'Dashboard')

@section('content')

{{-- STATS --}}
<div class="stats-grid">
  <div class="stat c1">
    <div class="stat-label">Total Rules</div>
    <div class="stat-val" id="stat-total">{{ $stats['total_rules'] }}</div>
    <div class="stat-sub">iptables aktif</div>
  </div>
  <div class="stat c2">
    <div class="stat-label">Rules Allow</div>
    <div class="stat-val" id="stat-accept">{{ $stats['accepted'] }}</div>
    <div class="stat-sub">ACCEPT target</div>
  </div>
  <div class="stat c3">
    <div class="stat-label">Rules Blokir</div>
    <div class="stat-val" id="stat-drop">{{ $stats['dropped'] }}</div>
    <div class="stat-sub">DROP/REJECT</div>
  </div>
  <div class="stat c4">
    <div class="stat-label">Koneksi Aktif</div>
    <div class="stat-val" id="stat-conn">{{ $stats['connections'] }}</div>
    <div class="stat-sub">established</div>
  </div>
  <div class="stat c5">
    <div class="stat-label">Total Paket</div>
    <div class="stat-val" id="stat-pkts">{{ number_format($stats['total_packets']) }}</div>
    <div class="stat-sub">dipantau</div>
  </div>
</div>

{{-- PANEL LOGIN CREDENTIALS --}}
<div class="panel" style="margin-bottom:20px">
  <div class="panel-head">
    <span class="panel-title">🔐 Pengaturan Login Panel</span>
  </div>
  <form method="POST" action="{{ route('firewall.credentials.update') }}" class="form-row">
    @csrf
    <div class="fg">
      <label class="flabel">Username Baru</label>
      <input class="finput" type="text" name="username" value="{{ config('firewall.panel_username') }}" style="width:160px" required>
    </div>
    <div class="fg">
      <label class="flabel">Password Baru</label>
      <input class="finput" type="password" name="password" placeholder="Minimal 6 karakter" style="width:180px" required>
    </div>
    <div class="fg">
      <label class="flabel">Password Saat Ini</label>
      <input class="finput" type="password" name="current_password" placeholder="Verifikasi perubahan" style="width:180px" required>
    </div>
    <button class="btn btn-green" type="submit">Simpan Login Baru</button>
  </form>
  @if(session('status'))
    <div style="padding:0 18px 14px;color:var(--accent2);font-size:13px">{{ session('status') }}</div>
  @endif
  @if($errors->has('credentials'))
    <div style="padding:0 18px 14px;color:var(--accent3);font-size:13px">{{ $errors->first('credentials') }}</div>
  @endif
</div>

{{-- CHAIN STATUS --}}
<div class="panel" style="margin-bottom:20px">
  <div class="panel-head">
    <span class="panel-title">⛓ Status Default Policy</span>
    <button class="btn btn-yellow btn-sm" onclick="openModal('modal-policy')">Ubah Policy</button>
  </div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0">
    @foreach(['input_policy' => 'INPUT', 'output_policy' => 'OUTPUT', 'forward_policy' => 'FORWARD'] as $key => $label)
    <div style="padding:16px 20px;text-align:center;border-right:1px solid var(--border)">
      <div style="font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--text2);margin-bottom:6px">{{ $label }}</div>
      <div style="font-family:'Share Tech Mono',monospace;font-size:22px;color:{{ $status[$key] === 'ACCEPT' ? 'var(--accent2)' : 'var(--accent3)' }}">
        {{ $status[$key] ?? 'UNKNOWN' }}
      </div>
    </div>
    @endforeach
  </div>
</div>

{{-- RULE TABLES --}}
<div class="panel">
  <div class="panel-head">
    <span class="panel-title">📋 Rules Firewall</span>
    <div class="btn-group">
      <button class="btn btn-blue btn-sm" onclick="openModal('modal-add-rule')">+ Tambah</button>
      <button class="btn btn-red btn-sm" onclick="flushChain('all')">🗑 Flush All</button>
    </div>
  </div>

  <div class="tabs">
    @foreach(['INPUT','OUTPUT','FORWARD'] as $chain)
    <button class="tab-btn {{ $loop->first ? 'active' : '' }}"
      onclick="switchTab('{{ $chain }}', this)">
      {{ $chain }}
      <span style="font-size:9px;opacity:.6;margin-left:4px">({{ count($rules[$chain] ?? []) }})</span>
    </button>
    @endforeach
  </div>

  @foreach(['INPUT','OUTPUT','FORWARD'] as $chain)
  <div class="tab-content {{ $loop->first ? 'active' : '' }}" id="tab-{{ $chain }}">
    <div style="display:flex;justify-content:flex-end;margin-bottom:10px">
      <button class="btn btn-red btn-sm" onclick="flushChain('{{ $chain }}')">🗑 Flush {{ $chain }}</button>
    </div>
    <table>
      <thead>
        <tr>
          <th>#</th><th>Proto</th><th>Sumber</th><th>Tujuan</th>
          <th>Info</th><th>Target</th><th>Pkts/Bytes</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody id="tbody-{{ $chain }}">
        @forelse($rules[$chain] ?? [] as $rule)
        <tr>
          <td class="mono">{{ $rule['num'] }}</td>
          <td class="mono">{{ $rule['proto'] }}</td>
          <td class="mono">{{ $rule['source'] }}</td>
          <td class="mono">{{ $rule['dest'] }}</td>
          <td class="mono" style="color:var(--text2);font-size:11px">{{ $rule['extra'] ?: '—' }}</td>
          <td>
            @php $badge = match($rule['target']) { 'ACCEPT' => 'b-accept', 'DROP' => 'b-drop', default => 'b-reject' }; @endphp
            <span class="badge {{ $badge }}">{{ $rule['target'] }}</span>
          </td>
          <td class="mono" style="color:var(--text2);font-size:11px">{{ $rule['pkts'] }}/{{ $rule['bytes'] }}</td>
          <td>
            <button class="btn btn-red btn-sm" onclick="deleteRule('{{ $chain }}', {{ $rule['num'] }})">Hapus</button>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:var(--text2);padding:20px">Tidak ada rule pada chain {{ $chain }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @endforeach
</div>

{{-- LIVE LOGS --}}
<div class="panel">
  <div class="panel-head">
    <span class="panel-title">📡 Live Kernel Log</span>
    <div style="display:flex;align-items:center;gap:8px">
      <div class="dot green"></div>
      <span style="font-size:10px;color:var(--text2);letter-spacing:1px">AUTO REFRESH 4s</span>
    </div>
  </div>
  <div class="log-area" id="log-preview">
    <div style="color:var(--text2)">Memuat log...</div>
  </div>
</div>

{{-- FAIL2BAN --}}
<div class="panel">
  <div class="panel-head">
    <span class="panel-title">🛡 Fail2Ban</span>
    <div class="btn-group">
      <button class="btn btn-blue btn-sm" onclick="loadFail2BanStatus()">Refresh</button>
      <button class="btn btn-green btn-sm" id="btn-install-fail2ban" onclick="installFail2Ban()" style="display:none">Install Fail2Ban</button>
    </div>
  </div>

  <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
    <div id="fail2ban-summary" style="font-size:13px;color:var(--text2)">Memuat status Fail2Ban...</div>
  </div>

  <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
    <div class="flabel" style="margin-bottom:8px">Enable / Disable Jail</div>
    <div class="form-row" style="padding:0;border:none;background:none;gap:10px">
      <div class="fg">
        <label class="flabel">Jail</label>
        <select id="fail2ban-jail" class="fselect" style="min-width:200px"></select>
      </div>
      <div class="fg">
        <label class="flabel">Status</label>
        <select id="fail2ban-enabled" class="fselect">
          <option value="1">Enable</option>
          <option value="0">Disable</option>
        </select>
      </div>
      <button class="btn btn-yellow" type="button" onclick="setFail2BanJail()">Simpan Konfigurasi</button>
    </div>
  </div>

  <div style="padding:14px 18px">
    <div class="flabel" style="margin-bottom:8px">Log status fail2ban-client (all + per jail)</div>
    <div id="fail2ban-logs" class="log-area" style="max-height:240px;border:1px solid var(--border);border-radius:4px;padding:10px">
      Memuat log status...
    </div>
  </div>
</div>

<script>
function switchTab(chain, btn) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-'+chain).classList.add('active');
  btn.classList.add('active');
}
</script>

@endsection

@section('scripts')
<script>
const fail2banEndpoints = {
  status: '{{ url('/firewall/fail2ban/status') }}',
  install: '{{ url('/firewall/fail2ban/install') }}',
  jail: '{{ url('/firewall/fail2ban/jail') }}',
  logs: '{{ url('/firewall/fail2ban/logs') }}',
};

async function loadFail2BanStatus() {
  const res = await api(fail2banEndpoints.status);
  if (!res.success) return;

  const data = res.data || {};
  const summary = document.getElementById('fail2ban-summary');
  const installBtn = document.getElementById('btn-install-fail2ban');
  const jailSelect = document.getElementById('fail2ban-jail');

  if (!data.installed) {
    summary.innerHTML = '<span style="color:var(--accent4)">Fail2Ban belum terpasang.</span>';
    installBtn.style.display = 'inline-block';
    jailSelect.innerHTML = '<option value="">-</option>';
    return;
  }

  installBtn.style.display = 'none';
  const activeText = data.active ? 'aktif' : 'tidak aktif';
  summary.innerHTML = `Fail2Ban terpasang dan <b>${activeText}</b>. Jail aktif: <b>${(data.jails || []).map(j => j.name).join(', ') || 'tidak ada'}</b>`;

  const available = Array.isArray(data.available_jails) ? data.available_jails : [];
  jailSelect.innerHTML = available.map(j => `<option value="${j}">${j}</option>`).join('') || '<option value="">Tidak ada jail</option>';
}

async function installFail2Ban() {
  if (!confirm('Install Fail2Ban sekarang?')) return;
  const res = await api(fail2banEndpoints.install, 'POST');
  toast(res.message || 'Proses install selesai.', !res.success);
  if (res.success) {
    await loadFail2BanStatus();
    await loadFail2BanLogs();
  }
}

async function setFail2BanJail() {
  const jail = document.getElementById('fail2ban-jail').value;
  const enabled = document.getElementById('fail2ban-enabled').value === '1';

  if (!jail) {
    toast('Pilih jail terlebih dahulu.', true);
    return;
  }

  const res = await api(fail2banEndpoints.jail, 'POST', { jail, enabled });
  toast(res.message || 'Konfigurasi jail diperbarui.', !res.success);
  if (res.success) {
    await loadFail2BanStatus();
    await loadFail2BanLogs();
  }
}

async function loadFail2BanLogs() {
  const el = document.getElementById('fail2ban-logs');
  const res = await api(fail2banEndpoints.logs);
  if (!res.success) {
    el.innerHTML = `<span style="color:var(--accent4)">${res.message || 'Fail2Ban belum terpasang.'}</span>`;
    return;
  }

  const logs = Array.isArray(res.data) ? res.data : [];
  if (logs.length === 0) {
    el.innerHTML = '<span style="color:var(--text2)">Belum ada output status Fail2Ban.</span>';
    return;
  }

  el.innerHTML = logs.map(item => {
    const safeScope = item.scope || 'all';
    const safeContent = (item.content || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return `<div style="margin-bottom:12px"><div style="color:var(--accent);margin-bottom:4px">[${safeScope}]</div><pre style="white-space:pre-wrap;color:var(--text2)">${safeContent}</pre></div>`;
  }).join('');
}

loadFail2BanStatus();
loadFail2BanLogs();
</script>
@endsection
