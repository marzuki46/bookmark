\# AI Agent Development Rules



Tujuan:

Mengembangkan aplikasi hingga seluruh spesifikasi terpenuhi.



Aturan:



\- Jangan mengubah requirement tanpa alasan.

\- Selalu baca seluruh folder docs sebelum mulai bekerja.

\- Implementasikan satu fitur dalam satu waktu.

\- Setelah selesai, jalankan testing.

\- Jika test gagal, perbaiki hingga berhasil.

\- Jangan lanjut ke fitur berikutnya sebelum fitur saat ini selesai.

\- Selalu lakukan refactoring jika menemukan kode duplikat.

\- Jangan meninggalkan TODO.

\- Jangan membuat dead code.

\- Selalu update dokumentasi jika ada perubahan.





Personal Knowledge Hub AI - Product Specification



\## Tujuan

Membangun aplikasi web pribadi berbasis Laravel 13 + PHP 8.4 yang menjadi pusat bookmark, catatan, snippet, prompt AI, file, dan secret vault. Aplikasi hanya memiliki \*\*1 pengguna (Owner)\*\*.



\## Prinsip

\- Single user.

\- Mobile \& desktop.

\- Chrome Extension menggunakan REST API dari aplikasi utama.

\- Extension \*\*tidak memiliki database sendiri\*\*.

\- Extension hanya menyimpan API Token yang dibuat dari website.

\- Input seminimal mungkin, otomatisasi semaksimal mungkin.



\## Modul



\### Dashboard

\- Statistik

\- AI Insight

\- Aktivitas terbaru

\- Pencarian global



\### Bookmark

Input minimum:

\- URL



Otomatis:

\- Title

\- Meta description

\- Thumbnail

\- Favicon

\- Domain

\- Canonical

\- OpenGraph

\- Estimated reading time

\- AI Summary

\- AI Category

\- AI Tags



\### Notes

\- Markdown

\- Checklist

\- Attachment



\### Prompt Library

\- Prompt

\- Kategori

\- Tag



\### Code Snippet

\- Bahasa

\- Kode

\- Deskripsi



\### File Manager

\- PDF

\- DOCX

\- XLSX

\- ZIP

\- Image



\### Secret Vault

\- API Key

\- License

\- Domain

\- Server

\- SSH

\- Token

Semua dienkripsi.



\## AI Engine



\### Auto Summary

Merangkum halaman.



\### Auto Category

Mengelompokkan topik.



\### Auto Tag

Memberikan tag otomatis.



\### Duplicate Detection

Mendeteksi konten mirip.



\### Collection Recommendation

Menyarankan collection baru berdasarkan pola.



\### Topic Insight

Menampilkan topik yang paling sering disimpan.



\### Knowledge Gap

Memberi rekomendasi topik yang belum lengkap.



\### Smart Search

Pencarian semantik.



\## Chrome Extension



Fitur:

\- Save Page

\- Save Selected Text

\- Save Image

\- Quick Note



Flow:

1\. Login di website.

2\. Generate Personal API Token.

3\. Tempel token di extension (sekali).

4\. Semua penyimpanan menggunakan REST API.



\## Database Inti



\- users

\- personal\_access\_tokens

\- items

\- folders

\- collections

\- tags

\- item\_tags

\- attachments

\- ai\_summaries

\- ai\_suggestions

\- settings

\- activity\_logs



\## Tabel items



\- id

\- type

\- title

\- url

\- content

\- metadata (JSON)

\- folder\_id

\- favorite

\- archived\_at

\- created\_at

\- updated\_at



\## Teknologi



\- Laravel 13

\- PHP 8.4

\- MySQL 8

\- Livewire 3

\- Tailwind CSS 4

\- Alpine.js

\- Redis

\- Horizon

\- Spatie Media Library

\- Spatie Activitylog

\- Meilisearch (opsional)



\## Loop Engineering Workflow



AI agent WAJIB mengikuti siklus berikut sampai seluruh fitur selesai dan lolos validasi.



1\. Analisis requirement.

2\. Pecah menjadi task kecil.

3\. Implementasi satu task.

4\. Jalankan test.

5\. Perbaiki error.

6\. Refactor.

7\. Jalankan test ulang.

8\. Ulangi langkah 3-7 sampai seluruh test berhasil.

9\. Jalankan static analysis.

10\. Verifikasi API.

11\. Verifikasi UI.

12\. Verifikasi database migration.

13\. Verifikasi security.

14\. Verifikasi performa.

15\. Jangan menganggap pekerjaan selesai jika masih ada error, failing test, TODO, warning penting, atau fitur yang belum sesuai spesifikasi.



\## Definition of Done



\- Semua migration sukses.

\- Semua endpoint berjalan.

\- Semua fitur dapat digunakan.

\- Tidak ada error di log.

\- Tidak ada failing test.

\- UI responsif.

\- AI feature berjalan.

\- Extension dapat menyimpan data ke API.

\- Dokumentasi diperbarui.

