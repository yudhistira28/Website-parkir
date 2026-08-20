-- =====================================================
-- PARKIR TIRTA TAMANSARI
-- Skema database — disusun dari pembacaan langsung semua
-- query (SELECT/INSERT/UPDATE/DELETE) di seluruh kode PHP.
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS db_tirta_tamansari_parkir
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_tirta_tamansari_parkir;

-- =====================================================
-- tb_user
-- Dipakai: auth/login.php, auth/register.php, admin/kelola_user.php,
--          member/edit_profil.php, tb_log_aktivitas (JOIN)
-- =====================================================
DROP TABLE IF EXISTS tb_user;
CREATE TABLE tb_user (
    id_user       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap  VARCHAR(150)        NOT NULL,
    username      VARCHAR(50)         NOT NULL UNIQUE,
    password      VARCHAR(255)        NOT NULL,   -- hash password_hash() / bcrypt
    role          ENUM('admin','petugas','owner','member') NOT NULL,
    status_aktif  TINYINT(1)          NOT NULL DEFAULT 1,
    foto          VARCHAR(255)        NULL,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- tb_kendaraan
-- Dipakai: member/kendaraan.php, member/booking.php
-- =====================================================
DROP TABLE IF EXISTS tb_kendaraan;
CREATE TABLE tb_kendaraan (
    id_kendaraan     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user          INT UNSIGNED    NOT NULL,
    plat_nomor       VARCHAR(20)     NOT NULL UNIQUE,
    jenis_kendaraan  VARCHAR(50)     NOT NULL,     -- cth: Motor, Mobil
    warna            VARCHAR(30)     NULL,
    pemilik          VARCHAR(150)    NULL,
    created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kendaraan_user FOREIGN KEY (id_user)
        REFERENCES tb_user (id_user) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- tb_tarif
-- Dipakai: admin/kelola_tarif.php, operator/transaksi_masuk.php,
--          operator/cetak_struk.php
-- =====================================================
DROP TABLE IF EXISTS tb_tarif;
CREATE TABLE tb_tarif (
    id_tarif         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jenis_kendaraan  VARCHAR(50)     NOT NULL,
    tarif_per_jam    DECIMAL(10,2)   NOT NULL DEFAULT 0,
    denda_per_jam    DECIMAL(10,2)   NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- =====================================================
-- tb_area_parkir
-- Dipakai: admin/kelola_area.php, member/booking.php,
--          member/cek_slot_area.php, operator/index.php
-- =====================================================
DROP TABLE IF EXISTS tb_area_parkir;
CREATE TABLE tb_area_parkir (
    id_area     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_area   VARCHAR(100)    NOT NULL,
    kapasitas   INT UNSIGNED    NOT NULL DEFAULT 0,
    terisi      INT UNSIGNED    NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- =====================================================
-- tb_booking
-- Dipakai: member/booking.php, member/cek_status_booking.php,
--          operator/kelola_booking.php, operator/transaksi_masuk.php,
--          operator/cek_booking_baru.php
-- =====================================================
DROP TABLE IF EXISTS tb_booking;
CREATE TABLE tb_booking (
    id_booking          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user             INT UNSIGNED    NOT NULL,
    id_kendaraan        INT UNSIGNED    NOT NULL,
    id_area             INT UNSIGNED    NOT NULL,
    tanggal_booking      DATE           NOT NULL,
    jam_booking_masuk    TIME           NOT NULL,
    jam_booking_keluar   TIME           NULL,
    catatan              VARCHAR(255)   NULL,
    status               ENUM('menunggu','dikonfirmasi','dibatalkan','selesai') NOT NULL DEFAULT 'menunggu',
    created_at           TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_user FOREIGN KEY (id_user)
        REFERENCES tb_user (id_user) ON DELETE CASCADE,
    CONSTRAINT fk_booking_kendaraan FOREIGN KEY (id_kendaraan)
        REFERENCES tb_kendaraan (id_kendaraan) ON DELETE CASCADE,
    CONSTRAINT fk_booking_area FOREIGN KEY (id_area)
        REFERENCES tb_area_parkir (id_area) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- tb_transaksi
-- Dipakai: operator/transaksi_masuk.php, operator/transaksi_keluar.php,
--          operator/cetak_struk.php, operator/riwayat_transaksi.php,
--          admin/index.php, owner/index.php, owner/rekap_transaksi.php
-- =====================================================
DROP TABLE IF EXISTS tb_transaksi;
CREATE TABLE tb_transaksi (
    id_parkir            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kendaraan         INT UNSIGNED    NOT NULL,
    id_booking           INT UNSIGNED    NULL,     -- nullable: transaksi walk-in tanpa booking
    id_tarif             INT UNSIGNED    NOT NULL,
    id_area              INT UNSIGNED    NOT NULL,
    id_user              INT UNSIGNED    NOT NULL, -- petugas yang memproses
    waktu_masuk          DATETIME        NOT NULL,
    waktu_keluar         DATETIME        NULL,
    durasi_jam           DECIMAL(6,2)    NULL,
    denda_telat_masuk    DECIMAL(10,2)   NOT NULL DEFAULT 0,
    denda_telat_keluar   DECIMAL(10,2)   NOT NULL DEFAULT 0,
    biaya_total          DECIMAL(10,2)   NULL,
    metode_bayar         VARCHAR(20)     NULL,     -- tunai / qris / dll
    status                ENUM('masuk','keluar') NOT NULL DEFAULT 'masuk',
    CONSTRAINT fk_transaksi_kendaraan FOREIGN KEY (id_kendaraan)
        REFERENCES tb_kendaraan (id_kendaraan) ON DELETE CASCADE,
    CONSTRAINT fk_transaksi_booking FOREIGN KEY (id_booking)
        REFERENCES tb_booking (id_booking) ON DELETE SET NULL,
    CONSTRAINT fk_transaksi_tarif FOREIGN KEY (id_tarif)
        REFERENCES tb_tarif (id_tarif) ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_area FOREIGN KEY (id_area)
        REFERENCES tb_area_parkir (id_area) ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_user FOREIGN KEY (id_user)
        REFERENCES tb_user (id_user) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- tb_log_aktivitas
-- Dipakai: config/koneksi.php (catatLog), admin/log_aktivitas.php
-- =====================================================
DROP TABLE IF EXISTS tb_log_aktivitas;
CREATE TABLE tb_log_aktivitas (
    id_log            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user           INT UNSIGNED    NOT NULL,
    aktivitas         VARCHAR(255)    NOT NULL,
    waktu_aktivitas   DATETIME        NOT NULL,
    CONSTRAINT fk_log_user FOREIGN KEY (id_user)
        REFERENCES tb_user (id_user) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- testimoni
-- Dipakai: index.php (landing), testimoni_submit.php,
--          admin/testimoni.php, admin/testimoni_action.php
-- =====================================================
DROP TABLE IF EXISTS testimoni;
CREATE TABLE testimoni (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100)    NOT NULL,
    role        VARCHAR(50)     NOT NULL DEFAULT 'Pengguna',
    rating      TINYINT UNSIGNED NOT NULL,
    komentar    VARCHAR(1000)   NOT NULL,
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- SEEDER — akun awal
-- =====================================================
-- PENTING soal password: sandbox tempat file ini dibuat tidak
-- punya PHP/bcrypt terpasang, jadi hash di bawah TIDAK bisa saya
-- generate & verifikasi sendiri di sini. Daripada menempel hash
-- karangan dan bilang "sudah diverifikasi" (yang sebelumnya
-- terbukti salah), silakan generate sendiri sebelum import:
--
--   php -r "echo password_hash('12345', PASSWORD_BCRYPT), PHP_EOL;"
--
-- lalu tempel hasilnya menggantikan '<GENERATE_DENGAN_PERINTAH_DI_ATAS>'
-- di 3 baris INSERT tb_user di bawah ini. login.php memakai
-- password_verify() standar, jadi hash apa pun dari password_hash()
-- PHP akan otomatis cocok.

INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif) VALUES
('Administrator', 'admin',    '<GENERATE_DENGAN_PERINTAH_DI_ATAS>', 'admin',   1),
('Petugas Satu',  'petugas1', '<GENERATE_DENGAN_PERINTAH_DI_ATAS>', 'petugas', 1),
('Owner Satu',    'owner1',   '<GENERATE_DENGAN_PERINTAH_DI_ATAS>', 'owner',   1);

-- Data awal tarif & area supaya aplikasi bisa langsung dites
INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam, denda_per_jam) VALUES
('Motor', 2000.00, 1000.00),
('Mobil', 5000.00, 2500.00);

INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi) VALUES
('Area Depan', 50, 0),
('Area Belakang', 30, 0);
