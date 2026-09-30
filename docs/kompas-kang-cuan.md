# Rencana: Kang Cuan — Teman Keuangan Keluarga (Pendamping Keuangan)

Status: rencana tahapan yang disepakati; dikerjakan bertahap dengan verifikasi tiap tahap.
Tanggal: 2026-09-29

## 1. Identitas & Branding

- Nama aplikasi: **Kang Cuan** (identitas keuangan: "cuan" = untung; label di bawah
  ikon HP: `Kang Cuan`).
- Tagline: **"Soal cuan, urusan Kang Cuan."** Deskripsi: "Kang Cuan — tukang cuan
  keluarga. Membantu mengatur pemasukan, pengeluaran, jajan, cicilan & dana darurat
  agar tetap beres."
- Nama fitur saran: **Pendamping Keuangan** (bukan "Kompas").
- Maskot: anak kecil bergaya kartun sederhana; wajah bulat, rambut pendek, senyum
  hangat, warna teal + aksen kuning; membawa celengan/buku catatan.
- Ikon launcher: wajah Kang Cuan dengan elemen rumah kecil; adaptif Android,
  versi monokrom untuk themed icons, ikon notifikasi sederhana.
- Paket identitas aplikasi (`com.keuangan.app...`) TIDAK diganti agar update
  tetap kompatibel; hanya label tampilan & ikon yang berubah.

## 2. Fitur Pendamping Keuangan (opsional, dimatikan secara default)

### 2.1 Aktifasi
- Home menampilkan ajakan: "Mau dibantu mengatur keuangan keluarga?" dengan
  tombol `Aktifkan Pendamping` dan `Nanti saja`.
- Kepala keluarga yang mengaktifkan/menonaktifkan. Nonaktif = saran berhenti;
  data transaksi/anggaran yang sudah disetujui tetap tersimpan.

### 2.2 Profil keluarga (dikonfirmasi, bukan hanya input baru)
- Pemasukan bersih (tetap / tidak tetap).
- Jumlah anggota & tanggungan.
- Kondisi hunian: rumah lunas, KPR, kontrak/kos, tinggal bersama keluarga.
- Cicilan & sisa hutang (dimuat dari modul Hutang).
- Cakupan kesehatan: BPJS/asuransi aktif, siapa yang belum tercakup.
- Dana darurat tersedia (dimuat dari target emergency_fund).
- Kebutuhan tetap: sekolah, transportasi, tagihan rutin, kewajiban tahunan.
- Prioritas keluarga.

### 2.3 Definisi "keuangan sehat" (berbasis kondisi, bukan angka kosong)
- Arus kas: pemasukan - pengeluaran; defisit tetap negatif.
- Kewajiban: kebutuhan pokok & tagihan wajib terpenuhi.
- Cicilan: proporsi terhadap pemasukan bersih + sisa uang untuk hidup.
- Dana darurat: berapa bulan kebutuhan esensial yang bisa ditanggung.
- Perlindungan kesehatan: tercakup / belum / belum diketahui.
- Tujuan: ada alokasi realistis.
- Jika data tidak cukup: tampilkan "Belum cukup data untuk menilai", bukan "Sehat".

### 2.4 Alokasi uang
Urutan: pemasukan bersih -> kebutuhan pokok+proteksi+cicilan wajib -> ruang sisa
-> dana darurat, tujuan, pengeluaran pilihan (jajan). Jika kewajiban melebihi
pemasukan: tampilkan skenario defisit, jangan memaksakan anggaran.

Contoh awal Rp8jt (patokan awal, bukan wajib; menyesuaikan kondisi nyata):
- Kebutuhan pokok & hunian 50% = Rp4.000.000
- Cicilan wajib 15% = Rp1.200.000
- Kesehatan/proteksi 5% = Rp400.000
- Dana darurat 10% = Rp800.000
- Pendidikan/tujuan/tabungan 10% = Rp800.000
- Jajan & hiburan 5% = Rp400.000
- Cadangan kebutuhan tidak rutin 5% = Rp400.000

Target dana darurat dihitung dari kebutuhan esensial bulanan x 3 (bisa ditambah
untuk penghasilan tidak tetap), dengan alasan yang terlihat.

### 2.5 Skenario
- Menstabilkan arus kas (defisit).
- Membangun dana darurat.
- Mempercepat pelunasan hutang.
- Seimbang (rutin + tabungan + jajan).
- Mengejar tujuan (sekolah/uang muka).

Tiap skenario: persentase & nominal per pos, alasan, estimasi dampak & waktu,
tombol `Ubah pembagian` dan `Terapkan ke anggaran`. Tidak otomatis membuat
transaksi/menganggap uang sudah ditabung.

### 2.6 Belajar dari pengguna (transparan)
- Pola pengeluaran rutin per kategori, pemasukan tetap vs bonus, pos yang sering
  melewati anggaran. Contoh:
  "Transportasi 3 bulan terakhir rata-rata Rp650.000. Anggaran Rp400.000. Mau
  menyesuaikan anggaran atau menargetkan pengurangan?"
- Bulan tanpa catatan tidak dianggap nol; bulan berjalan tidak dibandingkan
  langsung dengan bulan penuh. Perhitungan nominal pakai aturan yang diuji; AI
  hanya membantu penjelasan.

### 2.7 Tampilan mobile
- Menu berisi: Kondisi sekarang, Rencana bulan ini, Sisa anggaran per pos,
  Pilihan skenario, Profil & pengaturan.
- Model mengambang: avatar Kang Cuan (di atas bottom nav) -> bottom sheet saran +
  aksi (`Lihat rencana`/`Sesuaikan anggaran`); bisa disembunyikan/dinonaktifkan.
- Tidak menutupi tombol simpan/keyboard/navigasi.
- Akses sesuai permission: anggota hanya melihat data yang diizinkan; anak tidak
  mendapat bocoran gaji/hutang lewat teks saran.

## 3. Desain Android (Gojek-style, ringkas)

- Header: sapaan + nama keluarga, tanpa banner tinggi.
- Ringkasan utama: "Selisih bulan ini" (bukan "Saldo"), nominal utama 24sp.
- Pemasukan/pengeluaran sebagai baris pendamping dalam satu kartu.
- Skor kesehatan TIDAK mendominasi Home; detailnya di Pendamping Keuangan.
- Skala font: judul 18-20sp, nominal utama 24sp, nominal kartu/transaksi 14-16sp,
  isi 14sp, label 12-13sp. Nominal diuji sampai miliaran; hormati fontScale.
- Grid ikon fitur (Catat/Anggaran/Hutang/Tabungan/Laporan/Pendamping/Keluarga/Semua).
- Ikon "Lainnya" di ganti dari titik tiga menjadi grid 4 kotak (GridView).
- Area sentuh minimal ~48dp; layout tahan layar sempit & font besar.
- Konsistensi angka: defisit negatif, seimbang Rp0, kosong = "Belum ada catatan",
  sisa anggaran negatif = "Melebihi anggaran Rp…", surplus bukan otomatis tabungan.

## 4. Rombakan Dashboard Laravel

### Ruang Admin (mengelola layanan Kang Cuan)
Ringkasan | Keluarga: Daftar Keluarga | Bisnis: Paket & Harga, Lisensi Keluarga,
Pembayaran | Operasional: Perangkat, Aktivitas & Error, Rilis Android | Sistem:
Akun & Akses Admin, Pengaturan Aplikasi.

### Detail Keluarga (pusat pengelolaan)
Tab: Ringkasan | Anggota & Akses | Lisensi & Pembayaran | Keuangan | Perangkat |
Aktivitas. Konteks keluarga selalu terlihat.

### Ruang Keluarga (mengatur keuangan sendiri)
Beranda | Transaksi | Anggaran | Tabungan & Tujuan | Hutang & Cicilan | Laporan |
Anggota Keluarga | Pendamping Keuangan | Lisensi Keluarga | Pengaturan.
- Kepala keluarga: kelola anggota, izin, profil pendamping, rencana.
- Anggota dewasa: sesuai kewenangan.
- Anak/terbatas: hanya menu & data yang diizinkan (ditegakkan di server).

### Perpindahan menu lama
| Lama | Baru |
|---|---|
| Dashboard (2x) | Ringkasan Admin / Beranda Keluarga |
| Pengguna | Anggota pada Detail Keluarga |
| Langganan | Lisensi Keluarga |
| Keuangan User | Daftar Keluarga -> Keuangan |
| Log Akses | Aktivitas & Error (dengan filter keluarga) |
| Mode App (HP) | Tautan "Buka tampilan mobile" |
| Rilis Aplikasi | Rilis Android |

- Test negatif child: 403 (sudah ada, dijaga).
- Pemisahan role & menu tidak mengganti penegakan otorisasi di server.

## 5. Koreksi perhitungan (harus diuji dulu)

- [x] Defisit: hapus `max(0, income-expense)`; selisih boleh negatif. (DONE, test
      `test_summary_preserves_deficit_and_balanced_and_positive_cash_flow`)
- [x] Target dana darurat = kebutuhan esensial bulanan x 3 (bukan 1x). (DONE,
      `FamilyAllocationService::emergencyFundTarget`; test
      `test_emergency_fund_target_is_three_months_of_average_expense`)
- [~] Skor kesehatan memakai kebutuhan esensial, bukan pengeluaran biasa.
      (Emergency target & coverage sudah berbasis kebutuhan esensial di
      FamilyAdvisorService; skor FamilyAIService belum dirombak.)
- [ ] Cicilan tidak dihitung dua kali (tergantung preferensi pencatatan);
      dipisahkan rencana vs realisasi.
- [ ] Data kosong -> "Belum cukup data", bukan "Sehat".
- [ ] Ringkasan/insight tidak membocorkan data yang disembunyikan anggota.

## 6. Rilis & Deploy

- APK: versionCode/versionName konsisten dengan metadata server; signing konsisten.
- Downloyal & instalasi memberi status jelas di HP.
- Deploy Laravel online diverifikasi; proses diulang + screenshots/perangkat.
- Semua perubahan lokal: status "lokal / diuji / online" dibedakan jelas.
- `docs/spec-aplikasi-keluarga.md` & `docs/kompas-kang-cuan.md`: tidak di-commit/push.

## 7. Urutan pengerjaan (eksekusi)

1. Simpan rencana ini (DONE). 
2. Koreksi perhitungan + test. (DONE, sisa: skor kesehatan & kasus data kosong)
3. Fitur Pendamping Keuangan. (DONE backend: toggle+profil+skenario+test;
      Android: kartu toggle di Lainnya. Sisa: profil form, bottom sheet, grid Home)
4. Branding Android. (DONE: nama Kang Cuan, ikon launcher maskot lucu, ikon grid "Lainnya")
5. Desain Home + model mengambang (bottom sheet). (DONE grid Home + kartu Selisih
      bulan ini; sisa: bottom sheet Kang Cuan & form profil pendamping)
6. Rombak dashboard Laravel. (BELUM)
7. Build lint + full test + verifikasi visual; deploy & rilis.