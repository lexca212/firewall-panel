@extends('layouts.firewall')

@section('title', 'Backup Manager')

@section('content')
<div class="panel">
  <div class="panel-head">
    <span class="panel-title">🗄 Backup Manager</span>
  </div>
  <div class="two-col" style="padding:18px">
    <div class="panel" style="margin:0">
      <div class="panel-head"><span class="panel-title">MySQL Backup</span></div>
      <div class="form-row" style="border:none">
        <div class="fg"><label class="flabel">Host</label><input class="finput" id="db-host" value="127.0.0.1"></div>
        <div class="fg"><label class="flabel">Port</label><input class="finput" id="db-port" value="3306"></div>
        <div class="fg"><label class="flabel">Username</label><input class="finput" id="db-user"></div>
        <div class="fg"><label class="flabel">Password</label><input class="finput" id="db-pass" type="password"></div>
        <div class="fg"><label class="flabel">Database</label><input class="finput" id="db-name"></div>
        <div class="fg"><label class="flabel">Destination</label><input class="finput" id="db-dest" value="/var/backups/firepanel"></div>
        <button class="btn btn-green" type="button" onclick="runMysqlBackup()">Backup MySQL</button>
      </div>
    </div>

    <div class="panel" style="margin:0">
      <div class="panel-head"><span class="panel-title">ZIP Folder</span></div>
      <div class="form-row" style="border:none">
        <div class="fg"><label class="flabel">Source Folder</label><input class="finput" id="zip-source" value="/var/www"></div>
        <div class="fg"><label class="flabel">Destination</label><input class="finput" id="zip-dest" value="/var/backups/firepanel"></div>
        <button class="btn btn-yellow" type="button" onclick="runZipBackup()">Backup ZIP</button>
      </div>
    </div>

    <div class="panel" style="margin:0">
      <div class="panel-head"><span class="panel-title">Rsync ke Server Lain</span></div>
      <div class="form-row" style="border:none">
        <div class="fg"><label class="flabel">Source Folder</label><input class="finput" id="rsync-source" value="/var/www"></div>
        <div class="fg"><label class="flabel">Remote Host</label><input class="finput" id="rsync-host" placeholder="10.10.10.10"></div>
        <div class="fg"><label class="flabel">Remote User</label><input class="finput" id="rsync-user" placeholder="backup"></div>
        <div class="fg"><label class="flabel">Remote Path</label><input class="finput" id="rsync-path" placeholder="/backup/firepanel"></div>
        <div class="fg"><label class="flabel">Port</label><input class="finput" id="rsync-port" value="22"></div>
        <div class="fg"><label class="flabel">SSH Key (opsional)</label><input class="finput" id="rsync-key" placeholder="/root/.ssh/id_rsa"></div>
        <button class="btn btn-purple" type="button" onclick="runRsyncBackup()">Backup Rsync</button>
      </div>
    </div>

    <div class="panel" style="margin:0">
      <div class="panel-head"><span class="panel-title">Crontab Backup</span></div>
      <div class="form-row" style="border:none">
        <div class="fg"><label class="flabel">Cron Expr</label><input class="finput" id="cron-expr" value="0 2 * * *"></div>
        <div class="fg"><label class="flabel">Type</label>
          <select class="fselect" id="cron-type"><option value="mysql">mysql</option><option value="zip">zip</option><option value="rsync">rsync</option></select>
        </div>
        <button class="btn btn-blue" type="button" onclick="saveCrontab()">Simpan Crontab</button>
        <button class="btn btn-blue" type="button" onclick="loadCrontab()">Lihat Crontab</button>
      </div>
      <div style="padding:12px 18px">
        <pre id="cron-view" class="mono" style="white-space:pre-wrap;color:var(--text2)">Belum ada data.</pre>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
const backupEndpoints = {
  mysql: '{{ url('/firewall/backup/mysql') }}',
  zip: '{{ url('/firewall/backup/zip') }}',
  rsync: '{{ url('/firewall/backup/rsync') }}',
  cronSave: '{{ url('/firewall/backup/crontab') }}',
  cronShow: '{{ url('/firewall/backup/crontab') }}',
};

async function runMysqlBackup() {
  const body = {
    host: document.getElementById('db-host').value,
    port: Number(document.getElementById('db-port').value || 3306),
    username: document.getElementById('db-user').value,
    password: document.getElementById('db-pass').value,
    database: document.getElementById('db-name').value,
    destination: document.getElementById('db-dest').value,
  };
  const res = await api(backupEndpoints.mysql, 'POST', body);
  toast(res.message || 'Selesai', !res.success);
}

async function runZipBackup() {
  const body = {
    source: document.getElementById('zip-source').value,
    destination: document.getElementById('zip-dest').value,
  };
  const res = await api(backupEndpoints.zip, 'POST', body);
  toast(res.message || 'Selesai', !res.success);
}

async function runRsyncBackup() {
  const body = {
    source: document.getElementById('rsync-source').value,
    remote_host: document.getElementById('rsync-host').value,
    remote_user: document.getElementById('rsync-user').value,
    remote_path: document.getElementById('rsync-path').value,
    port: Number(document.getElementById('rsync-port').value || 22),
    ssh_key: document.getElementById('rsync-key').value,
  };
  const res = await api(backupEndpoints.rsync, 'POST', body);
  toast(res.message || 'Selesai', !res.success);
}

async function saveCrontab() {
  const type = document.getElementById('cron-type').value;
  let payload = {};
  if (type === 'mysql') {
    payload = {
      host: document.getElementById('db-host').value,
      port: Number(document.getElementById('db-port').value || 3306),
      username: document.getElementById('db-user').value,
      password: document.getElementById('db-pass').value,
      database: document.getElementById('db-name').value,
      destination: document.getElementById('db-dest').value,
    };
  } else if (type === 'zip') {
    payload = {
      source: document.getElementById('zip-source').value,
      destination: document.getElementById('zip-dest').value,
    };
  } else {
    payload = {
      source: document.getElementById('rsync-source').value,
      remote_host: document.getElementById('rsync-host').value,
      remote_user: document.getElementById('rsync-user').value,
      remote_path: document.getElementById('rsync-path').value,
      port: Number(document.getElementById('rsync-port').value || 22),
      ssh_key: document.getElementById('rsync-key').value,
    };
  }

  const res = await api(backupEndpoints.cronSave, 'POST', {
    cron: document.getElementById('cron-expr').value,
    type,
    payload
  });
  toast(res.message || 'Selesai', !res.success);
  if (res.success) loadCrontab();
}

async function loadCrontab() {
  const res = await api(backupEndpoints.cronShow);
  document.getElementById('cron-view').textContent = res.content || res.message || 'Belum ada data.';
}

loadCrontab();
</script>
@endsection
