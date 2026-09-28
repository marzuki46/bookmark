# Spec: Aplikasi Keuangan Keluarga (Android) + AI Insight Otomatis

Status: draft untuk implementasi
Tanggal: 2026-09-28

## 1. Outcome

Sebuah aplikasi Android untuk mengelola keuangan rumah tangga, dengan:

- Data keluarga menyatu dengan modul keluarga yang sudah ada (bukan sistem terpisah).
- AI insight otomatis (tanpa tombol) yang sampai ke HP sebagai notifikasi.
- Notifikasi berbeda untuk tiap anggota (suami/istri) plus satu notifikasi kesehatan
  keuangan keluarga.
- Modul hutang, anggaran, target, dan sumber pemasukan (gaji/usaha) yang berguna
  untuk rumah tangga sehari-hari.
- Login memakai kode, bukan email/password.

## 2. Actors

| Actor | Keterangan |
|---|---|
| Anggota keluarga | Suami atau istri. Satu akun = satu orang. |
| Kepala keluarga | Anggota dengan `role = owner`. |
| Admin perumahan | Mengelola daftar keluarga dipermalinkahan. **Tidak** melihat detail keuangan keluarga. |
| Server | Menyimpan data, menjalankan AI, mengirim notifikasi. |

## 3. Tenancy (3 lapis)

```
Perumahan (housing_complex)
  └── Keluarga (family)
        └── Anggota (family_members) -> user
              └── family_transactions (payer = husband | wife | shared)
```

**Asumsi yang harus dikonfirmasi:**

- Satu user hanya boleh menjadi anggota **satu** keluarga untuk keperluan app.
  Kemampuan `invite_code` di web tetap dibiarkan, tetapi app tidak pernah
  menawarkan alur "gabung keluarga lain".
- Admin perumahan hanya melihat data agregat/anonim (jumlah keluarga, jumlah
  anggota). Tidak ada akses ke nominal, transaksi, atau hutang keluarga.

## 4. Scope

### Termasuk

1. Tabel `housing_complexes` + `families.housing_complex_id`.
2. Login by kode permanen yang tidak bisa ditebak.
3. Device registration untuk notifikasi.
4. API keluarga: transaksi, hutang, anggaran, target, anggota.
5. Sumber pemasukan (gaji/usaha/dll) pada transaksi pemasukan.
6. AI insight otomatis mingguan + notifikasi per anggota dan kesehatan keluarga.
7. Nudge instan berbasis rule (gratis, langsung setelah input).
8. Redesign UI Android dengan palet warna yang lebih baik.
9. Notifikasi FCM dengan fallback polling.

### Tidak Termasuk (fase ini)

- Modul usaha lengkap (modal, omzet harian, pajak).
- Upload ke Play Store dan signing release.
- Pairing lewat SMS/WhatsApp/email.
- Multi-mata uang (semua IDR).
- Sistem poin gamification.

## 5. Functional Requirements + Acceptance Criteria

### FR1 — Holmes
Creating a perumahan assigns a family to it.

- AC1.1 `housing_complexes`: `name`, `address`, `code` (unique), `admin_user_id`,
  `is_active`, timestamps.
- AC1.2 `families` dapat memiliki `housing_complex_id` nullable (backward safe).
- AC1.3 Migration tidak menghapus data keluarga yang sudah ada.

### FR2 — Login by kode

- AC2.1 Setiap user punya `app_login_code` acak minimal 16 karakter dari charset
  yang ambigu (tanpa `0/O`, `1/I/l`).
- AC2.2 Kode **di-hash** di database; plaintext hanya ditampilkan sekali.
- AC2.3 `POST /api/app/login` dengan `{code}` mengembalikan token Sanctum.
- AC2.4 Kode salah → 401 dengan pesan generik, dan rate limit 5 percobaan/menit
  per IP.
- AC2.5 `POST /api/app/login-code/rotate` menghasilkan kode baru.
- AC2.6 Login by kode **tidak** menerima email, dan respons tidak membocorkan
  apakah kode itu ada.

### FR3 — Device & notifikasi

- AC3.1 `user_devices`: `user_id`, `fcm_token` (unique), `platform`, `last_seen_at`.
- AC3.2 `POST /api/app/devices` mendaftarkan/memperbarui token.
- AC3.3 Bila FCM belum dikonfigurasi, sistem memakai polling dan app tetap
  berjalan normal.

### FR4 — API keluarga

- AC4.1 Semua endpoint menolak user yang bukan anggota keluarga tersebut (403).
- AC4.2 `GET /api/families` menampilkan keluarga milik user beserta jumlah anggota.
- AC4.3 CRUD transaksi keluarga dengan filter tipe, kategori, rentang tanggal, search.
- AC4.4 `GET /api/families/debts` + `POST /api/families/debts/{id}/payments`
  untukcicilan; `paid_amount` tidak boleh melebihi `amount`.
- AC4.5 CRUD anggaran dan target.
- AC4.6 `GET /api/families/summary` untuk health score keluarga.

### FR5 — Sumber pemasukan

- AC5.1 `income_sources`: `family_id`, `name`, `type` (salary|side|business|other),
  `is_default`, timestamps.
- AC5.2 `family_transactions` dapat menunjuk `income_source_id`.
- AC5.3 Ringkasan "pemasukan per sumber" tersedia di endpoint summary.

### FR6 — AI insight otomatis

- AC6.1 Command terjadwal `finance:insights` berjalan setiap Senin 04:00.
- AC6.2 Per keluarga, menghasilkan: 1 pesan kesehatan keluarga + 1 pesan personal
  per anggota aktif.
- AC6.3 Hasil disimpan di `family_insights` (cache) dan dikirim ke device tiap anggota
  sesuai `payer` masing-masing.
- AC6.4 Bila AI tidak terkonfigurasi/gagal, sistem memakai `FamilyAIService::ruleSummary`
  (gratis) dan tetap mengirim notifikasi.
- AC6.5 Satu keluarga tidak_duplikat insight untuk hari yang sama.
- AC6.6 Pesan ringkas: maksimal 240 karakter, gaya satukan kalimat.

### FR7 — Nudge instan (gratis)

- AC7.1 Setelah transaksi disimpan, endpoint mengembalikan satu ringkasan rule
  (tanpa panggilan AI).
- AC7.2 Contoh aturan: pengeluaran kategori X melampaui persentasen budget; saldo
  turun di bawah sisa hari; total hutang naik; belum ada pemasukan bulan ini.
- AC7.3 Nudge tidak pernah negatif secaraUpdates; kalau tidak ada masalah, kembalikan
  null (bukan pesan kosong).

### FR8 — UI Android

- AC8.1 Login layar kode, bukan email/password.
- AC8.2 Palet warna konsisten (hijau = sehat, amber = perlu perhatian, merah = bahaya).
- AC8.3 Dashboard menampilkan insight terbaru **otomatis** saat dibuka, tanpa tombol.
- AC8.4 Layar hutang, anggaran, target, sumber pemasukan.
- AC8.5 Notifikasi lokal saat insight masuk, dengan deep link ke dashboard.
- AC8.6 Empty state yang informatif di setiap layar.

## 6. Data Changes

| Tabel | Perubahan |
|---|---|
| `housing_complexes` | baru |
| `families` | + `housing_complex_id` (nullable) |
| `users` | + `app_login_code` (nullable, hashed) |
| `user_devices` | baru |
| `family_insights` | baru |
| `income_sources` | baru |
| `family_transactions` | + `income_source_id` (nullable) |

Semua nullable/nullable-safe. Tidak ada data yang dihapus.

## 7. Security

- Kode login di-hash (`hash('sha256', ...)` dengan prefix) — perlu dicek apakah
  cukup; bila tidak, pakai `Hash::check` dengan bcrypt untuk paranoid.
- Rate limit login by kode.
- Semua endpoint keluarga memverifikasi keanggotaan lewat `family_members`.
- API key AI tidak pernah dikirim ke client.
- FCM service account hanya dari env/secret manager.

## 8. Increments

1. **I1** Holmes + `app_login_code` + login by kode + tes.
2. **I2** Device registration + endpoint keluarga dasar.
3. **I3** API hutang, anggaran, target, sumber pemasukan.
4. **I4** `family_insights` + command mingguan + nudge instan.
5. **I5** Android: login kode, redesign, layar baru.
6. **I6** Android: notifikasi (FCM + polling fallback).

## 9. Risiko

- Migration pada tabel yang sudah berisi data produksi → wajib `nullable` + expand-first.
- Biaya API AI: 1 keluarga = 1 + jumlah anggota panggilan per minggu. Untuk 100
  keluarga dengan 2 anggota = 300 panggilan/minggu. Perlu rate limit dan cache.
- FCM butuh `google-services.json` dari pengguna; sampai ada, pakai polling.
