<?php
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], ['petugas', 'admin'])) {
    http_response_code(403);
    exit;
}

header('Content-Type: application/json');

// 10 notifikasi terbaru (dibaca maupun belum) untuk ditampilkan di dropdown lonceng
$stmtList = $koneksi->query(
    "SELECT id_notifikasi, id_booking, pesan, dibaca, waktu_notifikasi
     FROM tb_notifikasi
     WHERE untuk_role = 'operator'
     ORDER BY id_notifikasi DESC
     LIMIT 10"
);
$daftar = $stmtList->fetchAll();

$stmtJumlah = $koneksi->query(
    "SELECT COUNT(*) AS jumlah FROM tb_notifikasi WHERE untuk_role = 'operator' AND dibaca = 0"
);
$jumlahBelumDibaca = (int) $stmtJumlah->fetch()['jumlah'];

echo json_encode([
    'jumlah_belum_dibaca' => $jumlahBelumDibaca,
    'daftar' => $daftar,
]);
