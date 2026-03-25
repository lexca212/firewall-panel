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

<script>
function switchTab(chain, btn) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-'+chain).classList.add('active');
  btn.classList.add('active');
}
</script>

@endsection
