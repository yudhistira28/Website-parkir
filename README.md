# Aplikasi Parkir - Tirta Tamansari

Aplikasi manajemen parkir **Tirta Tamansari**, dibangun dengan PHP native + MySQL (PDO)
dan tampilan Bootstrap 5, mengikuti struktur folder yang diminta serta ketentuan soal
**Uji Kompetensi Keahlian - Pengembangan Aplikasi Parkir (KM25.4.1.1)**.

## 1. Struktur Folder

```
parkir_tirta_tamansari/
├── admin/                  -> Halaman & fitur untuk role ADMIN
│   ├── template/            (header, footer, sidebar_admin)
│   ├── index.php            (dashboard admin)
│   ├── kelola_user.php      (CRUD user: admin/petugas/owner)
│   ├── kelola_tarif.php     (CRUD tarif parkir)
│   ├── kelola_area.php      (CRUD area parkir)
│   ├── kelola_kendaraan.php (CRUD data kendaraan)
│   └── log_aktivitas.php    (akses log aktivitas seluruh user)
├── assets/                  -> css & js
├── auth/                    -> login.php, logout.php, register.php
├── config/                  -> koneksi.php (koneksi DB + helper cekLogin, catatLog)
├── database/                -> db_tirta_tamansari_parkir.sql
├── img/                     -> logo/aset gambar
├── operator/                -> Halaman & fitur untuk role PETUGAS
│   ├── components/           (navbar, header, footer)
│   ├── index.php             (dashboard petugas)
│   ├── transaksi_masuk.php   (input kendaraan masuk)
│   ├── transaksi_keluar.php  (proses kendaraan keluar & hitung biaya)
│   ├── cetak_struk.php       (cetak struk masuk/keluar)
│   └── riwayat_transaksi.php (riwayat transaksi milik petugas)
├── owner/                   -> Halaman & fitur untuk role OWNER
│   ├── components/           (sidebar_owner, header, footer)
│   ├── index.php             (dashboard owner: grafik pendapatan)
│   └── rekap_transaksi.php   (rekap transaksi sesuai rentang tanggal)
├── uploads/                 -> profile_admin, profile_operator, profile_user
└── index.php                -> redirect otomatis ke login/dashboard
```

> **Catatan:** struktur folder mengikuti kerangka yang anda kirim (readme.md), disesuaikan
> isinya untuk kebutuhan aplikasi **parkir** dengan 3 role (Admin, Petugas, Owner) sesuai
> dokumen soal. Folder `users/` pada kerangka awal digunakan sebagai `owner/` karena role
> ketiga pada soal adalah Owner, bukan end-user biasa.

## 2. Instalasi (XAMPP / Laragon)

1. Copy folder `parkir_tirta_tamansari` ke dalam `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka **phpMyAdmin**, buat database dengan cara **Import** file
   `database/db_tirta_tamansari_parkir.sql` (database & tabel akan otomatis dibuat, nama
   database **db_tirta_tamansari_parkir**, bukan `db_parkir`).
3. Buka `config/koneksi.php`, sesuaikan bila perlu:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'db_tirta_tamansari_parkir');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('BASE_URL', 'http://localhost/parkir_tirta_tamansari/');
   ```
   Ubah `BASE_URL` jika nama folder project anda berbeda.
4. Akses aplikasi melalui `http://localhost/parkir_tirta_tamansari/`.

## 3. Akun Default (Seeder)

| Role     | Username  | Password |
|----------|-----------|----------|
| Admin    | admin     | 12345    |
| Petugas  | petugas1  | 12345    |
| Owner    | owner1    | 12345    |

Segera ubah password melalui menu **Kelola User** setelah login pertama kali.

## 4. Hak Akses Fitur (sesuai soal)

| Fitur                             | Admin | Petugas | Owner |
|------------------------------------|:-----:|:-------:|:-----:|
| Login / Logout                     | ✔     | ✔       | ✔     |
| CRUD User                          | ✔     |         |       |
| CRUD Tarif Parkir                  | ✔     |         |       |
| CRUD Area Parkir                   | ✔     |         |       |
| CRUD Kendaraan                     | ✔     |         |       |
| Akses Log Aktivitas                | ✔     |         |       |
| Kendaraan Masuk (Transaksi)        |       | ✔       |       |
| Kendaraan Keluar + Cetak Struk     |       | ✔       |       |
| Rekap Transaksi sesuai periode     |       |         | ✔     |

## 5. Skema Database

Tabel: `tb_user`, `tb_kendaraan`, `tb_tarif`, `tb_area_parkir`, `tb_transaksi`,
`tb_log_aktivitas` — mengikuti ERD pada dokumen soal (relasi antar tabel dijaga
dengan FOREIGN KEY).

## 6. Alur Kerja Aplikasi

1. **Petugas** mencatat kendaraan masuk (`transaksi_masuk.php`) → sistem otomatis
   mengurangi slot area parkir yang tersedia & mencetak tiket masuk.
2. Saat kendaraan keluar, petugas memilih kendaraan pada `transaksi_keluar.php` →
   sistem menghitung durasi parkir & biaya otomatis berdasarkan tarif per jam →
   struk pembayaran dicetak.
3. **Admin** mengelola master data (user, tarif, area, kendaraan) dan memantau log
   aktivitas seluruh pengguna.
4. **Owner** memantau dashboard pendapatan & dapat melihat rekap transaksi pada
   rentang tanggal tertentu, lengkap dengan opsi cetak.

---
Dibuat sesuai kerangka struktur project yang diberikan, untuk memenuhi ketentuan
Soal Praktik Kejuruan "Pengembangan Aplikasi Parkir" (Paket 2, RPL 2025/2026),
ditematik ulang untuk kebutuhan parkir **Tirta Tamansari**.
