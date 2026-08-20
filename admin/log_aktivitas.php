<?php
require_once __DIR__ . '/../config/koneksi.php';
cekLogin(['admin']);
$page_title = 'Log Aktivitas';

// ==== HANDLE HAPUS ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    if ($_POST['aksi'] === 'hapus_satu' && !empty($_POST['id_log'])) {
        $stmt = $koneksi->prepare("DELETE FROM tb_log_aktivitas WHERE id_log = ?");
        $stmt->execute([$_POST['id_log']]);
        $_SESSION['flash_sukses'] = 'Log aktivitas berhasil dihapus.';

    } elseif ($_POST['aksi'] === 'hapus_semua') {
        // hapus sesuai filter tanggal aktif; kalau tanggal kosong, hapus SEMUA log
        $tglHapus = $_POST['tanggal'] ?? '';
        if ($tglHapus !== '') {
            $stmt = $koneksi->prepare("DELETE FROM tb_log_aktivitas WHERE DATE(waktu_aktivitas) = ?");
            $stmt->execute([$tglHapus]);
            $_SESSION['flash_sukses'] = 'Log aktivitas tanggal ' . $tglHapus . ' berhasil dihapus.';
        } else {
            $koneksi->exec("DELETE FROM tb_log_aktivitas");
            $_SESSION['flash_sukses'] = 'Seluruh log aktivitas berhasil dihapus.';
        }
    }

    $qs = !empty($_POST['tanggal']) ? ('?tanggal=' . urlencode($_POST['tanggal'])) : '';
    header('Location: log_aktivitas.php' . $qs);
    exit;
}

$tanggal = $_GET['tanggal'] ?? '';
$sql = "SELECT l.*, u.nama_lengkap, u.role FROM tb_log_aktivitas l JOIN tb_user u ON u.id_user = l.id_user";
$params = [];
if ($tanggal !== '') {
    $sql .= " WHERE DATE(l.waktu_aktivitas) = ?";
    $params[] = $tanggal;
}
$sql .= " ORDER BY l.id_log DESC LIMIT 200";
$stmt = $koneksi->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

include __DIR__ . '/template/header.php';
?>

<div class="card card-tirta">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Log Aktivitas Pengguna</span>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" class="d-flex gap-2">
                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= htmlspecialchars($tanggal) ?>">
                <button class="btn btn-sm btn-tirta">Filter</button>
                <?php if ($tanggal !== ''): ?><a href="log_aktivitas.php" class="btn btn-sm btn-outline-secondary">Reset</a><?php endif; ?>
            </form>

            <?php if (!empty($logs)): ?>
            <form method="POST" onsubmit="return confirm('<?= $tanggal !== ''
                ? 'Hapus semua log pada tanggal ' . htmlspecialchars($tanggal) . '?'
                : 'Yakin ingin menghapus SEMUA log aktivitas? Tindakan ini tidak dapat dibatalkan.' ?>');">
                <input type="hidden" name="aksi" value="hapus_semua">
                <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
                <button class="btn btn-sm btn-danger">
                    <?= $tanggal !== '' ? 'Hapus Log Tanggal Ini' : 'Hapus Semua Log' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_sukses'])): ?>
        <div class="alert alert-success m-3 mb-0" data-sound="hapus"><?= htmlspecialchars($_SESSION['flash_sukses']) ?></div>
        <?php unset($_SESSION['flash_sukses']); ?>
    <?php endif; ?>

    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>#</th><th>Waktu</th><th>Nama</th><th>Role</th><th>Aktivitas</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $i => $l): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= date('d/m/Y H:i:s', strtotime($l['waktu_aktivitas'])) ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($l['nama_lengkap']) ?></td>
                    <td><span class="badge bg-secondary text-uppercase"><?= $l['role'] ?></span></td>
                    <td><?= htmlspecialchars($l['aktivitas']) ?></td>
                    <td class="text-end">
                        <form method="POST" onsubmit="return confirm('Hapus log ini?');">
                            <input type="hidden" name="aksi" value="hapus_satu">
                            <input type="hidden" name="id_log" value="<?= $l['id_log'] ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada log aktivitas</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/template/footer.php'; ?>