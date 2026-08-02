# PASAR — Pemantauan & Analisis Stasiun Radio

Aplikasi web untuk visualisasi dan analisis data ISR (Izin Stasiun Radio) berbasis MySQL, digunakan oleh Balai Monitor Spektrum Frekuensi Radio Kelas I Yogyakarta.

**URL Produksi:** `https://pasar.balmonjogja.info`

---

## Fitur

| Halaman | Keterangan |
|---|---|
| Dashboard | Statistik total ISR, jumlah klien, sebaran provinsi/kota/kecamatan, breakdown per service & subservice |
| Peta Stasiun | Visualisasi titik stasiun di peta Leaflet dengan filter service, subservice, wilayah, dan pencarian teks |
| Data | Tabel ISR lengkap dengan pagination, filter multi-kolom, dan ekspor |
| Laporan | Import data ISR dari file XLSX, pencatatan upload log |
| Detail | Informasi lengkap per record ISR |

---

## Stack Teknologi

- **Backend:** PHP 8+, PDO MySQL
- **Database:** MySQL (diakses via PDO Prepared Statements)
- **Frontend:** HTML/CSS/JS, Leaflet.js untuk peta
- **Auth:** SSO GUDEG (`gudeg.xo.je`) via OAuth callback
- **Hosting:** Hostinger (FTP deploy)
- **CI/CD:** GitHub Actions

---

## Struktur File

```
pasar/
├── index.php           ← Entry point (redirect ke login atau dashboard)
├── login.php           ← Halaman login (redirect ke SSO)
├── callback.php        ← SSO OAuth callback
├── logout.php          ← Hapus sesi & redirect
├── dashboard.php       ← Statistik utama
├── peta.php            ← Peta interaktif stasiun
├── data.php            ← Tabel data ISR
├── laporan.php         ← Import XLSX & upload log
├── detail.php          ← Detail satu record ISR
├── config.php          ← Loader konfigurasi (credential di config.local.php)
├── includes/
│   ├── auth.php        ← Helper sesi & SSO
│   ├── db.php          ← Koneksi PDO
│   ├── header.php      ← Template header HTML
│   └── footer.php      ← Template footer HTML
├── api/
│   └── geojson.php     ← Endpoint GeoJSON untuk peta (limit 5000 titik)
├── assets/
│   ├── css/app.css
│   └── js/app.js
└── .github/workflows/
    └── deploy.yml      ← CI/CD GitHub Actions → FTP Hostinger
```

---

## Setup Lokal

### 1. Clone & siapkan database

```bash
git clone https://github.com/aprianto199304012025041002-wq/pasar.git
cd pasar
```

Buat database MySQL dan import skema (file `schema.sql` tidak ada di repo, buat dari struktur tabel `isr` dan `upload_log`).

### 2. Buat `config.local.php`

File ini **tidak ada di repo** dan harus dibuat manual di server:

```php
<?php
define('DB_HOST',                 'localhost');
define('DB_NAME',                 'nama_database');
define('DB_USER',                 'username_db');
define('DB_PASS',                 'password_db');
define('DB_CHARSET',              'utf8mb4');
define('SSO_CLIENT_ID',           'client_id_dari_gudeg');
define('SSO_CLIENT_SECRET',       'hmac_secret');
define('SSO_CLIENT_SECRET_PLAIN', 'secret_plain');
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

### 3. Jalankan di web server lokal

Gunakan XAMPP, Laragon, atau PHP built-in server:

```bash
php -S localhost:8000
```

---

## Deploy ke Hostinger

Deploy otomatis terjadi setiap push ke branch `main` via GitHub Actions.

### Secrets yang dibutuhkan di repository

| Secret | Keterangan |
|---|---|
| `DB_HOST` | Host database Hostinger |
| `DB_NAME` | Nama database |
| `DB_USER` | Username database |
| `DB_PASS` | Password database |
| `SSO_CLIENT_ID` | Client ID terdaftar di GUDEG |
| `SSO_CLIENT_SECRET` | HMAC secret (base64) |
| `SSO_CLIENT_SECRET_PLAIN` | Secret plaintext |
| `SSO_PUBLIC_KEY` | RSA public key GUDEG (base64) |
| `FTP_PASS` | Password FTP Hostinger |

File `config.local.php` dibuat otomatis oleh workflow saat deploy — **tidak perlu upload manual**.

---

## Catatan Keamanan

- `config.local.php` tidak pernah masuk repo (ada di `.gitignore`)
- Semua query menggunakan PDO Prepared Statements
- Endpoint API memverifikasi sesi SSO sebelum mengembalikan data
- Endpoint peta membatasi output ke 5.000 titik per request
