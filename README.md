# 📡 PASAR — Pemantauan & Analisis Stasiun Radio

**PASAR** adalah aplikasi web untuk visualisasi dan analisis data ISR (Izin Stasiun Radio) Balai Monitor Spektrum Frekuensi Radio Kelas I Yogyakarta. Data bersumber dari SIMS (Sistem Informasi Manajemen Spektrum) dan mencakup seluruh wilayah kerja Balmon Kelas I Yogyakarta.

- **URL Produksi:** `https://pasar.balmonjogja.info`
- **Hosting:** Hostinger
- **Stack:** PHP 8+, PDO MySQL, Leaflet.js
- **Auth:** SSO GUDEG (`gudeg.xo.je`)
- **Deploy:** GitHub Actions → FTP → Hostinger (push ke `main` = auto deploy)
- **Repo:** `aprianto199304012025041002-wq/pasar`

---

## ✨ Fitur

| Halaman | Keterangan |
|---|---|
| **Dashboard** | Statistik total ISR, jumlah klien, jumlah provinsi/kab-kota/kecamatan, breakdown per service & subservice, sebaran per klien — semua expandable dengan bar chart |
| **Data** | Tabel ISR lengkap: pagination, filter multi-kolom (service, subservice, wilayah layanan, provinsi, kab/kota, kecamatan, pencarian teks), sort by jarak dari lokasi GPS/koordinat manual |
| **Peta** | Marker stasiun di Leaflet dengan cluster, filter identik dengan halaman Data, mode radius dari lokasi GPS, toggle cluster on/off, legenda per service |
| **Detail** | Info lengkap satu record ISR: identitas klien, lisensi & masa berlaku, teknis frekuensi, perangkat & lokasi, mini-map Leaflet |
| **Laporan** | Import data ISR dari file XLSX atau CSV (replace seluruh tabel, dalam satu transaksi), import SQL mentah (khusus admin), riwayat upload dengan pagination |

---

## 🏗️ Struktur File

```
pasar/
├── .github/workflows/
│   └── deploy.yml          # Push ke main → generate config.local.php → FTP ke Hostinger
├── .htaccess               # Blokir akses ke config.local.php, Options -Indexes, security headers
├── .gitignore              # Exclude: config.local.php, ftp-deploy-sync-state.json, data/, .vs/
├── api/
│   └── geojson.php         # Endpoint GeoJSON untuk peta (limit 15.000 titik, dengan flag truncated)
├── assets/
│   ├── css/app.css         # Stylesheet utama (dark/bright theme, CSS custom properties)
│   └── js/app.js           # JS utama (filter drawer, auto-submit, theme toggle)
├── includes/
│   ├── auth.php            # Helper sesi SSO: _session_start(), require_login(), verify_token(), get_user()
│   ├── db.php              # Koneksi PDO MySQL (menggunakan konstanta dari config.local.php)
│   ├── header.php          # Template header HTML + sidebar nav
│   └── footer.php          # Template footer HTML
├── callback.php            # SSO OAuth callback — verifikasi token + state, isi session
├── config.php              # Loader konfigurasi → require config.local.php (tidak di-commit)
├── dashboard.php           # Halaman statistik utama
├── data.php                # Tabel ISR dengan filter + sort jarak Haversine
├── default.php             # Landing page / redirect
├── detail.php              # Detail satu record ISR + mini-map
├── index.php               # Entry point (redirect ke login)
├── laporan.php             # Upload & import data ISR (XLSX/CSV/SQL) + riwayat upload
├── login.php               # Redirect ke SSO GUDEG dengan state CSRF
├── logout.php              # Hapus session + redirect ke login
└── peta.php                # Peta interaktif stasiun dengan filter + GPS radius
```

---

## ⚙️ Setup

### 1. Clone

```bash
git clone https://github.com/aprianto199304012025041002-wq/pasar.git
cd pasar
```

### 2. Buat Database MySQL

Buat tabel `isr` dan `upload_log`. Tabel `upload_log` dibuat otomatis oleh `laporan.php` jika belum ada. Untuk tabel `isr`, import data dari SIMS via halaman Laporan setelah aplikasi berjalan.

Kolom penting tabel `isr`: `id`, `clnt_id`, `clnt_name`, `service`, `subservice`, `curr_lic_num`, `stn_name`, `stn_addr`, `freq`, `freq_pair`, `validity_date`, `sid_lat`, `sid_long`, `province`, `city`, `district`, `area_of_service`, `TGL_QUERY`, dan lainnya sesuai ekspor SIMS.

### 3. Buat `config.local.php`

File ini **tidak ada di repo** dan dibuat otomatis oleh GitHub Actions saat deploy. Untuk lokal:

```php
<?php
define('DB_HOST',                 'localhost');
define('DB_NAME',                 'nama_database');
define('DB_USER',                 'username_db');
define('DB_PASS',                 'password_db');
define('DB_CHARSET',              'utf8mb4');
define('SSO_CLIENT_ID',           'client_id_dari_gudeg');
define('SSO_CLIENT_SECRET',       'bcrypt_hash_secret');
define('SSO_CLIENT_SECRET_PLAIN', 'secret_plaintext');
define('SSO_URL',                 'https://gudeg.xo.je/sso.php');
define('SSO_VERIFY_URL',          'https://gudeg.xo.je/verify.php');
define('SSO_CALLBACK_URL',        'https://pasar.balmonjogja.info/callback.php');
define('APP_NAME',                'pasar');
define('APP_SUBTITLE',            'Balmon Kelas I Yogyakarta');
define('APP_BASE_URL',            'https://pasar.balmonjogja.info');
define('PER_PAGE',                25);
define('SSO_PUBLIC_KEY',          <<<'PEM'
-----BEGIN PUBLIC KEY-----
... (RSA public key dari GUDEG) ...
-----END PUBLIC KEY-----
PEM);
```

---

## 🔄 Alur Auth SSO

```
User → login.php (generate state CSRF → redirect ke gudeg.xo.je/sso.php)
     → GUDEG login → generate JWT RS256
     → Redirect ke callback.php?token=<jwt>&state=<state>
     → callback.php: verifikasi state, verify_token() (RS256 atau HMAC)
     → Isi $_SESSION[user_id, name, email, role, ip]
     → Redirect ke dashboard.php
```

`verify_token()` di `includes/auth.php` mendukung dua mode:
- **RS256** — verifikasi dengan `SSO_PUBLIC_KEY`
- **HMAC** — verifikasi dengan `SSO_CLIENT_SECRET_PLAIN` (untuk kompatibilitas mundur)

---

## 🗺️ Peta & GeoJSON

`api/geojson.php` mengembalikan FeatureCollection dengan:
- Semua filter yang sama dengan halaman Data (service, wilayah, pencarian teks)
- Hard limit **15.000 titik** per request — response menyertakan `total`, `shown`, dan flag `truncated`
- Koordinat divalidasi: `sid_lat BETWEEN -11 AND 6`, `sid_long BETWEEN 95 AND 141`

`peta.php` menggunakan Leaflet.markercluster untuk performa dengan ribuan marker, mode radius Haversine untuk filter berdasarkan jarak GPS, dan toggle cluster on/off.

---

## 📤 Import Data (Laporan)

`laporan.php` mendukung:
- **XLSX / CSV** — Replace seluruh tabel `isr` dalam satu transaksi (`DELETE` + batch `INSERT`). Kalau insert gagal di tengah, data lama dikembalikan (rollback).
- **SQL** — Eksekusi langsung (khusus role `admin`)
- Header kolom dicocokkan dengan daftar `KNOWN_COLUMNS` dari SIMS — minimal 3 kolom cocok
- Semua upload dicatat di `upload_log` (user, file, ukuran, baris, status, keterangan)

---

## 🚀 Deploy

Push ke `main` → GitHub Actions:
1. Generate `config.local.php` dari GitHub Secrets
2. Deploy seluruh file via FTP ke Hostinger (`/public_html/`)
3. `.htaccess` di-exclude dari FTP — tidak di-overwrite saat deploy

### GitHub Secrets yang Dibutuhkan

| Secret | Keterangan |
|---|---|
| `DB_HOST` | Host database Hostinger |
| `DB_NAME` | Nama database |
| `DB_USER` | Username database |
| `DB_PASS` | Password database |
| `SSO_CLIENT_ID` | Client ID terdaftar di GUDEG |
| `SSO_CLIENT_SECRET` | bcrypt hash secret |
| `SSO_CLIENT_SECRET_PLAIN` | Secret plaintext |
| `SSO_PUBLIC_KEY` | RSA public key GUDEG (base64) |
| `FTP_HOST` | Hostname FTP Hostinger |
| `FTP_USER` | Username FTP |
| `FTP_PASS` | Password FTP |

---

## 🛡️ Keamanan

- `config.local.php` tidak pernah masuk repo (`.gitignore`) dan diblokir akses HTTP via `.htaccess`
- `.htaccess` tidak di-overwrite saat deploy (di-exclude dari FTP workflow)
- `Options -Indexes` — directory listing dimatikan
- Semua query menggunakan PDO Prepared Statements
- Validasi state CSRF di `callback.php` (one-time, expired setelah 10 menit)
- Session regenerasi ID setelah login (`session_regenerate_id(true)`)
- API `/api/geojson.php` memverifikasi sesi SSO sebelum mengembalikan data

---

## 📄 Lisensi

Proyek privat — hak cipta milik Balai Monitor Spektrum Frekuensi Radio Kelas I Yogyakarta.
