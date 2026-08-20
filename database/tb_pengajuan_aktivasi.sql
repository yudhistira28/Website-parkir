-- =====================================================
-- tb_pengajuan_aktivasi
-- Jalankan SQL ini di database jika tabel BELUM ada.
-- Dipakai oleh: ajukan_aktivasi.php (halaman publik, tanpa login)
--               admin/pengajuan_aktivasi.php (approve/tolak oleh admin)
-- Alur: akun member/petugas dinonaktifkan admin -> user tidak bisa
-- login -> user mengisi form pengajuan aktivasi via halaman publik ->
-- admin dapat notifikasi -> admin setujui -> status_aktif di tb_user
-- diubah jadi 1 lagi.
--
-- CATATAN PERBAIKAN:
-- Versi sebelumnya gagal (errno 150) karena tipe data id_user di sini
-- (INT UNSIGNED) kemungkinan tidak sama persis dengan tipe kolom
-- id_user di tabel tb_user (biasanya INT biasa / signed).
-- Versi ini memakai INT biasa supaya cocok dengan pola umum tb_user
-- pada project UKK. Jika masih gagal, kirim hasil
-- `SHOW CREATE TABLE tb_user;` untuk penyesuaian tipe yang presisi.
-- =====================================================
CREATE TABLE IF NOT EXISTS tb_pengajuan_aktivasi (
    id_pengajuan     INT AUTO_INCREMENT PRIMARY KEY,
    id_user          INT             NOT NULL,
    alasan           VARCHAR(500)    NULL,
    status           ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
    catatan_admin    VARCHAR(500)    NULL,
    diproses_oleh    INT             NULL,
    waktu_pengajuan  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    waktu_diproses   DATETIME        NULL,
    CONSTRAINT fk_pengajuan_user FOREIGN KEY (id_user)
        REFERENCES tb_user (id_user) ON DELETE CASCADE,
    CONSTRAINT fk_pengajuan_admin FOREIGN KEY (diproses_oleh)
        REFERENCES tb_user (id_user) ON DELETE SET NULL
) ENGINE=InnoDB;
