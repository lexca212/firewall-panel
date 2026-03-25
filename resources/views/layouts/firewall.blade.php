<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>FirePanel — @yield('title', 'Dashboard')</title>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:#080c10;--bg2:#0d1117;--bg3:#111820;--panel:#0a1520;
    --border:#1a2f45;--accent:#00d4ff;--accent2:#00ff88;
    --accent3:#ff4444;--accent4:#ffaa00;--accent5:#b080ff;
    --text:#c8d8e8;--text2:#6a8aa0;
    --glow:0 0 12px rgba(0,212,255,.35);
    --glow2:0 0 12px rgba(0,255,136,.35);
    --glow3:0 0 12px rgba(255,68,68,.35);
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:var(--bg);color:var(--text);font-family:'Rajdhani',sans-serif;font-weight:500;min-height:100vh;overflow-x:hidden}
  body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,212,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,212,255,.03) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;z-index:0}

  /* TOPBAR */
  .topbar{position:sticky;top:0;z-index:100;background:rgba(8,12,16,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 24px;height:54px}
  .logo{display:flex;align-items:center;gap:12px}
  .logo-hex{width:30px;height:30px;background:linear-gradient(135deg,var(--accent),var(--accent2));clip-path:polygon(50% 0%,100% 25%,100% 75%,50% 100%,0% 75%,0% 25%);display:flex;align-items:center;justify-content:center;font-size:13px;animation:pulse-hex 3s ease-in-out infinite}
  @keyframes pulse-hex{0%,100%{box-shadow:0 0 0 0 rgba(0,212,255,.4)}50%{box-shadow:0 0 0 8px rgba(0,212,255,0)}}
  .logo-text{font-size:17px;font-weight:700;letter-spacing:3px;text-transform:uppercase;background:linear-gradient(90deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
  .topbar-right{display:flex;align-items:center;gap:16px}
  .fw-badge{display:flex;align-items:center;gap:7px;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--text2)}
  .dot{width:8px;height:8px;border-radius:50%;animation:blink 2s ease-in-out infinite}
  .dot.green{background:var(--accent2);box-shadow:var(--glow2)}
  .dot.red{background:var(--accent3);box-shadow:var(--glow3)}
  @keyframes blink{0%,100%{opacity:1}50%{opacity:.4}}
  .host-chip{font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--accent);border:1px solid var(--border);padding:3px 9px;border-radius:3px}

  /* LAYOUT */
  .layout{position:relative;z-index:1;display:flex;min-height:calc(100vh - 54px)}

  /* SIDEBAR */
  .sidebar{width:210px;flex-shrink:0;background:rgba(10,21,32,.7);border-right:1px solid var(--border);padding:18px 0;display:flex;flex-direction:column}
  .nav-group{margin-bottom:24px}
  .nav-label{font-size:9px;letter-spacing:2.5px;color:var(--text2);text-transform:uppercase;padding:0 18px 8px}
  .nav-item{display:flex;align-items:center;gap:10px;padding:9px 18px;cursor:pointer;border-left:2px solid transparent;transition:all .2s;font-size:14px;letter-spacing:.5px;color:var(--text2);text-decoration:none}
  .nav-item:hover,.nav-item.active{color:var(--accent);background:rgba(0,212,255,.05);border-left-color:var(--accent)}
  .nav-item .ico{width:15px;text-align:center;font-size:14px}
  .sidebar-bottom{margin-top:auto;padding:12px 18px;border-top:1px solid var(--border)}
  .sidebar-bottom .nav-item{padding:9px 0;border-left:none;border-radius:3px}

  /* MAIN */
  .main{flex:1;padding:24px;overflow:auto;max-width:calc(100vw - 210px)}

  /* PANELS */
  .panel{background:var(--panel);border:1px solid var(--border);border-radius:4px;margin-bottom:20px;overflow:hidden}
  .panel-head{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid var(--border);background:rgba(0,212,255,.03)}
  .panel-title{font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:var(--accent)}

  /* STATS */
  .stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px}
  .stat{background:var(--panel);border:1px solid var(--border);border-radius:4px;padding:16px;position:relative;overflow:hidden;transition:border-color .2s}
  .stat:hover{border-color:var(--accent)}
  .stat::before{content:'';position:absolute;top:0;left:0;right:0;height:2px}
  .stat.c1::before{background:var(--accent)} .stat.c2::before{background:var(--accent2)} .stat.c3::before{background:var(--accent3)} .stat.c4::before{background:var(--accent4)} .stat.c5::before{background:var(--accent5)}
  .stat-label{font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--text2);margin-bottom:8px}
  .stat-val{font-family:'Share Tech Mono',monospace;font-size:26px;line-height:1;margin-bottom:4px}
  .stat.c1 .stat-val{color:var(--accent)} .stat.c2 .stat-val{color:var(--accent2)} .stat.c3 .stat-val{color:var(--accent3)} .stat.c4 .stat-val{color:var(--accent4)} .stat.c5 .stat-val{color:var(--accent5)}
  .stat-sub{font-size:10px;color:var(--text2)}

  /* BUTTONS */
  .btn{padding:6px 14px;border-radius:3px;font-family:'Rajdhani',sans-serif;font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;cursor:pointer;border:1px solid;transition:all .2s}
  .btn-blue{border-color:var(--accent);color:var(--accent);background:rgba(0,212,255,.08)}
  .btn-blue:hover{background:var(--accent);color:#000;box-shadow:var(--glow)}
  .btn-green{border-color:var(--accent2);color:var(--accent2);background:rgba(0,255,136,.06)}
  .btn-green:hover{background:var(--accent2);color:#000;box-shadow:var(--glow2)}
  .btn-red{border-color:var(--accent3);color:var(--accent3);background:rgba(255,68,68,.06)}
  .btn-red:hover{background:var(--accent3);color:#fff;box-shadow:var(--glow3)}
  .btn-yellow{border-color:var(--accent4);color:var(--accent4);background:rgba(255,170,0,.06)}
  .btn-yellow:hover{background:var(--accent4);color:#000}
  .btn-purple{border-color:var(--accent5);color:var(--accent5);background:rgba(176,128,255,.06)}
  .btn-purple:hover{background:var(--accent5);color:#000}
  .btn-sm{padding:3px 9px;font-size:10px}
  .btn-group{display:flex;gap:8px;flex-wrap:wrap}

  /* TABLE */
  table{width:100%;border-collapse:collapse}
  thead tr{background:rgba(0,0,0,.3)}
  th{text-align:left;padding:9px 14px;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--text2);font-weight:600;border-bottom:1px solid var(--border)}
  td{padding:10px 14px;border-bottom:1px solid rgba(26,47,69,.5);font-size:13px}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:rgba(0,212,255,.025)}
  .mono{font-family:'Share Tech Mono',monospace;font-size:12px}

  /* BADGES */
  .badge{display:inline-block;padding:1px 7px;border-radius:2px;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;font-weight:700}
  .b-accept{background:rgba(0,255,136,.12);color:var(--accent2);border:1px solid rgba(0,255,136,.25)}
  .b-drop{background:rgba(255,170,0,.12);color:var(--accent4);border:1px solid rgba(255,170,0,.25)}
  .b-reject{background:rgba(255,68,68,.12);color:var(--accent3);border:1px solid rgba(255,68,68,.25)}
  .b-input{background:rgba(0,212,255,.1);color:var(--accent);border:1px solid rgba(0,212,255,.2)}
  .b-output{background:rgba(176,128,255,.1);color:var(--accent5);border:1px solid rgba(176,128,255,.2)}
  .b-forward{background:rgba(255,170,0,.1);color:var(--accent4);border:1px solid rgba(255,170,0,.2)}

  /* FORM */
  .form-row{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;padding:16px 18px;border-top:1px solid var(--border);background:rgba(0,0,0,.2)}
  .fg{display:flex;flex-direction:column;gap:5px}
  .flabel{font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:var(--text2)}
  .finput,.fselect{background:var(--bg3);border:1px solid var(--border);color:var(--text);padding:6px 10px;border-radius:3px;font-family:'Share Tech Mono',monospace;font-size:12px;outline:none;transition:border-color .2s}
  .finput:focus,.fselect:focus{border-color:var(--accent)}
  .fselect{min-width:100px} option{background:var(--bg3)}

  /* LOGS */
  .log-area{padding:14px 18px;font-family:'Share Tech Mono',monospace;font-size:12px;line-height:1.9;max-height:280px;overflow-y:auto;color:var(--text2)}
  .log-area::-webkit-scrollbar{width:3px}
  .log-area::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
  .log-line{display:flex;gap:10px;align-items:baseline}
  .log-time{color:var(--text2);min-width:90px;flex-shrink:0}
  .log-accept{color:var(--accent2)} .log-drop{color:var(--accent4)} .log-reject{color:var(--accent3)}

  /* MODAL */
  .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:500;display:none;align-items:center;justify-content:center}
  .modal-overlay.show{display:flex}
  .modal{background:var(--bg2);border:1px solid var(--border);border-radius:6px;padding:28px;min-width:400px;max-width:560px;width:100%}
  .modal-title{font-size:14px;letter-spacing:2px;text-transform:uppercase;color:var(--accent);margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border)}
  .modal-body{display:flex;flex-direction:column;gap:14px}
  .modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:14px;border-top:1px solid var(--border)}

  /* TOAST */
  .toast{position:fixed;bottom:22px;right:22px;background:var(--panel);border-radius:4px;padding:11px 18px;font-size:13px;transform:translateX(200%);transition:transform .3s;z-index:999;border-left:3px solid var(--accent2)}
  .toast.show{transform:translateX(0)}
  .toast.err{border-left-color:var(--accent3);color:var(--accent3)}

  /* TWO COL */
  .two-col{display:grid;grid-template-columns:1fr 1fr;gap:18px}
  @media(max-width:1200px){.stats-grid{grid-template-columns:repeat(3,1fr)}.two-col{grid-template-columns:1fr}}
  @media(max-width:800px){.stats-grid{grid-template-columns:repeat(2,1fr)}.sidebar{display:none}.main{max-width:100vw}}

  /* SECTION TABS */
  .tabs{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:0}
  .tab-btn{padding:10px 20px;font-size:12px;letter-spacing:1px;text-transform:uppercase;cursor:pointer;border:none;background:none;color:var(--text2);border-bottom:2px solid transparent;transition:all .2s;font-family:'Rajdhani',sans-serif;font-weight:600}
  .tab-btn.active,.tab-btn:hover{color:var(--accent);border-bottom-color:var(--accent)}
  .tab-content{display:none;padding:18px} .tab-content.active{display:block}

  /* TOGGLE */
  .toggle-sw{width:36px;height:18px;background:var(--border);border-radius:9px;cursor:pointer;position:relative;transition:background .2s;flex-shrink:0}
  .toggle-sw.on{background:var(--accent2)}
  .toggle-sw::after{content:'';position:absolute;top:3px;left:3px;width:12px;height:12px;border-radius:50%;background:#fff;transition:left .2s}
  .toggle-sw.on::after{left:21px}
</style>
@yield('head')
</head>
<body>

<div class="topbar">
  <div class="logo">
    <div class="logo-hex">🔥</div>
    <span class="logo-text">FirePanel</span>
  </div>
  <div class="topbar-right">
    <div class="fw-badge">
      <div class="dot green" id="fw-dot"></div>
      <span id="fw-status-label">Firewall Aktif</span>
    </div>
    <span class="host-chip" id="host-chip">{{ php_uname('n') }} · iptables</span>
  </div>
</div>

<div class="layout">
  <nav class="sidebar">
    <div class="nav-group">
      <div class="nav-label">Panel</div>
      <a href="{{ route('firewall.index') }}" class="nav-item {{ request()->routeIs('firewall.index') ? 'active' : '' }}">
        <span class="ico">⬡</span> Dashboard
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-label">Firewall</div>
      <a href="#" class="nav-item" onclick="openModal('modal-add-rule')">
        <span class="ico">＋</span> Tambah Rule
      </a>
      <a href="#" class="nav-item" onclick="openModal('modal-policy')">
        <span class="ico">⚙</span> Set Policy
      </a>
      <a href="#" class="nav-item" onclick="openModal('modal-port')">
        <span class="ico">🔄</span> Ganti Port
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-label">Data</div>
      <a href="#" class="nav-item" onclick="saveRules()">
        <span class="ico">💾</span> Simpan Rules
      </a>
      <a href="#" class="nav-item" onclick="restoreRules()">
        <span class="ico">♻</span> Restore Rules
      </a>
      <a href="{{ route('firewall.export') }}" class="nav-item">
        <span class="ico">⬇</span> Export Rules
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-label">Notifikasi</div>
      <a href="#" class="nav-item" onclick="openModal('modal-telegram')">
        <span class="ico">✈</span> Telegram Setup
      </a>
    </div>
    <div class="sidebar-bottom">
      <a href="#" class="nav-item" id="btn-toggle-fw" onclick="toggleFirewall()">
        <span class="ico" id="toggle-fw-ico">⏼</span>
        <span id="toggle-fw-label">Nonaktifkan FW</span>
      </a>
      <form method="POST" action="{{ route('firewall.logout') }}" style="margin-top:10px">
        @csrf
        <button class="btn btn-red" style="width:100%" type="submit">Logout Panel</button>
      </form>
    </div>
  </nav>

  <main class="main">
    @yield('content')
  </main>
</div>

<!-- MODAL: TAMBAH RULE -->
<div class="modal-overlay" id="modal-add-rule">
  <div class="modal">
    <div class="modal-title">➕ Tambah Rule Baru</div>
    <div class="modal-body">
      <div class="form-row" style="border:none;padding:0;background:none;gap:14px">
        <div class="fg"><label class="flabel">Chain</label>
          <select class="fselect" id="r-chain"><option>INPUT</option><option>OUTPUT</option><option>FORWARD</option></select></div>
        <div class="fg"><label class="flabel">Protokol</label>
          <select class="fselect" id="r-proto"><option>tcp</option><option>udp</option><option>icmp</option><option>all</option></select></div>
        <div class="fg"><label class="flabel">Sumber IP</label>
          <input class="finput" id="r-src" placeholder="0.0.0.0/0" style="width:140px"></div>
        <div class="fg"><label class="flabel">Dest IP</label>
          <input class="finput" id="r-dst" placeholder="0.0.0.0/0" style="width:140px"></div>
        <div class="fg"><label class="flabel">Port</label>
          <input class="finput" id="r-port" placeholder="22" style="width:70px"></div>
        <div class="fg"><label class="flabel">Aksi</label>
          <select class="fselect" id="r-action"><option>ACCEPT</option><option>DROP</option><option>REJECT</option></select></div>
        <div class="fg" style="flex:1"><label class="flabel">Keterangan</label>
          <input class="finput" id="r-comment" placeholder="Deskripsi..." style="width:100%"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-red" onclick="closeModal('modal-add-rule')">Batal</button>
      <button class="btn btn-green" onclick="submitAddRule()">Tambah Rule</button>
    </div>
  </div>
</div>

<!-- MODAL: SET POLICY -->
<div class="modal-overlay" id="modal-policy">
  <div class="modal">
    <div class="modal-title">⚙ Set Chain Policy</div>
    <div class="modal-body">
      <div class="form-row" style="border:none;padding:0;background:none">
        <div class="fg"><label class="flabel">Chain</label>
          <select class="fselect" id="p-chain"><option>INPUT</option><option>OUTPUT</option><option>FORWARD</option></select></div>
        <div class="fg"><label class="flabel">Policy</label>
          <select class="fselect" id="p-policy"><option>ACCEPT</option><option>DROP</option></select></div>
      </div>
      <p style="font-size:12px;color:var(--accent4);padding:0 2px">⚠ Mengubah policy DEFAULT dapat memblokir semua koneksi jika tidak ada rule allow.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-red" onclick="closeModal('modal-policy')">Batal</button>
      <button class="btn btn-yellow" onclick="submitPolicy()">Terapkan</button>
    </div>
  </div>
</div>

<!-- MODAL: GANTI PORT -->
<div class="modal-overlay" id="modal-port">
  <div class="modal">
    <div class="modal-title">🔄 Ganti Port</div>
    <div class="modal-body">
      <div class="form-row" style="border:none;padding:0;background:none;gap:14px">
        <div class="fg"><label class="flabel">Chain</label>
          <select class="fselect" id="cp-chain"><option>INPUT</option><option>OUTPUT</option></select></div>
        <div class="fg"><label class="flabel">Protokol</label>
          <select class="fselect" id="cp-proto"><option>tcp</option><option>udp</option></select></div>
        <div class="fg"><label class="flabel">Port Lama</label>
          <input class="finput" id="cp-old" placeholder="22" style="width:80px"></div>
        <div class="fg"><label class="flabel">Port Baru</label>
          <input class="finput" id="cp-new" placeholder="2222" style="width:80px"></div>
        <div class="fg"><label class="flabel">Aksi</label>
          <select class="fselect" id="cp-action"><option>ACCEPT</option><option>DROP</option><option>REJECT</option></select></div>
        <div class="fg" style="flex:1"><label class="flabel">Keterangan</label>
          <input class="finput" id="cp-comment" placeholder="Misal: SSH custom port" style="width:100%"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-red" onclick="closeModal('modal-port')">Batal</button>
      <button class="btn btn-blue" onclick="submitChangePort()">Ubah Port</button>
    </div>
  </div>
</div>

<!-- MODAL: TELEGRAM -->
<div class="modal-overlay" id="modal-telegram">
  <div class="modal">
    <div class="modal-title">✈ Konfigurasi Telegram</div>
    <div class="modal-body">
      <div class="fg"><label class="flabel">Bot Token</label>
        <input class="finput" id="tg-token" placeholder="1234567890:ABCdefGHI..." style="width:100%"></div>
      <div class="fg"><label class="flabel">Chat ID</label>
        <input class="finput" id="tg-chatid" placeholder="-100xxxxxxxxxx" style="width:100%"></div>
      <p style="font-size:11px;color:var(--text2)">
        1. Buat bot via <b>@BotFather</b> → salin token.<br>
        2. Tambahkan bot ke grup/channel → kirim pesan.<br>
        3. Dapatkan Chat ID dari <b>@userinfobot</b> atau API.
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-red" onclick="closeModal('modal-telegram')">Tutup</button>
      <button class="btn btn-purple" onclick="testTelegram()">Test Kirim</button>
      <button class="btn btn-green" onclick="saveTelegram()">Simpan</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ---- MODAL ----
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if(e.target===el) el.classList.remove('show'); });
});

// ---- TOAST ----
let toastTimer;
function toast(msg, err=false) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = 'toast show' + (err?' err':'');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('show'), 3000);
}

// ---- API HELPER ----
async function api(url, method='GET', body=null) {
  const opts = { method, headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF } };
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(url, opts);
  return res.json();
}

// ---- FIREWALL TOGGLE ----
let fwActive = true;
async function toggleFirewall() {
  const endpoint = fwActive ? '{{ route("firewall.disable") }}' : '{{ route("firewall.enable") }}';
  const data = await api(endpoint, 'POST');
  if (data.success) {
    fwActive = !fwActive;
    document.getElementById('fw-dot').className   = 'dot ' + (fwActive?'green':'red');
    document.getElementById('fw-status-label').textContent = fwActive ? 'Firewall Aktif' : 'Firewall NONAKTIF';
    document.getElementById('toggle-fw-label').textContent = fwActive ? 'Nonaktifkan FW' : 'Aktifkan FW';
    toast(data.message);
  } else toast(data.message, true);
}

// ---- SAVE / RESTORE ----
async function saveRules() {
  const data = await api('{{ route("firewall.save") }}', 'POST');
  toast(data.message, !data.success);
}
async function restoreRules() {
  if(!confirm('Pulihkan rules dari /etc/iptables/rules.v4?')) return;
  const data = await api('{{ route("firewall.restore") }}', 'POST');
  toast(data.message, !data.success);
  if(data.success) setTimeout(()=>location.reload(), 1200);
}

// ---- ADD RULE ----
async function submitAddRule() {
  const body = {
    chain:   document.getElementById('r-chain').value,
    proto:   document.getElementById('r-proto').value,
    src:     document.getElementById('r-src').value   || '0.0.0.0/0',
    dst:     document.getElementById('r-dst').value   || '0.0.0.0/0',
    port:    document.getElementById('r-port').value  || null,
    action:  document.getElementById('r-action').value,
    comment: document.getElementById('r-comment').value,
  };
  const data = await api('{{ route("firewall.rules.add") }}', 'POST', body);
  toast(data.message, !data.success);
  if(data.success) { closeModal('modal-add-rule'); refreshRules(); }
}

// ---- SET POLICY ----
async function submitPolicy() {
  const body = { chain: document.getElementById('p-chain').value, policy: document.getElementById('p-policy').value };
  const data = await api('{{ route("firewall.rules.policy") }}', 'POST', body);
  toast(data.message, !data.success);
  if(data.success) closeModal('modal-policy');
}

// ---- CHANGE PORT ----
async function submitChangePort() {
  const body = {
    chain:    document.getElementById('cp-chain').value,
    proto:    document.getElementById('cp-proto').value,
    old_port: document.getElementById('cp-old').value,
    new_port: document.getElementById('cp-new').value,
    action:   document.getElementById('cp-action').value,
    comment:  document.getElementById('cp-comment').value,
  };
  const data = await api('{{ route("firewall.change-port") }}', 'POST', body);
  toast(data.message, !data.success);
  if(data.success) { closeModal('modal-port'); refreshRules(); }
}

// ---- TELEGRAM ----
async function saveTelegram() {
  const body = { bot_token: document.getElementById('tg-token').value, chat_id: document.getElementById('tg-chatid').value };
  const data = await api('{{ route("firewall.telegram.save") }}', 'POST', body);
  toast(data.message, !data.success);
  if(data.success) closeModal('modal-telegram');
}
async function testTelegram() {
  const data = await api('{{ route("firewall.telegram.test") }}', 'POST');
  toast(data.message, !data.success);
}

// ---- DELETE RULE ----
async function deleteRule(chain, lineNum) {
  if(!confirm(`Hapus rule #${lineNum} di chain ${chain}?`)) return;
  const data = await api('{{ route("firewall.rules.delete") }}', 'DELETE', { chain, line_number: lineNum });
  toast(data.message, !data.success);
  if(data.success) refreshRules();
}

// ---- FLUSH ----
async function flushChain(chain='all') {
  if(!confirm(`Flush ${chain==='all'?'SEMUA rules':chain+' chain'}? Tindakan ini tidak bisa dibatalkan!`)) return;
  const data = await api('{{ route("firewall.rules.flush") }}', 'POST', { chain });
  toast(data.message, !data.success);
  if(data.success) refreshRules();
}

// ---- REFRESH RULES ----
async function refreshRules() {
  const data = await api('{{ route("firewall.rules") }}');
  if(data.success) renderRulesTable(data.data);
}

function renderRulesTable(allRules) {
  ['INPUT','OUTPUT','FORWARD'].forEach(chain => {
    const tbody = document.getElementById('tbody-'+chain);
    if(!tbody) return;
    const rules = allRules[chain] || [];
    tbody.innerHTML = rules.length === 0
      ? `<tr><td colspan="8" style="text-align:center;color:var(--text2);padding:20px">Tidak ada rule</td></tr>`
      : rules.map(r => {
          const badge = r.target==='ACCEPT'?'b-accept':r.target==='DROP'?'b-drop':'b-reject';
          return `<tr>
            <td class="mono">${r.num}</td>
            <td class="mono">${r.proto}</td>
            <td class="mono">${r.source}</td>
            <td class="mono">${r.dest}</td>
            <td class="mono">${r.extra||'—'}</td>
            <td><span class="badge ${badge}">${r.target}</span></td>
            <td class="mono" style="color:var(--text2)">${r.pkts}/${r.bytes}</td>
            <td><button class="btn btn-red btn-sm" onclick="deleteRule('${chain}',${r.num})">Hapus</button></td>
          </tr>`;
        }).join('');
  });
}

// ---- STATS POLLING ----
async function pollStats() {
  const data = await api('{{ route("firewall.stats") }}');
  if(data.success) {
    const s = data.data;
    const el = id => document.getElementById(id);
    if(el('stat-total'))  el('stat-total').textContent  = s.total_rules;
    if(el('stat-accept')) el('stat-accept').textContent = s.accepted;
    if(el('stat-drop'))   el('stat-drop').textContent   = s.dropped;
    if(el('stat-conn'))   el('stat-conn').textContent   = s.connections;
    if(el('stat-pkts'))   el('stat-pkts').textContent   = s.total_packets.toLocaleString();
  }
}
setInterval(pollStats, 8000);

// ---- LOG POLLING ----
async function pollLogs() {
  const data = await api('{{ route("firewall.logs") }}?lines=30');
  if(!data.success) return;
  ['log-preview','log-full'].forEach(id => {
    const el = document.getElementById(id);
    if(!el) return;
    el.innerHTML = data.data.map(l => {
      const cls = l.action==='ACCEPT'?'log-accept':l.action==='DROP'?'log-drop':'log-reject';
      const info = l.src ? `${l.proto||'?'} ${l.src}:${l.spt||'?'} → ${l.dst||'?'}:${l.dpt||'?'}` : l.raw.substring(0,90);
      return `<div class="log-line"><span class="log-time">${l.time||'—'}</span><span class="${cls}">[${l.action}]</span><span>${info}</span></div>`;
    }).join('');
    el.scrollTop = el.scrollHeight;
  });
}
setInterval(pollLogs, 4000);
pollLogs();
</script>
@yield('scripts')
</body>
</html>
