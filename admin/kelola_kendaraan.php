<?php
require_once __DIR__ . '/../config/koneksi.php';
cekLogin(['admin']);
$page_title = 'Data Kendaraan';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['aksi'] === 'tambah') {
    $stmt = $koneksi->prepare("INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([strtoupper($_POST['plat_nomor']), $_POST['jenis_kendaraan'], $_POST['warna'], $_POST['pemilik'], $_SESSION['id_user']]);
    catatLog($koneksi, $_SESSION['id_user'], "Menambahkan data kendaraan {$_POST['plat_nomor']}");
    header("Location: kelola_kendaraan.php?sukses=Kendaraan berhasil ditambahkan&aksi=tambah"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['aksi'] === 'edit') {
    $stmt = $koneksi->prepare("UPDATE tb_kendaraan SET plat_nomor=?, jenis_kendaraan=?, warna=?, pemilik=? WHERE id_kendaraan=?");
    $stmt->execute([strtoupper($_POST['plat_nomor']), $_POST['jenis_kendaraan'], $_POST['warna'], $_POST['pemilik'], $_POST['id_kendaraan']]);
    catatLog($koneksi, $_SESSION['id_user'], "Mengubah data kendaraan ID {$_POST['id_kendaraan']}");
    header("Location: kelola_kendaraan.php?sukses=Data kendaraan berhasil diperbarui&aksi=ubah"); exit;
}

if (isset($_GET['hapus'])) {
    try {
        $stmt = $koneksi->prepare("DELETE FROM tb_kendaraan WHERE id_kendaraan = ?");
        $stmt->execute([$_GET['hapus']]);
        catatLog($koneksi, $_SESSION['id_user'], "Menghapus data kendaraan ID {$_GET['hapus']}");
        header("Location: kelola_kendaraan.php?sukses=" . urlencode('Data kendaraan berhasil dihapus') . "&aksi=hapus");
        exit;
    } catch (PDOException $e) {
        // Kendaraan masih punya riwayat booking terkait (foreign key constraint) -> tidak boleh dihapus
        if ($e->getCode() === '23000') {
            header("Location: kelola_kendaraan.php?gagal=" . urlencode('Kendaraan tidak bisa dihapus karena masih memiliki riwayat booking. Hapus/arsipkan dulu riwayat bookingnya, atau nonaktifkan kendaraan ini.'));
            exit;
        }
        throw $e;
    }
}

$cari = trim($_GET['cari'] ?? '');
if ($cari !== '') {
    $stmt = $koneksi->prepare("SELECT * FROM tb_kendaraan WHERE plat_nomor LIKE ? OR pemilik LIKE ? ORDER BY id_kendaraan DESC");
    $stmt->execute(["%$cari%", "%$cari%"]);
    $kendaraans = $stmt->fetchAll();
} else {
    $kendaraans = $koneksi->query("SELECT * FROM tb_kendaraan ORDER BY id_kendaraan DESC LIMIT 100")->fetchAll();
}

include __DIR__ . '/template/header.php';
?>

<div class="card card-tirta">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Data Kendaraan Member / Tamu</span>
        <button class="btn btn-tirta btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg"></i> Tambah Kendaraan</button>
    </div>
    <div class="card-body">
        <?php if (!empty($_GET['sukses'])): ?>
            <?php
                $aksiSuara = $_GET['aksi'] ?? '';
                $jenisSuaraValid = in_array($aksiSuara, ['tambah', 'ubah', 'hapus'], true) ? $aksiSuara : 'ubah';
            ?>
            <div class="alert alert-success py-2" data-sound="<?= $jenisSuaraValid ?>"><?= htmlspecialchars($_GET['sukses']) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['gagal'])): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($_GET['gagal']) ?></div>
        <?php endif; ?>
        <form method="GET" class="mb-3">
            <div class="input-group" style="max-width:350px;">
                <input type="text" name="cari" class="form-control" placeholder="Cari plat nomor / pemilik..." value="<?= htmlspecialchars($cari) ?>">
                <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
            </div>
        </form>
        <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>#</th><th>Plat Nomor</th><th>Jenis</th><th>Warna</th><th>Pemilik</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($kendaraans as $i => $k): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($k['plat_nomor']) ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($k['jenis_kendaraan']) ?></td>
                    <td><?= htmlspecialchars($k['warna']) ?></td>
                    <td><?= htmlspecialchars($k['pemilik']) ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $k['id_kendaraan'] ?>"><i class="bi bi-pencil"></i></button>
                        <a href="?hapus=<?= $k['id_kendaraan'] ?>" class="btn btn-sm btn-outline-danger btn-hapus-konfirmasi"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($kendaraans)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Data tidak ditemukan</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!--
    PERBAIKAN: modal Edit dipindahkan ke luar <table> (sebelumnya berada
    di dalam foreach yang masih ada di dalam <tbody>, sehingga <div class="modal">
    jadi anak langsung <tbody> -- HTML tidak valid. Browser "membuang" div itu
    keluar dari tabel dengan cara tak terduga (foster parenting), membuat modal
    tembus/transparan dan elemen di baliknya tetap bisa diklik.
-->
<?php foreach ($kendaraans as $k): ?>
                <div class="modal fade" id="modalEdit<?= $k['id_kendaraan'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" class="modal-content">
                            <input type="hidden" name="aksi" value="edit">
                            <input type="hidden" name="id_kendaraan" value="<?= $k['id_kendaraan'] ?>">
                            <div class="modal-header"><h5 class="modal-title">Edit Kendaraan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <div class="mb-3"><label class="form-label">Plat Nomor</label>
                                    <input type="text" name="plat_nomor" class="form-control text-uppercase" value="<?= htmlspecialchars($k['plat_nomor']) ?>" required></div>
                                <div class="mb-3"><label class="form-label">Jenis Kendaraan</label>
                                    <select name="jenis_kendaraan" class="form-select" required>
                                        <option value="motor" <?= $k['jenis_kendaraan']==='motor'?'selected':'' ?>>Motor</option>
                                        <option value="mobil" <?= $k['jenis_kendaraan']==='mobil'?'selected':'' ?>>Mobil</option>
                                        <option value="lainnya" <?= $k['jenis_kendaraan']==='lainnya'?'selected':'' ?>>Lainnya</option>
                                    </select></div>
                                <div class="mb-3"><label class="form-label">Warna</label>
                                    <input type="text" name="warna" class="form-control" value="<?= htmlspecialchars($k['warna']) ?>"></div>
                                <div class="mb-3"><label class="form-label">Nama Pemilik</label>
                                    <input type="text" name="pemilik" class="form-control" value="<?= htmlspecialchars($k['pemilik']) ?>" required></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-tirta">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
<?php endforeach; ?>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="aksi" value="tambah">
            <div class="modal-header"><h5 class="modal-title">Tambah Kendaraan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Plat Nomor</label>
                    <input type="text" name="plat_nomor" class="form-control text-uppercase" required></div>
                <div class="mb-3"><label class="form-label">Jenis Kendaraan</label>
                    <select name="jenis_kendaraan" class="form-select" required>
                        <option value="motor">Motor</option>
                        <option value="mobil">Mobil</option>
                        <option value="truk/bus">Truk/Bus</option>
                    </select></div>
                <div class="mb-3"><label class="form-label">Warna</label>
                    <input type="text" name="warna" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Nama Pemilik</label>
                    <input type="text" name="pemilik" class="form-control" placeholder="Nama member/tamu" required></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-tirta">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/template/footer.php'; ?>