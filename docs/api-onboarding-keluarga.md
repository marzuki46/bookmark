# API Onboarding Keluarga

Dokumen ini menjelaskan cara menambahkan anggota keluarga dari aplikasi dan
mendapatkan kode login serta informasi APK terbaru.

## Authentication

Gunakan token Sanctum pada header:

```http
Authorization: Bearer <token-kepala-keluarga>
Accept: application/json
Content-Type: application/json
```

Token harus milik anggota dengan hak kelola pada keluarga tersebut. Biasanya
ini adalah kepala keluarga.

## Tambah Anggota

```http
POST /api/families/{family_id}/members
```

Request:

```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "relationship": "adult",
  "payer_role": "husband"
}
```

Fields:

- `name`: wajib, string, maksimal 255 karakter.
- `email`: email baru anggota. Boleh dikosongkan untuk akun family-only.
- `relationship`: `adult` atau `child`, default `adult`.
- `payer_role`: opsional, `husband` atau `wife`. Satu peran hanya boleh dipakai
  sekali dalam satu keluarga.

Response `201 Created`:

```json
{
  "data": {
    "user_id": 42,
    "name": "Budi Santoso",
    "payer_role": "husband",
    "payer_label": "Suami",
    "login_code": "K7QM-3ZPD-8RWT-5XNB",
    "app": {
      "latest_version_code": 12,
      "latest_version_name": "1.2.0",
      "download_url": "https://bookmark.juki.eu.org/apk/download/12",
      "notes": "Perbaikan bug",
      "is_mandatory": false
    }
  }
}
```

`login_code` diberikan sekali pada respons pembuatan anggota. Simpan atau
teruskan kode tersebut kepada anggota. Kode dipakai pada endpoint login app:

```http
POST /api/app/login
Content-Type: application/json

{
  "code": "K7QM-3ZPD-8RWT-5XNB",
  "device_name": "Android Budi"
}
```

Jika `app.download_url` kosong, APK terbaru belum dikonfigurasi. Aplikasi juga
dapat mengecek versi secara mandiri:

```http
GET /api/app/updates?current_version_code=11
```

## Error

Validation menggunakan status `422` dengan bentuk:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["Email sudah dipakai."]
  }
}
```

Kemungkinan lain:

- `401`: token tidak valid atau belum login.
- `403`: pengguna bukan anggota yang berhak mengelola keluarga.
- `404`: keluarga tidak ditemukan atau tidak dapat diakses.
- `422`: keluarga sudah mencapai maksimal 5 anggota, email sudah dipakai,
  atau payer role sudah dipakai.

## Catatan Keamanan

- Jangan mencatat `login_code` ke log server atau analytics.
- Jangan mengirim kode melalui URL query string.
- Kode login bersifat permanen sampai dirotasi dari aplikasi.
- Jika kode bocor, gunakan `POST /api/app/login-code/rotate` setelah login untuk
  mencabut token lama dan menerbitkan kode baru.
