<div align="center">

# Clips

**AI-Powered Personal Knowledge Manager**

Save what matters. Find it instantly.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-8.2+-purple.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/laravel-13-red.svg)](https://laravel.com)

</div>

---

## Apa itu Clips?

Clips adalah aplikasi manajemen pengetahuan pribadi yang membantu kamu menyimpan, mengorganisir, dan menemukan kembali bookmark, catatan, snippet kode, dan lainnya — semua didukung oleh AI.

Berbeda dari bookmark manager biasa, Clips hadir dengan **AI assistant built-in** yang bisa menganalisis, merangkum, dan mengorganisir konten kamu secara otomatis.

---

## Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| **Smart Clips** | Simpan URL, catatan, snippet kode, file, dan secret. Auto-fetch metadata, favicon, OG image. |
| **AI Assistant** | Tanya apa saja tentang data kamu. Ringkas, analisis, dan temukan pola dengan AI. |
| **AI Chat Widget** | Widget chat AI yang muncul di setiap halaman. Langsung tanya tanpa pindah halaman. |
| **Global Search** | Full-text search di seluruh konten. Temukan clip, catatan, atau snippet dalam hitungan milidetik. |
| **Chrome Extension** | Simpan langsung dari halaman mana saja dengan overlay panel atau side panel. |
| **Quick Notepad** | Catatan instan dengan warna dan auto-save. Seperti Google Keep, tapi terintegrasi. |
| **Collections & Tags** | Organisir dengan tag, koleksi, dan folder. Filter dan sortir sesuai kebutuhan. |
| **Code Snippets** | Simpan snippet kode dengan syntax highlighting. |
| **Worksheets** | Worksheet interaktif untuk data terstruktur. |
| **To-Do List** | Daftar tugas dengan checkbox dan status. |
| **Secret Vault** | Simpan credential dan data sensitif dengan aman. |
| **File Manager** | Upload dan kelola file. |
| **Dead Link Checker** | Otomatis deteksi link yang sudah mati. |
| **Notulensi AI** | Konversi teks mentah rapat menjadi notulensi terstruktur dengan AI. |
| **Invoice System** | Buat, kelola, dan cetak invoice. |
| **Financial Reports** | Laporan keuangan dengan AI analysis. |
| **Backup & Export** | Backup dan export seluruh data kamu. |
| **Activity Log** | Pantau semua aktivitas di akun kamu. |
| **IP Blocker** | Blokir IP yang mencurigakan untuk keamanan. |
| **Multi-user** | Setiap user punya data terpisah. |

---

## Kelebihan

- **AI-Powered**: Semua fitur didukung AI — dari ringkasan, kategori otomatis, hingga chat interaktif.
- **Self-hosted**: Data sepenuhnya di server kamu. Tidak ada data yang dikirim ke pihak ketiga.
- **Privacy-first**: Tidak ada tracking, analytics, atau telemetri. Data hanya milik kamu.
- **Fast**: Dioptimasi dengan query caching, sidebar caching, dan lazy loading.
- **Chrome Extension**: Floating overlay panel yang bisa diakses dari halaman mana saja.
- **Modern UI**: Interface yang clean dan responsive, terinspirasi dari WordPress admin.
- **Dark Mode**: Mendukung light dan dark mode otomatis.
- **Indonesian-first**: Semua prompt AI dan interface dalam Bahasa Indonesia.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13, PHP 8.2+ |
| Frontend | Livewire 3, Tailwind CSS |
| Database | MySQL 8+ / MariaDB |
| Cache | Redis / File cache |
| Extension | Chrome Extension (Manifest V3) |
| AI | OpenAI-compatible API (Gemini, OpenAI, dll) |

---

## Requirements

- PHP 8.2 atau lebih baru
- MySQL 8+ atau MariaDB 10.6+
- Composer
- Node.js & NPM (untuk build asset)
- Redis (opsional, untuk cache lebih cepat)

---

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/marzuki46/bookmark.git clips
cd clips
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuai konfigurasi server kamu:

```env
APP_NAME="Clips"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://clips.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clips
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### 4. Setup Database

```bash
php artisan migrate
```

### 5. Build Assets

```bash
npm run build
```

### 6. Setup Storage Link

```bash
php artisan storage:link
```

### 7. Start Server

```bash
php artisan serve
```

Buka `http://localhost:8000` di browser. Kamu akan diarahkan ke halaman setup untuk membuat akun admin pertama.

---

## Chrome Extension

### Install

1. Buka `chrome://extensions`
2. Aktifkan **Developer mode** (sudut kanan atas)
3. Klik **Load unpacked**
4. Pilih folder `extension/` dari project ini

### Konfigurasi

1. Klik ikon Clips di toolbar Chrome
2. Klik tombol gear (⚙️) untuk buka config
3. Masukkan **API URL** (contoh: `http://localhost:8000/api`)
4. Masukkan **API Token** (generate dari halaman Extension di dashboard)

### Fitur Extension

- **Save Clip**: Simpan halaman saat ini sebagai clip dengan satu klik
- **AI Render**: Generate catatan AI dari halaman saat ini
- **Search**: Cari di knowledge base tanpa meninggalkan halaman
- **Page Info**: Lihat metadata halaman (OG image, deskripsi, dll)
- **Read Later**: Simpan dengan tag "read-later" untuk baca nanti
- **Highlight**: Pilih teks, klik kanan, simpan sebagai highlight

---

## AI Setup

Clips mendukung semua API yang kompatibel dengan OpenAI format:

### Supported Providers

| Provider | API URL | Model |
|----------|---------|-------|
| OpenAI | `https://api.openai.com/v1` | gpt-4o, gpt-4o-mini |
| Google Gemini | `https://generativelanguage.googleapis.com/v1beta/openai` | gemini-2.5-flash |
| Groq | `https://api.groq.com/openai/v1` | llama-3.3-70b |
| DeepSeek | `https://api.deepseek.com/v1` | deepseek-chat |
| OpenRouter | `https://openrouter.ai/api/v1` | multi-model |
| Custom | Sesuaikan | Sesuaikan |

### Cara Setup

1. Login ke Clips
2. Buka **Settings** > **AI Settings**
3. Masukkan API URL, API Key, dan Model
4. Klik **Save**

---

## Project Structure

```
clips/
├── app/
│   ├── Http/Controllers/     # Controllers
│   ├── Livewire/             # Livewire components
│   ├── Models/               # Eloquent models
│   ├── Services/             # Business logic (AI, Search, etc.)
│   └── Jobs/                 # Background jobs
├── database/
│   └── migrations/           # Database migrations
├── extension/                # Chrome Extension files
│   ├── manifest.json
│   ├── background.js
│   ├── overlay.js
│   ├── sidepanel.html
│   └── sidepanel.js
├── resources/
│   └── views/                # Blade templates
│       ├── layouts/          # Main layout
│       ├── livewire/         # Livewire components
│       ├── pages/            # Page views
│       └── auth/             # Auth views
├── routes/
│   └── web.php               # Routes
├── public/                   # Public assets
├── .env                      # Environment config
├── composer.json             # PHP dependencies
└── package.json              # JS dependencies
```

---

## API Endpoints

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/items` | Buat clip baru |
| GET | `/api/items` | List semua clips |
| GET | `/api/items/{id}` | Detail clip |
| PUT | `/api/items/{id}` | Update clip |
| DELETE | `/api/items/{id}` | Hapus clip |
| GET | `/api/search?q=` | Search clips |
| POST | `/api/auth/token` | Generate API token |

---

## Performance Optimizations

- **Sidebar Query Cache**: 4 query COUNT digabung jadi 1 + cache 5 menit
- **Dashboard Stats**: 7 query COUNT digabung jadi 1 groupBy
- **AI Settings Cache**: User settings di-cache 5 menit
- **Metadata Cache**: URL metadata di-cache 24 jam
- **Search Result Cache**: Hasil web search di-cache 1 jam
- **Connection Timeout**: HTTP requests dioptimasi timeout-nya

---

## Security Features

- Rate limiting pada login attempts
- IP blocking untuk brute force protection
- CSRF protection (Laravel default)
- XSS protection via Blade escaping
- SQL injection prevention via Eloquent
- Secure password hashing (bcrypt)
- API token authentication
- `noindex, nofollow` pada landing page
- Tidak menampilkan tech stack di public pages

---

## License

MIT License. Silakan gunakan untuk keperluan pribadi maupun komersial.

---

<div align="center">

**Clips** — Save what matters. Find it instantly.

</div>
