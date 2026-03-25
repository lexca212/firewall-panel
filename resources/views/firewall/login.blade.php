<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FirePanel Login</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    :root{--bg:#080c10;--panel:#0a1520;--border:#1a2f45;--accent:#00d4ff;--accent2:#00ff88;--err:#ff4444;--text:#c8d8e8;--muted:#6a8aa0}
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);color:var(--text);font-family:Arial,sans-serif}
    .card{width:min(420px,94vw);background:var(--panel);border:1px solid var(--border);padding:22px;border-radius:8px}
    h1{margin:0 0 6px;font-size:22px;color:var(--accent)}
    p{margin:0 0 18px;color:var(--muted);font-size:13px}
    label{display:block;font-size:12px;margin-bottom:6px;color:var(--muted)}
    input{width:100%;padding:10px 12px;border-radius:6px;border:1px solid var(--border);background:#111820;color:var(--text);margin-bottom:14px}
    button{width:100%;padding:10px;border:1px solid var(--accent2);background:rgba(0,255,136,.08);color:var(--accent2);border-radius:6px;cursor:pointer;font-weight:700}
    .msg{margin-bottom:12px;padding:8px 10px;border-radius:6px;font-size:13px}
    .msg.err{border:1px solid rgba(255,68,68,.35);color:var(--err);background:rgba(255,68,68,.08)}
    .msg.ok{border:1px solid rgba(0,255,136,.35);color:var(--accent2);background:rgba(0,255,136,.08)}
  </style>
</head>
<body>
  <form class="card" method="POST" action="{{ route('firewall.login') }}">
    @csrf
    <h1>🔥 FirePanel Login</h1>
    <p>Masuk untuk mengakses panel firewall.</p>

    @if(session('status'))
      <div class="msg ok">{{ session('status') }}</div>
    @endif
    @if($errors->has('login'))
      <div class="msg err">{{ $errors->first('login') }}</div>
    @endif

    <label>Username</label>
    <input type="text" name="username" value="{{ old('username') }}" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
  </form>
</body>
</html>
