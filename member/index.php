<?php
require_once __DIR__ . '/../config/koneksi.php';

// Hanya boleh diakses oleh yang sudah login dan berrole member
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'member') {
    header("Location: " . BASE_URL . "auth/login.php?err=akses_ditolak");
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil foto profil terbaru langsung dari database (biar selalu sinkron)
$stmtFoto = $koneksi->prepare("SELECT foto FROM tb_user WHERE id_user = ?");
$stmtFoto->execute([$id_user]);
$fotoProfil = $stmtFoto->fetch()['foto'] ?? null;
$fotoProfilNav = $fotoProfil; // Diteruskan ke navbar

// Ambil daftar kendaraan milik member ini
$stmtKendaraan = $koneksi->prepare("SELECT * FROM tb_kendaraan WHERE id_user = ? ORDER BY id_kendaraan DESC");
$stmtKendaraan->execute([$id_user]);
$daftarKendaraan = $stmtKendaraan->fetchAll();

// Ambil riwayat parkir terbaru (join ke kendaraan milik member ini)
$stmtRiwayat = $koneksi->prepare(
    "SELECT t.*, k.plat_nomor, k.jenis_kendaraan
     FROM tb_transaksi t
     JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
     WHERE k.id_user = ?
     ORDER BY t.waktu_masuk DESC
     LIMIT 10"
);
$stmtRiwayat->execute([$id_user]);
$riwayat = $stmtRiwayat->fetchAll();

// Cek apakah ada kendaraan yang sedang parkir (status masuk, belum keluar)
$stmtAktif = $koneksi->prepare(
    "SELECT t.*, k.plat_nomor, k.jenis_kendaraan
     FROM tb_transaksi t
     JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
     WHERE k.id_user = ? AND t.status = 'masuk'
     ORDER BY t.waktu_masuk DESC"
);
$stmtAktif->execute([$id_user]);
$parkirAktif = $stmtAktif->fetchAll();

// Total riwayat parkir (untuk statistik)
$stmtTotal = $koneksi->prepare(
    "SELECT COUNT(*) AS total FROM tb_transaksi t
     JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
     WHERE k.id_user = ?"
);
$stmtTotal->execute([$id_user]);
$totalParkir = $stmtTotal->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Member - Parkir Tirta Tamansari</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

<?php 
if (function_exists('tampilkanNotifikasiLogin')) {
    tampilkanNotifikasiLogin(); 
}
?>

<?php include __DIR__ . '/template/navbar_member.php'; ?>

<div class="container py-4">

    <h4 class="mb-1">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?> 👋</h4>
    <p class="text-muted mb-4">Berikut ringkasan aktivitas parkir kendaraan anda.</p>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-car-front-fill text-primary fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Kendaraan Terdaftar</div>
                        <div class="fs-4 fw-bold"><?= count($daftarKendaraan) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-clock-history text-success fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Riwayat Parkir</div>
                        <div class="fs-4 fw-bold"><?= (int)$totalParkir ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-p-square-fill text-warning fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Sedang Parkir</div>
                        <div class="fs-4 fw-bold"><?= count($parkirAktif) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($parkirAktif) > 0): ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-p-square text-warning"></i> Kendaraan Sedang Parkir
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Plat Nomor</th>
                            <th>Jenis Kendaraan</th>
                            <th>Waktu Masuk</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parkirAktif as $p): ?>
                        <tr>
                            <td><span class="fw-semibold"><?= htmlspecialchars($p['plat_nomor']) ?></span></td>
                            <td><?= htmlspecialchars($p['jenis_kendaraan']) ?></td>
                            <td><?= date('d M Y, H:i', strtotime($p['waktu_masuk'])) ?></td>
                            <td><span class="badge bg-warning text-dark">Sedang Parkir</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-car-front"></i> Kendaraan Saya</span>
                    <a href="<?= BASE_URL ?>member/kendaraan.php" class="text-decoration-none small">Kelola</a>
                </div>
                <div class="card-body p-0">
                    <?php if (count($daftarKendaraan) === 0): ?>
                        <p class="text-muted text-center py-4 mb-0">Belum ada kendaraan terdaftar.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($daftarKendaraan as $k): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($k['plat_nomor']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($k['jenis_kendaraan']) ?></small>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-clock-history"></i> Riwayat Parkir Terbaru
                </div>
                <div class="card-body p-0">
                    <?php if (count($riwayat) === 0): ?>
                        <p class="text-muted text-center py-4 mb-0">Belum ada riwayat parkir.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Plat Nomor</th>
                                        <th>Masuk</th>
                                        <th>Keluar</th>
                                        <th>Biaya</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($riwayat as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['plat_nomor']) ?></td>
                                        <td><?= date('d M Y, H:i', strtotime($r['waktu_masuk'])) ?></td>
                                        <td>
                                            <?= $r['waktu_keluar'] ? date('d M Y, H:i', strtotime($r['waktu_keluar'])) : '-' ?>
                                        </td>
                                        <td>
                                            <?= !empty($r['biaya_total']) ? 'Rp ' . number_format($r['biaya_total'], 0, ',', '.') : '-' ?>
                                        </td>
                                        <td>
                                            <?php if ($r['status'] === 'masuk'): ?>
                                                <span class="badge bg-warning text-dark">Sedang Parkir</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.APP_BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>assets/js/sound-effect.js"></script>
</body>
</html>