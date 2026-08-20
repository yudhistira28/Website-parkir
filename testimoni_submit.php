<?php
require_once __DIR__ . '/config/koneksi.php';

// Siapapun boleh mengirim testimoni & rating — tidak perlu login.
// Setelah dikirim, testimoni langsung berstatus 'approved' sehingga
// otomatis tampil di landing page tanpa menunggu persetujuan admin.
// Testimoni tetap tercatat & bisa dipantau/dihapus admin lewat
// menu Kelola Testimoni (admin/testimoni.php).

$nama     = trim($_POST['nama'] ?? '');
$role     = trim($_POST['role'] ?? '');
$rating   = (int) ($_POST['rating'] ?? 0);
$komentar = trim($_POST['komentar'] ?? '');

// Validasi dasar: nama, rating (1-5), dan komentar wajib diisi
if ($nama === '' || $komentar === '' || $rating < 1 || $rating > 5) {
    header("Location: " . BASE_URL . "index.php?testimoni=gagal");
    exit;
}

if ($role === '') {
    $role = 'Pengunjung';
}

try {
    $stmt = $koneksi->prepare(
        "INSERT INTO testimoni (nama, role, rating, komentar, status, created_at)
         VALUES (:nama, :role, :rating, :komentar, 'approved', NOW())"
    );
    $stmt->execute([
        ':nama'     => $nama,
        ':role'     => $role,
        ':rating'   => $rating,
        ':komentar' => $komentar,
    ]);

    header("Location: " . BASE_URL . "index.php?testimoni=sukses#testimoni");
    exit;
} catch (PDOException $e) {
    header("Location: " . BASE_URL . "index.php?testimoni=gagal");
    exit;
}
