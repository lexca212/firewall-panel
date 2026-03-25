@extends('layouts.firewall')

@section('title', 'Fail2Ban')

@section('content')
<div class="panel">
  <div class="panel-head">
    <span class="panel-title">🛡 Fail2Ban Manager</span>
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
        <select id="fail2ban-jail" class="fselect" style="min-width:220px"></select>
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
    <div id="fail2ban-logs" class="log-area" style="max-height:320px;border:1px solid var(--border);border-radius:4px;padding:10px">
      Memuat log status...
    </div>
  </div>
</div>
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
  const summary = document.getElementById('fail2ban-summary');
  const installBtn = document.getElementById('btn-install-fail2ban');
  const jailSelect = document.getElementById('fail2ban-jail');
  try {
    const res = await api(fail2banEndpoints.status);
    if (!res.success) {
      summary.innerHTML = '<span style="color:var(--accent3)">Gagal membaca status Fail2Ban.</span>';
      return;
    }

    const data = res.data || {};
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
  } catch (err) {
    summary.innerHTML = '<span style="color:var(--accent3)">Status Fail2Ban tidak bisa dimuat.</span>';
  }
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
  if (!jail) return toast('Pilih jail terlebih dahulu.', true);

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
  el.innerHTML = logs.length
    ? logs.map(item => `<div style="margin-bottom:12px"><div style="color:var(--accent);margin-bottom:4px">[${item.scope || 'all'}]</div><pre style="white-space:pre-wrap;color:var(--text2)">${(item.content || '').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</pre></div>`).join('')
    : '<span style="color:var(--text2)">Belum ada output status Fail2Ban.</span>';
}

loadFail2BanStatus();
loadFail2BanLogs();
</script>
@endsection
