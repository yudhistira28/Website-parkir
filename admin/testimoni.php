<?php
require_once __DIR__ . '/../config/koneksi.php';
cekLogin(['admin']);
$page_title = 'Kelola Testimoni';

// Testimoni langsung tampil di landing page begitu dikirim (tidak perlu
// persetujuan admin). Halaman ini untuk memantau semua testimoni yang
// masuk, rating paling rendah ditampilkan lebih dulu supaya admin gampang
// menemukan rating jelek — bisa disembunyikan (Tolak) atau dihapus permanen.
$daftarTestimoni = [];
try {
    $daftarTestimoni = $koneksi->query(
        "SELECT id, nama, role, rating, komentar, status, created_at
         FROM testimoni
         ORDER BY rating ASC, created_at DESC"
    )->fetchAll();
} catch (PDOException $e) {
    $daftarTestimoni = [];
}

include __DIR__ . '/template/header.php';

function badgeRatingTestimoni(int $r): string {
    return $r <= 2 ? 'bg-danger' : ($r === 3 ? 'bg-warning' : 'bg-success');
}
?>

<div class="card card-tirta">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-chat-left-text me-2"></i>Semua Testimoni</span>
        <span class="text-muted small">Testimoni baru langsung tampil di landing page — kelola di sini jika perlu disembunyikan atau dihapus.</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($daftarTestimoni)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-chat-left-text fs-1 d-block mb-2"></i>
                Belum ada testimoni.
            </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Peran</th>
                    <th>Rating</th>
                    <th>Komentar</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftarTestimoni as $t): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($t['nama']) ?></td>
                        <td><?= htmlspecialchars($t['role']) ?></td>
                        <td>
                            <span class="badge <?= badgeRatingTestimoni((int)$t['rating']) ?>">
                                <?= (int)$t['rating'] ?> <i class="bi bi-star-fill"></i>
                            </span>
                        </td>
                        <td style="max-width:320px;"><?= htmlspecialchars($t['komentar']) ?></td>
                        <td>
                            <?php if ($t['status'] === 'approved'): ?>
                                <span class="badge bg-success">Tampil</span>
                            <?php elseif ($t['status'] === 'rejected'): ?>
                                <span class="badge bg-danger">Disembunyikan</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Menunggu</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap"><small class="text-muted"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></small></td>
                        <td class="text-end text-nowrap">
                            <?php if ($t['status'] === 'approved'): ?>
                                <form action="<?= BASE_URL ?>admin/testimoni_action.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                    <input type="hidden" name="aksi" value="reject">
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-eye-slash"></i> Sembunyikan
                                    </button>
                                </form>
                            <?php else: ?>
                                <form action="<?= BASE_URL ?>admin/testimoni_action.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                    <input type="hidden" name="aksi" value="approve">
                                    <button type="submit" class="btn btn-sm btn-tirta">
                                        <i class="bi bi-eye"></i> Tampilkan
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form action="<?= BASE_URL ?>admin/testimoni_action.php" method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus permanen testimoni dari ' + <?= json_encode($t['nama']) ?> + '?');">
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <input type="hidden" name="aksi" value="hapus">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/template/footer.php'; ?>
