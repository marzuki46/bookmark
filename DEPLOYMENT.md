# 📦 Panduan Deploy ke Hosting Indonesia (cPanel)

Panduan lengkap deploy **Knowledge Hub** (Laravel) ke hosting Indonesia seperti Niagahoster, IdCloudHost, JagoanHosting, dll.

---

## 📋 Persyaratan Hosting

Pastikan hosting Anda mendukung:
- ✅ **PHP 8.2+** (cek di cPanel → Select PHP Version)
- ✅ **MySQL 5.7+ / MariaDB 10.3+**
- ✅ **mod_rewrite** (wajib untuk pretty URL — cek di cPanel → Select PHP Version → Extensions, pastikan `mod_rewrite` aktif)
- ✅ **Composer** (bisa dijalankan via SSH atau terminal cPanel)
- ✅ **Cron Job** (untuk scheduler & queue)
- ✅ **SSL Gratis** (AutoSSL dari cPanel)

> 💡 **Rekomendasi hosting murah:** Niagahoster (mulai Rp 25rb/bln), JagoanHosting (Rp 20rb/bln), IdCloudHost

---

## 🚀 Langkah 1: Upload File ke Hosting

### Opsi A: Via Git (Rekomendasi)
```bash
# SSH ke hosting
ssh user@domain.com
cd public_html

# Clone repo
git clone https://github.com/marzuki46/bookmark.git .
git checkout master
```

### Opsi B: Via cPanel File Manager
1. Login ke cPanel
2. Buka **File Manager** → `public_html`
3. Upload file ZIP project (Download dari GitHub: Code → Download ZIP)
4. Extract ZIP di `public_html`
5. Pindahkan semua file ke folder `public_html`

### Opsi C: Via FTP (FileZilla)
1. Download project dari GitHub sebagai ZIP
2. Extract di komputer
3. Upload semua file ke folder `public_html` via FTP

> ⚠️ **PENTING:** Setelah upload, pastikan folder `public/` menjadi **document root** (lihat Langkah 1b)

### Langkah 1b: Set Document Root ke `/public`

Buat file `.htaccess` di root `public_html` dengan isi:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

Atau di cPanel:
1. Buka **Domains** → pilih domain Anda
2. Set **Document Root** → `/public_html/public`
3. Simpan

---

## 📁 Langkah 2: Setup Environment (.env)

### Buat file `.env`
```bash
# Copy dari template
cp .env.example .env

# Generate APP_KEY
php artisan key:generate
```

Atau jika tidak ada SSH, buat manual isi file `.env`:

```ini
APP_NAME="Knowledge Hub"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com
APP_KEY=base64:xxxxx   # GENERATE DULU!

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database    # ← GANTI!
DB_USERNAME=user_database    # ← GANTI!
DB_PASSWORD=password_db      # ← GANTI!

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### Generate APP_KEY (WAJIB)
```bash
# Via SSH
php artisan key:generate
```

Jika tidak ada SSH, generate APP_KEY di:
1. https://generate-random.org/laravel-key-generator
2. Copy hasilnya ke `.env` → `APP_KEY=base64:xxxxxxxxx`

---

## 🗄️ Langkah 3: Setup Database MySQL

### 1. Buat Database di cPanel
1. Buka **MySQL Databases** di cPanel
2. Buat database baru (contoh: `bookmark_db`)
3. Buat user database (contoh: `bookmark_user`)
4. Tambahkan user ke database dengan **ALL PRIVILEGES**

### 2. Update `.env`
```
DB_DATABASE=bookmark_db
DB_USERNAME=bookmark_user
DB_PASSWORD=password_user_tadi
```

### 3. Jalankan Migration
```bash
php artisan migrate
```

> ⚠️ Jika error `Access denied`, cek username & password di cPanel

---

## 🔄 Langkah 4: Storage Link & Permission

### 1. Set Permission Folder
```bash
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/uploads  # jika ada
```

### 2. Buat Storage Link
```bash
php artisan storage:link
```

### 3. Cache Konfigurasi
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ⏰ Langkah 5: Setup Cron Job (WAJIB untuk webhook & queue)

Di cPanel:
1. Buka **Cron Jobs**
2. Pilih **Once Per Minute** (`* * * * *`)
3. Isi Command:
```bash
/usr/local/bin/php /home/username/public_html/artisan schedule:run >> /dev/null 2>&1
```
4. Tambahkan cron job kedua untuk queue worker:
```bash
/usr/local/bin/php /home/username/public_html/artisan queue:work --stop-when-empty --tries=3 --delay=5 >> /dev/null 2>&1
```

> 💡 Flag `--stop-when-empty` memastikan queue worker memproses antrian lalu berhenti (lebih aman untuk shared hosting). Jangan gunakan `--daemon` karena sudah tidak didukung di Laravel 8+.

> 💡 Ganti `username` dengan username cPanel Anda

---

## 🔐 Langkah 6: Setup SSL

1. Buka **SSL/TLS Status** di cPanel
2. Klik **Run AutoSSL**
3. Tunggu hingga SSL terinstall
4. Pastikan URL sudah `https://domain-anda.com`

---

## 📱 Langkah 7: Setup Webhook Fonnte

Setelah domain aktif:
1. Buka aplikasi → **Laporan Keuangan** → klik **WA Gateway**
2. Masukkan:
   - **API Key Fonnte** (dari dashboard Fonnte)
   - **Nomor WhatsApp** Anda
3. Klik **Simpan Pengaturan**
4. Copy **Webhook URL** yang muncul
5. Buka dashboard Fonnte → Settings → **Webhook**
6. Paste URL: `https://domain-anda.com/api/webhook/wa-finance`
7. Klik Save

---

## ✅ Langkah 8: Verifikasi

Cek apakah semua berfungsi:
1. ✅ Buka `https://domain-anda.com` → harus muncul landing page
2. ✅ Buka `https://domain-anda.com/setup` → halaman setup
3. ✅ Buka `https://domain-anda.com/login` → halaman login
4. ✅ Buka `https://domain-anda.com/dashboard` → dashboard (setelah login)
5. ✅ Buka `https://domain-anda.com/financial` → laporan keuangan
6. ✅ Setup webhook Fonnte

### Cek error jika ada masalah:
```bash
# Lihat log Laravel
cat storage/logs/laravel.log

# Cek permission
ls -la storage/ bootstrap/cache/
```

---

## 🔧 Troubleshooting

### ❌ Error 500 / Blank Page
```bash
# 1. Cek error log
tail -f storage/logs/laravel.log

# 2. Set permission
chmod -R 775 storage bootstrap/cache

# 3. Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### ❌ Error "No application encryption key"
Jalankan:
```bash
php artisan key:generate
```

### ❌ Error 403 Forbidden
Cek file `.htaccess` di folder `public/`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### ❌ Database connection error
```bash
# Cek file .env sudah benar
cat .env | grep DB_

# Test koneksi
php artisan tinker
> DB::connection()->getPdo();
```

### ❌ Webhook tidak masuk
1. Cek di **Laporan Keuangan → WA Gateway** apakah webhook URL sudah benar
2. Cek di dashboard Fonnte apakah webhook URL sudah diset
3. Cek log: `storage/logs/laravel.log`
4. Coba kirim WA dan lihat apakah ada log: `tail -f storage/logs/laravel.log`

---

## 🆘 Butuh Bantuan?

Jika ada kendala, pastikan:
1. PHP version ≥ 8.2
2. Ekstensi PHP: `BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL, GD`
3. Folder `storage/` dan `bootstrap/cache/` writable
4. Composer sudah dijalankan (`composer install --no-dev`)
5. Migration sudah jalan (`php artisan migrate`)
