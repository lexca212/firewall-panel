# 🔥 FirePanel — Laravel 12 Firewall Management Panel

Panel manajemen firewall Linux berbasis Laravel 12 dengan eksekusi iptables via sudo (www-data).

---

## ✅ Fitur

| Fitur | Keterangan |
|---|---|
| CRUD Rules | Tambah/hapus rule iptables (INPUT/OUTPUT/FORWARD) |
| Toggle Firewall | Aktifkan/nonaktifkan firewall sepenuhnya |
| Set Policy | Ubah default policy chain (ACCEPT/DROP) |
| Flush Rules | Hapus semua rule atau per chain |
| Ganti Port | Ubah port layanan (misal SSH 22→2222) |
| Save/Restore | Simpan ke `/etc/iptables/rules.v4` & pulihkan |
| Export Rules | Download file backup rules |
| Live Logs | Streaming log kernel via journalctl |
| Telegram Notif | Kirim notif ke Telegram saat ada perubahan |
| Stats Polling | Auto-refresh statistik setiap 8 detik |

---

## 📋 Persyaratan

- PHP 8.2+
- Laravel 12
- `iptables` / `iptables-save` / `iptables-restore`
- `sudo` dikonfigurasi untuk www-data
- `journalctl` (untuk live log)

---

## 🚀 Instalasi

### 1. Clone & Setup Laravel

```bash
composer create-project laravel/laravel firewall-panel
cd firewall-panel
```

### 2. Copy File Proyek

Salin semua file dari paket ini ke direktori Laravel Anda:

```
app/Services/FirewallService.php
app/Http/Controllers/FirewallController.php
config/firewall.php
routes/web.php          ← ganti seluruh isi
resources/views/layouts/firewall.blade.php
resources/views/firewall/index.blade.php
```

### 3. Register Service Provider (opsional, atau gunakan DI otomatis)

Laravel 12 mendukung auto-discovery, tidak perlu register manual.

### 4. Tambahkan Logging Channel

Buka `config/logging.php`, tambahkan ke dalam array `channels`:

```php
'firewall' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/firewall.log'),
    'level'  => 'debug',
    'days'   => 14,
],
```

### 5. Konfigurasi .env

```dotenv
APP_NAME="FirePanel"
APP_URL=http://your-server-ip

# Telegram (isi jika ingin notifikasi)
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

### 6. Konfigurasi Sudoers

```bash
# Copy file sudoers
sudo cp sudoers.d/firewall-panel /etc/sudoers.d/firewall-panel

# Set permission WAJIB 0440
sudo chmod 0440 /etc/sudoers.d/firewall-panel

# Verifikasi syntax
sudo visudo -c
```

### 7. Buat Direktori iptables

```bash
sudo mkdir -p /etc/iptables
sudo chown root:root /etc/iptables
sudo chmod 755 /etc/iptables
```

### 8. Simpan Rules Awal (jika belum ada)

```bash
sudo iptables-save > /etc/iptables/rules.v4
```

### 9. Set Permission Storage

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 10. Jalankan Migrasi & Cache

```bash
php artisan key:generate
php artisan config:cache
php artisan route:cache
```

### 11. Konfigurasi Web Server (Nginx)

```nginx
server {
    listen 80;
    server_name your-server-ip;
    root /var/www/firewall-panel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 🔒 Keamanan

Panel ini **tidak memiliki autentikasi** (internal only). Lindungi akses dengan:

```bash
# Izinkan akses panel hanya dari IP admin (misal 192.168.1.10)
sudo iptables -I INPUT -p tcp --dport 80 -s 192.168.1.10 -j ACCEPT
sudo iptables -I INPUT -p tcp --dport 80 ! -s 192.168.1.10 -j DROP
```

Atau gunakan `.htpasswd` / VPN sebagai lapisan tambahan.

---

## 📡 API Endpoints

| Method | URL | Fungsi |
|---|---|---|
| GET | `/firewall` | Dashboard |
| GET | `/firewall/status` | Status firewall |
| POST | `/firewall/enable` | Aktifkan firewall |
| POST | `/firewall/disable` | Nonaktifkan firewall |
| GET | `/firewall/rules` | List semua rules (JSON) |
| POST | `/firewall/rules` | Tambah rule baru |
| DELETE | `/firewall/rules` | Hapus rule |
| POST | `/firewall/rules/flush` | Flush chain |
| POST | `/firewall/rules/policy` | Set policy |
| POST | `/firewall/change-port` | Ganti port |
| POST | `/firewall/save` | Simpan rules |
| POST | `/firewall/restore` | Restore rules |
| GET | `/firewall/export` | Download rules |
| GET | `/firewall/logs` | Live logs (JSON) |
| GET | `/firewall/logs/stream` | SSE stream |
| GET | `/firewall/stats` | Statistik (JSON) |
| POST | `/firewall/telegram/save` | Simpan config Telegram |
| POST | `/firewall/telegram/test` | Test notifikasi |

---

## 🔔 Setup Telegram Bot

1. Chat [@BotFather](https://t.me/BotFather) → `/newbot` → salin token
2. Tambahkan bot ke grup/channel
3. Kirim pesan ke grup
4. Dapatkan Chat ID dari [@userinfobot](https://t.me/userinfobot)
5. Buka panel → klik **Telegram Setup** → isi token & chat ID → Test Kirim

---

## 🐛 Troubleshooting

**sudo: iptables: command not found**
```bash
which iptables   # cek lokasi, sesuaikan di sudoers
# Biasanya /sbin/iptables atau /usr/sbin/iptables
```

**Permission denied saat exec**
```bash
sudo visudo -c   # verifikasi sudoers tidak ada error
sudo -u www-data sudo iptables -L   # test manual
```

**Live log kosong**
```bash
# Pastikan logging iptables diaktifkan
sudo iptables -A INPUT -j LOG --log-prefix "IPTABLES: " --log-level 4
```
