# Aplikasi Parkir - Tirta Tamansari

Aplikasi manajemen parkir **Tirta Tamansari**, dibangun dengan PHP native + MySQL (PDO)
dan tampilan Bootstrap 5, mengikuti struktur folder yang diminta serta ketentuan soal
**Uji Kompetensi Keahlian - Pengembangan Aplikasi Parkir (KM25.4.1.1)**.

## 1. Struktur Folder

```
parkir/
├── admin/                      -> Halaman & fitur untuk role ADMIN
│   ├── template/                 (header, footer, sidebar admin)
│   ├── index.php                 (dashboard admin)
│   ├── edit_profil.php           (edit profil admin)
│   ├── kelola_user.php           (CRUD user: admin/petugas/owner/member)
│   ├── kelola_tarif.php          (CRUD tarif parkir)
│   ├── kelola_area.php           (CRUD area parkir)
│   ├── kelola_kendaraan.php      (CRUD data kendaraan)
│   ├── pengajuan_aktivasi.php    (kelola pengajuan aktivasi akun)
│   ├── testimoni.php             (kelola testimoni yang masuk)
│   ├── testimoni_action.php      (proses approve/reject testimoni)
│   └── log_aktivitas.php         (akses log aktivitas seluruh user)
├── assets/                      -> css, js, audio
├── auth/                        -> login.php, logout.php, register.php
├── config/                      -> koneksi.php (koneksi DB + BASE_URL auto-detect)
├── database/                    -> file .sql (skema database & tabel tambahan)
├── img/                         -> logo/aset gambar & video
├── member/                      -> Halaman & fitur untuk role MEMBER (pengguna umum)
│   ├── template/
│   ├── index.php                 (dashboard member)
│   ├── booking.php               (booking slot parkir)
│   ├── cek_slot_area.php         (cek ketersediaan slot area parkir)
│   ├── cek_status_booking.php    (cek status booking yang diajukan)
│   ├── kendaraan.php             (kelola data kendaraan milik member)
│   └── edit_profil.php           (edit profil member)
├── operator/                    -> Halaman & fitur untuk role PETUGAS (operator)
│   ├── components/                (navbar, header, footer)
│   ├── index.php                  (dashboard petugas)
│   ├── kelola_booking.php         (kelola booking dari member)
│   ├── cek_booking_baru.php       (cek notifikasi booking baru)
│   ├── ambil_notifikasi.php       (ambil data notifikasi)
│   ├── tandai_notifikasi_dibaca.php (tandai notifikasi sudah dibaca)
│   ├── transaksi_masuk.php        (input kendaraan masuk)
│   ├── transaksi_keluar.php       (proses kendaraan keluar & hitung biaya)
│   ├── cetak_struk.php            (cetak struk masuk/keluar)
│   ├── riwayat_transaksi.php      (riwayat transaksi milik petugas)
│   └── edit_profil.php            (edit profil petugas)
├── owner/                       -> Halaman & fitur untuk role OWNER
│   ├── components/                (sidebar owner, header, footer)
│   ├── index.php                  (dashboard owner: grafik pendapatan)
│   ├── rekap_transaksi.php        (rekap transaksi sesuai rentang tanggal)
│   └── edit_profil.php            (edit profil owner)
├── uploads/                     -> profil, profile_admin, profile_operator, profile_user
├── ajukan_aktivasi.php          -> Form pengajuan aktivasi akun (untuk calon member)
├── testimoni_submit.php         -> Kirim testimoni dari halaman utama
└── index.php                    -> Landing page + redirect otomatis ke dashboard sesuai role
```

> **Catatan:** aplikasi memiliki **4 role** — Admin, Petugas (Operator), Owner, dan Member —
> ditambah fitur pendukung berupa pengajuan aktivasi akun, booking slot parkir oleh member,
> notifikasi booking untuk petugas, dan testimoni dari pengguna di halaman utama.

## 2. Instalasi (XAMPP / Laragon)

1. Copy folder `parkir` ke dalam `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka **phpMyAdmin**, buat database dengan cara **Import** file
   `database/db_tirta_tamansari_parkir.sql` (database & tabel utama akan otomatis dibuat,
   nama database **db_tirta_tamansari_parkir**). Import juga file `.sql` tambahan di folder
   `database/`, `member/`, dan `operator/` (misalnya untuk tabel pengajuan aktivasi dan
   notifikasi) bila belum tercakup di file utama.
3. `BASE_URL` di `config/koneksi.php` sudah **auto-detect** berdasarkan lokasi folder
   project, jadi tidak perlu diedit manual walaupun nama foldernya berbeda. Sesuaikan hanya
   bagian koneksi database bila perlu:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'db_tirta_tamansari_parkir');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
4. Akses aplikasi melalui `http://localhost/parkir/`.

## 3. Akun Default (Seeder)

| Role     | Username  | Password |
|----------|-----------|----------|
| Admin    | admin     | 12345    |
| Petugas  | petugas1  | 12345    |
| Owner    | owner1    | 12345    |

Segera ubah password melalui menu **Kelola User** / edit profil setelah login pertama kali.
Akun **Member** dapat didaftarkan sendiri melalui halaman **register** atau melalui alur
**pengajuan aktivasi** yang disetujui Admin.

## 4. Hak Akses Fitur

| Fitur                               | Admin | Petugas | Owner | Member |
|---------------------------------------|:-----:|:-------:|:-----:|:------:|
| Login / Logout / Edit Profil          | ✔     | ✔       | ✔     | ✔      |
| CRUD User                             | ✔     |         |       |        |
| CRUD Tarif Parkir                     | ✔     |         |       |        |
| CRUD Area Parkir                      | ✔     |         |       |        |
| CRUD Kendaraan                        | ✔     |         |       |        |
| Kelola Pengajuan Aktivasi             | ✔     |         |       |        |
| Kelola Testimoni                      | ✔     |         |       |        |
| Akses Log Aktivitas                   | ✔     |         |       |        |
| Kelola Booking & Notifikasi Booking   |       | ✔       |       |        |
| Kendaraan Masuk (Transaksi)           |       | ✔       |       |        |
| Kendaraan Keluar + Cetak Struk        |       | ✔       |       |        |
| Riwayat Transaksi                     |       | ✔       |       |        |
| Rekap Transaksi sesuai periode        |       |         | ✔     |        |
| Ajukan Aktivasi Akun                  |       |         |       | ✔      |
| Booking Slot Parkir                   |       |         |       | ✔      |
| Cek Slot Area & Status Booking        |       |         |       | ✔      |
| Kelola Data Kendaraan Sendiri         |       |         |       | ✔      |
| Kirim Testimoni                       |       |         |       | ✔      |

## 5. Skema Database

Tabel utama mengikuti ERD pada dokumen soal, ditambah tabel pendukung untuk fitur booking,
notifikasi, dan pengajuan aktivasi, antara lain: `tb_user`, `tb_kendaraan`, `tb_tarif`,
`tb_area_parkir`, `tb_transaksi`, `tb_log_aktivitas`, `tb_pengajuan_aktivasi`,
`tb_notifikasi`, dan `testimoni` — relasi antar tabel dijaga dengan FOREIGN KEY.

## 6. Alur Kerja Aplikasi

1. **Member** mendaftar akun (atau mengajukan aktivasi melalui `ajukan_aktivasi.php` yang
   disetujui Admin), lalu dapat melakukan **booking slot parkir**, mengecek ketersediaan
   area, memantau status booking, dan mengelola data kendaraan miliknya sendiri.
2. **Petugas (Operator)** menerima notifikasi booking baru dari member, mengelola booking
   tersebut, mencatat kendaraan masuk (`transaksi_masuk.php`) → sistem otomatis mengurangi
   slot area parkir yang tersedia & mencetak tiket masuk.
3. Saat kendaraan keluar, petugas memilih kendaraan pada `transaksi_keluar.php` → sistem
   menghitung durasi parkir & biaya otomatis berdasarkan tarif per jam → struk pembayaran
   dicetak.
4. **Admin** mengelola master data (user, tarif, area, kendaraan), menyetujui pengajuan
   aktivasi akun, mengelola testimoni yang masuk, dan memantau log aktivitas seluruh
   pengguna.
5. **Owner** memantau dashboard pendapatan & dapat melihat rekap transaksi pada rentang
   tanggal tertentu, lengkap dengan opsi cetak.
6. Pengunjung di halaman utama (`index.php`) dapat melihat testimoni yang sudah disetujui
   Admin dan mengirim testimoni baru melalui `testimoni_submit.php`.

## 7. Username & password login
   Login admin
   username: admin
   password: admin123

   Login petugas
   username: petugas
   password: petugas123

   Login owner
   username: owner
   password: owner123

   Login tamu
   username: tamu
   password: tamu123
---
Dibuat sesuai kerangka struktur project yang diberikan, untuk memenuhi ketentuan
Soal Praktik Kejuruan "Pengembangan Aplikasi Parkir" (Paket 2, RPL 2025/2026),
ditematik ulang untuk kebutuhan parkir **Tirta Tamansari**.
