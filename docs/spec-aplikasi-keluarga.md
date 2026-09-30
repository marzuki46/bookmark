# Spec: Aplikasi Keuangan Keluarga (Android) + AI Insight Otomatis

Status: baseline produk untuk implementasi
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
| Anggota keluarga | Satu akun = satu orang yang menjadi anggota satu keluarga. |
| Kepala keluarga | Pembeli/order pertama; pemilik lisensi dan pengatur anggota serta izin data. |
| Anggota dewasa | Suami, istri, atau peran dewasa lain yang ditetapkan kepala keluarga. |
| Anak | Anggota dengan akses terbatas sesuai pengaturan kepala keluarga. |
| Admin aplikasi | Mengelola banyak keluarga, memantau pengguna/perangkat/lisensi/error, dan dapat melihat data untuk dukungan operasional sesuai audit. |
| Server | Menyimpan data, menjalankan AI, mengirim notifikasi. |

## 3. Tenancy (3 lapis)

```
Perumahan (housing_complex)
  └── Keluarga (family)
        └── Anggota (family_members) -> user
              └── family_transactions (payer = husband | wife | shared)
```

**Keputusan domain:**

- Satu user hanya boleh menjadi anggota **satu** keluarga untuk keperluan app.
  Kemampuan `invite_code` di web tetap dibiarkan, tetapi app tidak pernah
  menawarkan alur "gabung keluarga lain".
- Orang yang melakukan order pertama menjadi kepala keluarga dan pemilik lisensi.
- Kepala keluarga dapat menetapkan peran anggota sebagai suami, istri, anak, atau anggota lain.
- Lisensi melekat pada keluarga, bukan pada akun suami/istri secara terpisah.
- Pembayaran mencatat user pembayar untuk audit, tetapi hak pakai diberikan ke seluruh anggota keluarga.
- Kepala keluarga dapat membatasi anak dari pemasukan, pengeluaran, hutang, atau detail anggota dewasa. Pembatasan ditegakkan di API, bukan hanya UI.
- Admin aplikasi memiliki akses operasional yang diaudit untuk memastikan aplikasi berjalan lancar.

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
- AC4.7 Semua laporan, transaksi, hutang, target, dan anggaran mengikuti izin anggota yang sedang login.
- AC4.8 Kepala keluarga dapat mengubah izin visibilitas anggota.

### FR4A — Lisensi keluarga

- AC4A.1 Subscription memiliki `family_id`; satu keluarga memiliki satu entitlement aktif.
- AC4A.2 Semua anggota keluarga melihat status lisensi keluarga yang sama.
- AC4A.3 Pembayaran menyimpan `user_id` sebagai pembayar dan `family_id` sebagai pemilik manfaat.
- AC4A.4 Admin dapat mengelola lisensi dari halaman detail keluarga.

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
- AC8.7 Navigasi utama selalu menampilkan label yang jelas.
- AC8.8 Layar langganan menampilkan pemilik lisensi keluarga, status, tanggal berakhir, dan catatan update.
- AC8.9 Update aplikasi menampilkan versi, tanggal, fitur baru, perbaikan bug, keamanan, dan status wajib/disarankan.

### FR9 — Admin aplikasi

- AC9.1 Dashboard admin memiliki Manajemen Keluarga sebagai pusat navigasi pelanggan.
- AC9.2 Detail keluarga memiliki tab Ringkasan, Anggota, Laporan, Lisensi, Perangkat, dan Aktivitas.
- AC9.3 Admin dapat melihat status aktif user/perangkat, error aplikasi, dan kesehatan API.
- AC9.4 Akses admin ke data keluarga dicatat dalam activity log.

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
| `subscriptions` | + `family_id` (nullable saat expand; backfill dari owner) |
| `subscription_payments` | + `family_id` (nullable saat expand) |
| `family_members` | + visibility permission fields atau permission JSON yang tervalidasi |
| `app_releases` | + release notes terstruktur dan mandatory flag |

Semua nullable/nullable-safe. Tidak ada data yang dihapus.

## 7. Security

- Kode login di-hash (`hash('sha256', ...)` dengan prefix) — perlu dicek apakah
  cukup; bila tidak, pakai `Hash::check` dengan bcrypt untuk paranoid.
- Rate limit login by kode.
- Semua endpoint keluarga memverifikasi keanggotaan lewat `family_members`.
- Semua endpoint keluarga memverifikasi visibility permission setelah membership.
- Kode keluarga/login dibuat dengan CSPRNG, disimpan hash, di-rate-limit, dan dapat dirotasi.
- Endpoint `/api/*` tidak boleh dilayani cache CDN.
- ID keluarga dari client tidak pernah menjadi bukti kepemilikan.
- API key AI tidak pernah dikirim ke client.
- FCM service account hanya dari env/secret manager.

## 8. Database & Performance

- Index tenant utama: `(family_id, date)`, `(family_id, type, date)`, `(family_id, status)`, dan `(family_id, user_id)`.
- Membership memiliki unique constraint untuk satu user dalam satu keluarga.
- Query laporan wajib membatasi tenant dan rentang tanggal sebelum agregasi.
- Migration produksi memakai expand-and-contract; tidak menghapus kolom lama pada deployment pertama.

## 9. Release Notes & Updates

- Setiap release memiliki `version_code`, `version_name`, `released_at`, `notes`, dan `is_mandatory`.
- Catatan rilis dibagi menjadi fitur baru, perbaikan bug, keamanan, performa, dan perubahan UI.
- Update opsional menampilkan notifikasi berulang secara wajar.
- Update wajib memblokir akses setelah grace period sampai APK berhasil diperbarui.

## 10. Increments

1. **I1** Holmes + `app_login_code` + login by kode + tes.
2. **I2** Device registration + endpoint keluarga dasar.
3. **I3** API hutang, anggaran, target, sumber pemasukan.
4. **I4** `family_insights` + command mingguan + nudge instan.
5. **I5** Android: login kode, redesign, layar baru.
6. **I6** Android: notifikasi (FCM + polling fallback).

## 11. Risiko

- Migration pada tabel yang sudah berisi data produksi → wajib `nullable` + expand-first.
- Biaya API AI: 1 keluarga = 1 + jumlah anggota panggilan per minggu. Untuk 100
  keluarga dengan 2 anggota = 300 panggilan/minggu. Perlu rate limit dan cache.
- FCM butuh `google-services.json` dari pengguna; sampai ada, pakai polling.
