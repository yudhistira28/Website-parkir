<?php
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], ['petugas', 'admin'])) {
    http_response_code(403);
    exit;
}

header('Content-Type: application/json');

// Tandai semua notifikasi operator sebagai sudah dibaca (dipanggil saat lonceng notifikasi dibuka)
$koneksi->exec("UPDATE tb_notifikasi SET dibaca = 1 WHERE untuk_role = 'operator' AND dibaca = 0");

echo json_encode(['sukses' => true]);
