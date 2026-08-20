<?php
require_once __DIR__ . '/../config/koneksi.php';
cekLogin(['petugas']);
$page_title = 'Kendaraan Masuk';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plat = strtoupper(trim($_POST['plat_nomor']));
    $jenis = $_POST['jenis_kendaraan'] ?? '';
    $warna = trim($_POST['warna']);
    $pemilik = !empty(trim($_POST['pemilik'])) ? trim($_POST['pemilik']) : 'TAMU';
    $id_area = $_POST['id_area'];
    $id_tarif = $_POST['id_tarif'];
    $id_booking = $_POST['id_booking'] ?? '';

    // Cek kapasitas area
    $stmtArea = $koneksi->prepare("SELECT * FROM tb_area_parkir WHERE id_area = ?");
    $stmtArea->execute([$id_area]);
    $area = $stmtArea->fetch();

    if (!$area || $area['terisi'] >= $area['kapasitas']) {
        $error = 'Area parkir yang dipilih sudah penuh. Silakan pilih area lain.';
    } else {
        $koneksi->beginTransaction();
        try {
            $denda_telat_masuk = 0;

            if ($id_booking !== '') {
                $stmtBooking = $koneksi->prepare(
                    "SELECT b.id_kendaraan, b.tanggal_booking, b.jam_booking_masuk FROM tb_booking b WHERE b.id_booking = ? AND b.status = 'dikonfirmasi'"
                );
                $stmtBooking->execute([$id_booking]);
                $booking = $stmtBooking->fetch();

                if (!$booking) {
                    throw new Exception('Booking tidak ditemukan atau sudah diproses.');
                }
                $id_kendaraan = $booking['id_kendaraan'];

                $jadwalMasuk = $booking['tanggal_booking'] . ' ' . $booking['jam_booking_masuk'];
                $menitTelatMasuk = hitungMenitTelat($jadwalMasuk, date('Y-m-d H:i:s'));

                $stmtTarifDenda = $koneksi->prepare("SELECT denda_per_jam FROM tb_tarif WHERE id_tarif = ?");
                $stmtTarifDenda->execute([$id_tarif]);
                $dendaPerJam = (float) ($stmtTarifDenda->fetch()['denda_per_jam'] ?? 0);

                $denda_telat_masuk = hitungDenda($menitTelatMasuk, $dendaPerJam);
            } else {
                $cekPlat = $koneksi->prepare("SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = ?");
                $cekPlat->execute([$plat]);
                $existing = $cekPlat->fetch();

                if ($existing) {
                    $id_kendaraan = $existing['id_kendaraan'];
                } else {
                    $stmtK = $koneksi->prepare("INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES (?, ?, ?, ?, ?)");
                    $stmtK->execute([$plat, $jenis, $warna, $pemilik, $_SESSION['id_user']]);
                    $id_kendaraan = $koneksi->lastInsertId();
                }
            }

            // Simpan transaksi
            $stmtT = $koneksi->prepare("INSERT INTO tb_transaksi (id_kendaraan, id_booking, waktu_masuk, id_tarif, denda_telat_masuk, status, id_user, id_area) VALUES (?, ?, NOW(), ?, ?, 'masuk', ?, ?)");
            $stmtT->execute([$id_kendaraan, $id_booking !== '' ? $id_booking : null, $id_tarif, $denda_telat_masuk, $_SESSION['id_user'], $id_area]);
            $id_parkir = $koneksi->lastInsertId();

            // Update area terisi
            $stmtUp = $koneksi->prepare("UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = ?");
            $stmtUp->execute([$id_area]);

            if ($id_booking !== '') {
                $stmtSelesai = $koneksi->prepare("UPDATE tb_booking SET status = 'selesai' WHERE id_booking = ?");
                $stmtSelesai->execute([$id_booking]);
            }

            catatLog($koneksi, $_SESSION['id_user'], "Mencatat kendaraan masuk: $plat" . ($id_booking !== '' ? " (dari booking #$id_booking)" : '') . ($denda_telat_masuk > 0 ? " - kena denda telat masuk " . rupiah($denda_telat_masuk) : ''));

            $koneksi->commit();
            header("Location: cetak_struk.php?id=$id_parkir&mode=masuk");
            exit;
        } catch (Exception $e) {
            $koneksi->rollBack();
            $error = 'Gagal menyimpan transaksi: ' . $e->getMessage();
        }
    }
}

$areas = $koneksi->query("SELECT *, (kapasitas - terisi) AS sisa_slot FROM tb_area_parkir WHERE terisi < kapasitas ORDER BY sisa_slot DESC, nama_area ASC")->fetchAll();
$tarifs = $koneksi->query("SELECT * FROM tb_tarif ORDER BY jenis_kendaraan")->fetchAll();

$bookingSiap = $koneksi->query("
    SELECT b.id_booking, b.status, b.tanggal_booking, b.jam_booking_masuk, b.jam_booking_keluar, b.catatan, b.id_area,
           k.id_kendaraan, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
           u.nama_lengkap, a.nama_area
    FROM tb_booking b
    JOIN tb_kendaraan k ON k.id_kendaraan = b.id_kendaraan
    JOIN tb_user u ON u.id_user = b.id_user
    LEFT JOIN tb_area_parkir a ON a.id_area = b.id_area
    WHERE b.status IN ('menunggu', 'dikonfirmasi')
    ORDER BY FIELD(b.status, 'dikonfirmasi', 'menunggu'), b.tanggal_booking ASC, b.jam_booking_masuk ASC
")->fetchAll();

$petaDenda = [];
foreach ($tarifs as $t) { $petaDenda[strtolower($t['jenis_kendaraan'])] = (float) $t['denda_per_jam']; }

include __DIR__ . '/components/header.php';
?>

<style>
    .field-otomatis {
        background-color: rgba(255,255,255,.03) !important;
        color: rgba(255,255,255,.6) !important;
        cursor: default;
    }
    .field-otomatis:focus { box-shadow: none; }
    .label-otomatis-badge {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .3px;
        color: #2fe6c8;
        background: rgba(47,230,200,.15);
        border: 1px solid rgba(47,230,200,.3);
        border-radius: 999px;
        padding: 2px 8px;
        margin-left: 6px;
        vertical-align: middle;
        text-transform: uppercase;
    }
</style>

<div class="row justify-content-center g-3">

    <?php if (count($bookingSiap) > 0): ?>
    <div class="col-lg-9">
        <div class="card card-tirta border-primary">
            <div class="card-header bg-primary-subtle d-flex align-items-center justify-content-between">
                <span><i class="bi bi-calendar-check text-primary"></i> Booking Member — Menunggu &amp; Siap Diproses Masuk</span>
                <span class="badge bg-primary"><?= count($bookingSiap) ?> booking</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Kendaraan</th>
                            <th>Area</th>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Keluar</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bookingSiap as $b): ?>
                        <?php
                            $menitTelat = hitungMenitTelat($b['tanggal_booking'] . ' ' . $b['jam_booking_masuk'], date('Y-m-d H:i:s'));
                            $dendaEstimasi = hitungDenda($menitTelat, $petaDenda[strtolower($b['jenis_kendaraan'])] ?? 0);
                        ?>
                        <tr id="row-booking-<?= $b['id_booking'] ?>">
                            <td><?= htmlspecialchars($b['nama_lengkap']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($b['plat_nomor']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($b['jenis_kendaraan']) ?><?= $b['warna'] ? ' - ' . htmlspecialchars($b['warna']) : '' ?></small>
                            </td>
                            <td>
                                <?php if ($b['nama_area']): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis"><?= htmlspecialchars($b['nama_area']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($b['tanggal_booking'])) ?></td>
                            <td><?= date('H:i', strtotime($b['jam_booking_masuk'])) ?></td>
                            <td><?= $b['jam_booking_keluar'] ? date('H:i', strtotime($b['jam_booking_keluar'])) : '<span class="text-muted small">-</span>' ?></td>
                            <td>
                                <?php if ($b['status'] === 'menunggu'): ?>
                                    <span class="badge bg-warning text-dark">Menunggu konfirmasi</span><br>
                                <?php else: ?>
                                    <span class="badge bg-primary">Dikonfirmasi</span><br>
                                <?php endif; ?>
                                <?php if ($dendaEstimasi > 0): ?>
                                    <span class="badge bg-danger">Telat <?= $menitTelat ?> menit</span><br>
                                    <small class="text-danger">Denda est. <?= rupiah($dendaEstimasi) ?></small>
                                <?php else: ?>
                                    <span class="badge bg-success">Tepat waktu</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($b['status'] === 'menunggu'): ?>
                                    <a href="kelola_booking.php" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-hourglass-split"></i> Konfirmasi dulu
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-tirta btn-proses-booking"
                                            data-id-booking="<?= $b['id_booking'] ?>"
                                            data-plat="<?= htmlspecialchars($b['plat_nomor']) ?>"
                                            data-jenis="<?= htmlspecialchars($b['jenis_kendaraan']) ?>"
                                            data-warna="<?= htmlspecialchars($b['warna'] ?? '') ?>"
                                            data-pemilik="<?= htmlspecialchars($b['pemilik']) ?>"
                                            data-id-area="<?= $b['id_area'] ?? '' ?>"
                                            data-denda="<?= $dendaEstimasi ?>">
                                        <i class="bi bi-arrow-down-circle"></i> Proses
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-lg-7">
        <div class="card card-tirta">
            <div class="card-header"><i class="bi bi-box-arrow-in-right me-1"></i> Form Kendaraan Masuk</div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <div id="infoBooking" class="alert alert-primary py-2 d-none">
                    <i class="bi bi-info-circle"></i> Data diisi dari booking member.
                    <span id="infoDendaMasuk"></span>
                    <button type="button" id="btnBatalBooking" class="btn btn-sm btn-outline-primary float-end">Batal, input manual</button>
                </div>

                <form method="POST">
                    <input type="hidden" name="id_booking" id="id_booking" value="">
                    <input type="hidden" name="pemilik" id="pemilik" value="TAMU">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Plat Nomor</label>
                            <input type="text" name="plat_nomor" id="plat_nomor" class="form-control text-uppercase" placeholder="Contoh: AB 1234 CD" required autofocus>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jenis Kendaraan</label>
                            <select name="jenis_kendaraan" id="jenis_kendaraan_select" class="form-select" required>
                                <option value="">-- Pilih Jenis --</option>
                                <?php foreach ($tarifs as $t): ?>
                                    <option value="<?= $t['jenis_kendaraan'] ?>" data-tarif-id="<?= $t['id_tarif'] ?>" data-tarif="<?= $t['tarif_per_jam'] ?>"><?= ucfirst($t['jenis_kendaraan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Warna Kendaraan <span class="label-otomatis-badge">Default terpilih</span></label>
                            <select name="warna" id="warna" class="form-select">
                                <option value="Hitam" selected>Hitam</option>
                                <option value="Putih">Putih</option>
                                <option value="Perak/Abu">Perak / Abu-Abu</option>
                                <option value="Merah">Merah</option>
                                <option value="Biru">Biru</option>
                                <option value="Kuning/Emas">Kuning / Emas</option>
                                <option value="Hijau">Hijau</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Area Parkir <span class="label-otomatis-badge">Otomatis</span></label>
                            <select name="id_area" id="id_area_select" class="form-select" required>
                                <?php foreach ($areas as $i => $a): ?>
                                    <option value="<?= $a['id_area'] ?>" <?= $i === 0 ? 'selected' : '' ?>><?= htmlspecialchars($a['nama_area']) ?> (sisa <?= $a['sisa_slot'] ?> slot)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tarif <span class="label-otomatis-badge">Otomatis</span></label>
                            <select name="id_tarif" id="id_tarif_select" class="form-select field-otomatis" required>
                                <option value="">-- Pilih Jenis Kendaraan dulu --</option>
                                <?php foreach ($tarifs as $t): ?>
                                    <option value="<?= $t['id_tarif'] ?>"><?= ucfirst($t['jenis_kendaraan']) ?> - <?= rupiah($t['tarif_per_jam']) ?>/jam</option>
                                <?php endforeach; ?>
                            </select>
                            <small id="info_tarif" class="text-success fw-semibold"></small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-tirta w-100 mt-4 py-2"><i class="bi bi-check-circle me-1"></i> Simpan &amp; Cetak Struk Masuk</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let activeBookingId = null;

document.getElementById('jenis_kendaraan_select').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const tarifId = opt.getAttribute('data-tarif-id');
    const tarifVal = opt.getAttribute('data-tarif');
    const info = document.getElementById('info_tarif');

    info.textContent = tarifVal ? '✓ Terisi otomatis: Rp ' + Number(tarifVal).toLocaleString('id-ID') + '/jam' : '';

    if (tarifId) {
        document.getElementById('id_tarif_select').value = tarifId;
    }
});

document.getElementById('plat_nomor').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

document.querySelectorAll('.btn-proses-booking').forEach(function (btn) {
    btn.addEventListener('click', function () {
        activeBookingId = btn.dataset.idBooking;
        document.getElementById('id_booking').value = activeBookingId;
        document.getElementById('plat_nomor').value = btn.dataset.plat;

        const warnaInput = btn.dataset.warna || 'Hitam';
        const warnaSelect = document.getElementById('warna');
        let optionExists = Array.from(warnaSelect.options).some(o => o.value.toLowerCase() === warnaInput.toLowerCase());
        if (!optionExists) {
            warnaSelect.add(new Option(warnaInput, warnaInput, true, true));
        } else {
            warnaSelect.value = warnaInput;
        }

        document.getElementById('pemilik').value = btn.dataset.pemilik;

        const denda = parseInt(btn.dataset.denda || '0', 10);
        const infoDenda = document.getElementById('infoDendaMasuk');
        infoDenda.innerHTML = denda > 0 ? ' <strong class="text-danger">Kendaraan ini terlambat, denda est. Rp ' + denda.toLocaleString('id-ID') + '.</strong>' : '';

        const areaSelect = document.getElementById('id_area_select');
        const idArea = btn.dataset.idArea;
        if (idArea && Array.from(areaSelect.options).some(o => o.value === idArea)) {
            areaSelect.value = idArea;
        }

        const jenisSelect = document.getElementById('jenis_kendaraan_select');
        for (const opt of jenisSelect.options) {
            if (opt.value.toLowerCase() === btn.dataset.jenis.toLowerCase()) {
                opt.selected = true;
                jenisSelect.value = opt.value;
                jenisSelect.dispatchEvent(new Event('change'));
            }
        }

        document.getElementById('plat_nomor').readOnly = true;
        jenisSelect.disabled = true;

        document.getElementById('infoBooking').classList.remove('d-none');
        document.getElementById('plat_nomor').closest('.card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

document.getElementById('btnBatalBooking').addEventListener('click', function () {
    activeBookingId = null;
    document.getElementById('id_booking').value = '';
    document.getElementById('plat_nomor').readOnly = false;
    document.getElementById('jenis_kendaraan_select').disabled = false;

    document.getElementById('plat_nomor').value = '';
    document.getElementById('warna').value = 'Hitam';
    document.getElementById('pemilik').value = 'TAMU';
    document.getElementById('jenis_kendaraan_select').value = '';
    document.getElementById('id_tarif_select').value = '';
    document.getElementById('info_tarif').textContent = '';
    document.getElementById('infoDendaMasuk').innerHTML = '';
    document.getElementById('infoBooking').classList.add('d-none');
});
</script>