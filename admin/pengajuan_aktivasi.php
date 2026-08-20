<?php
require_once __DIR__ . '/../config/koneksi.php';
cekLogin(['admin']);
$page_title = 'Pengajuan Aktivasi Akun';

// ==== SETUJUI PENGAJUAN ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'setujui') {
    $id_pengajuan = (int) $_POST['id_pengajuan'];

    $stmt = $koneksi->prepare("SELECT * FROM tb_pengajuan_aktivasi WHERE id_pengajuan = ? LIMIT 1");
    $stmt->execute([$id_pengajuan]);
    $pengajuan = $stmt->fetch();

    if ($pengajuan && $pengajuan['status'] === 'menunggu') {
        $koneksi->beginTransaction();
        try {
            $koneksi->prepare("UPDATE tb_user SET status_aktif = 1 WHERE id_user = ?")
                    ->execute([$pengajuan['id_user']]);

            $koneksi->prepare(
                "UPDATE tb_pengajuan_aktivasi SET status = 'disetujui', diproses_oleh = ?, waktu_diproses = NOW() WHERE id_pengajuan = ?"
            )->execute([$_SESSION['id_user'], $id_pengajuan]);

            $koneksi->commit();
            catatLog($koneksi, $_SESSION['id_user'], "Menyetujui pengajuan aktivasi akun ID user {$pengajuan['id_user']}");
            header("Location: pengajuan_aktivasi.php?sukses=Pengajuan disetujui, akun kembali aktif&aksi=ubah");
            exit;
        } catch (Exception $e) {
            $koneksi->rollBack();
            header("Location: pengajuan_aktivasi.php?gagal=Gagal memproses pengajuan");
            exit;
        }
    } else {
        header("Location: pengajuan_aktivasi.php?gagal=Pengajuan tidak ditemukan atau sudah diproses");
        exit;
    }
}

// ==== TOLAK PENGAJUAN ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tolak') {
    $id_pengajuan  = (int) $_POST['id_pengajuan'];
    $catatan       = trim($_POST['catatan_admin'] ?? '');

    $stmt = $koneksi->prepare("SELECT * FROM tb_pengajuan_aktivasi WHERE id_pengajuan = ? LIMIT 1");
    $stmt->execute([$id_pengajuan]);
    $pengajuan = $stmt->fetch();

    if ($pengajuan && $pengajuan['status'] === 'menunggu') {
        $koneksi->prepare(
            "UPDATE tb_pengajuan_aktivasi SET status = 'ditolak', catatan_admin = ?, diproses_oleh = ?, waktu_diproses = NOW() WHERE id_pengajuan = ?"
        )->execute([$catatan !== '' ? $catatan : null, $_SESSION['id_user'], $id_pengajuan]);

        catatLog($koneksi, $_SESSION['id_user'], "Menolak pengajuan aktivasi akun ID user {$pengajuan['id_user']}");
        header("Location: pengajuan_aktivasi.php?sukses=Pengajuan ditolak&aksi=ubah");
        exit;
    } else {
        header("Location: pengajuan_aktivasi.php?gagal=Pengajuan tidak ditemukan atau sudah diproses");
        exit;
    }
}

// ==== DATA ====
$menunggu = $koneksi->query("
    SELECT p.*, u.nama_lengkap, u.username, u.role
    FROM tb_pengajuan_aktivasi p
    JOIN tb_user u ON u.id_user = p.id_user
    WHERE p.status = 'menunggu'
    ORDER BY p.waktu_pengajuan ASC
")->fetchAll();

$riwayat = $koneksi->query("
    SELECT p.*, u.nama_lengkap, u.username, u.role, a.nama_lengkap AS nama_admin
    FROM tb_pengajuan_aktivasi p
    JOIN tb_user u ON u.id_user = p.id_user
    LEFT JOIN tb_user a ON a.id_user = p.diproses_oleh
    WHERE p.status != 'menunggu'
    ORDER BY p.waktu_diproses DESC
    LIMIT 30
")->fetchAll();

include __DIR__ . '/template/header.php';
?>

<div class="card card-tirta mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-life-preserver me-2"></i>Pengajuan Menunggu Persetujuan</span>
        <?php if (count($menunggu) > 0): ?>
            <span class="badge bg-warning"><?= count($menunggu) ?> Menunggu</span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($menunggu)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
                Tidak ada pengajuan aktivasi yang menunggu saat ini.
            </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Alasan</th>
                    <th>Waktu Pengajuan</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menunggu as $i => $p): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($p['username']) ?></td>
                    <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($p['role']) ?></span></td>
                    <td style="max-width: 240px;">
                        <small><?= $p['alasan'] ? htmlspecialchars($p['alasan']) : '<span class="text-muted">-</span>' ?></small>
                    </td>
                    <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($p['waktu_pengajuan'])) ?></small></td>
                    <td class="text-end">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="aksi" value="setujui">
                            <input type="hidden" name="id_pengajuan" value="<?= $p['id_pengajuan'] ?>">
                            <button type="submit" class="btn btn-sm btn-tirta">
                                <i class="bi bi-check-lg"></i> Setujui
                            </button>
                        </form>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTolak<?= $p['id_pengajuan'] ?>">
                            <i class="bi bi-x-lg"></i> Tolak
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-tirta">
    <div class="card-header"><i class="bi bi-clock-history me-2"></i>Riwayat Pengajuan</div>
    <div class="card-body p-0">
        <?php if (empty($riwayat)): ?>
            <div class="text-center text-muted py-4">Belum ada riwayat pengajuan yang diproses.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Catatan Admin</th>
                    <th>Diproses Oleh</th>
                    <th>Waktu Diproses</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($riwayat as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($r['username']) ?></td>
                    <td>
                        <?php if ($r['status'] === 'disetujui'): ?>
                            <span class="badge bg-success">Disetujui</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Ditolak</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= $r['catatan_admin'] ? htmlspecialchars($r['catatan_admin']) : '<span class="text-muted">-</span>' ?></small></td>
                    <td><small class="text-muted"><?= htmlspecialchars($r['nama_admin'] ?? '-') ?></small></td>
                    <td><small class="text-muted"><?= $r['waktu_diproses'] ? date('d/m/Y H:i', strtotime($r['waktu_diproses'])) : '-' ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tolak (di luar table, mengikuti pola kelola_user.php) -->
<?php foreach ($menunggu as $p): ?>
<div class="modal fade" id="modalTolak<?= $p['id_pengajuan'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="aksi" value="tolak">
            <input type="hidden" name="id_pengajuan" value="<?= $p['id_pengajuan'] ?>">
            <div class="modal-header">
                <h5 class="modal-title">Tolak Pengajuan — <?= htmlspecialchars($p['nama_lengkap']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Catatan untuk pemohon (opsional)</label>
                    <textarea name="catatan_admin" class="form-control" rows="3" placeholder="Contoh: Hubungi admin langsung untuk verifikasi identitas"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-outline-danger">Tolak Pengajuan</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php include __DIR__ . '/template/footer.php'; ?>
