-- Jalankan SQL ini di database jika tabel tb_notifikasi BELUM ada.
-- Tabel ini dipakai untuk menyimpan notifikasi booking baru yang akan
-- ditampilkan di halaman operator.

CREATE TABLE IF NOT EXISTS tb_notifikasi (
    id_notifikasi     INT AUTO_INCREMENT PRIMARY KEY,
    untuk_role        VARCHAR(20) NOT NULL DEFAULT 'operator',
    id_booking        INT NOT NULL,
    pesan             VARCHAR(255) NOT NULL,
    dibaca            TINYINT(1) NOT NULL DEFAULT 0,
    waktu_notifikasi  DATETIME NOT NULL,
    FOREIGN KEY (id_booking) REFERENCES tb_booking(id_booking) ON DELETE CASCADE
);
