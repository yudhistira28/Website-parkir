<?php
require_once __DIR__ . '/../config/koneksi.php';
cekLogin(['owner']);
$page_title = 'Rekap Transaksi';

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

$stmt = $koneksi->prepare("
    SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.pemilik, a.nama_area, u.nama_lengkap AS petugas
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
    JOIN tb_area_parkir a ON a.id_area = t.id_area
    JOIN tb_user u ON u.id_user = t.id_user
    WHERE t.status = 'keluar' AND DATE(t.waktu_keluar) BETWEEN ? AND ?
    ORDER BY t.waktu_keluar DESC
");
$stmt->execute([$dari, $sampai]);
$rekap = $stmt->fetchAll();

$totalPendapatan = array_sum(array_column($rekap, 'biaya_total'));
$totalTransaksi = count($rekap);
$totalTunai = array_sum(array_column(array_filter($rekap, fn($r) => $r['metode_bayar'] === 'tunai'), 'biaya_total'));
$totalQris = array_sum(array_column(array_filter($rekap, fn($r) => $r['metode_bayar'] === 'qris'), 'biaya_total'));

include __DIR__ . '/components/header.php';
?>

<style>
.rekap-page { color: rgba(255,255,255,.85); }

.rekap-page .card-tirta {
    background: #101b3d !important;
    border: 1px solid rgba(255,255,255,.06) !important;
    border-radius: 18px !important;
    box-shadow: 0 12px 30px rgba(0,0,0,.35) !important;
    overflow: hidden;
}
.rekap-page .card-tirta .card-header {
    background: #0c1530 !important;
    border-bottom: 1px solid rgba(255,255,255,.07) !important;
    color: #fff !important;
    font-weight: 600;
    letter-spacing: .2px;
    padding: 14px 20px;
}
.rekap-page .card-tirta .card-body { padding: 20px; }

.rekap-page .form-label { color: rgba(255,255,255,.55) !important; margin-bottom: 6px; }
.rekap-page .form-control {
    background: #0b1330 !important;
    border: 1px solid rgba(255,255,255,.12) !important;
    color: #fff !important;
    border-radius: 10px !important;
    color-scheme: dark;
}
.rekap-page .form-control:focus {
    background: #0b1330 !important;
    border-color: #2fe6c8 !important;
    color: #fff !important;
    box-shadow: 0 0 0 .2rem rgba(47,230,200,.15) !important;
}
.rekap-page .btn-outline-secondary {
    background: transparent !important;
    border: 1px solid rgba(255,255,255,.18) !important;
    color: rgba(255,255,255,.85) !important;
    border-radius: 10px !important;
}
.rekap-page .btn-outline-secondary:hover {
    background: rgba(255,255,255,.08) !important;
    color: #fff !important;
}
.rekap-page .btn-tirta { border-radius: 10px !important; }

.rekap-page .stat-card { border-radius: 18px !important; }

.rekap-page .table {
    color: rgba(255,255,255,.85) !important;
    margin-bottom: 0;
}
.rekap-page .table thead th {
    background: #0c1530 !important;
    color: rgba(255,255,255,.55) !important;
    text-transform: uppercase;
    font-size: .72rem;
    letter-spacing: .06em;
    font-weight: 600;
    border-bottom: 1px solid rgba(255,255,255,.08) !important;
    padding: 12px 16px;
    white-space: nowrap;
}
/* FIX: sebelumnya <td> di sini tidak diberi `color` sendiri, sehingga
   rule default Bootstrap (`.table > :not(caption) > * > *`, warna teks
   gelap) menang berdasarkan specificity dan membuat isi tabel Rekap
   Transaksi terlihat hitam/nyaris tak terlihat di atas background navy. */
.rekap-page .table tbody td {
    border-bottom: 1px solid rgba(255,255,255,.06) !important;
    padding: 12px 16px;
    background: transparent !important;
    color: rgba(255,255,255,.85) !important;
}
.rekap-page .table tbody td.fw-semibold {
    color: #ffffff !important;
}
.rekap-page .table tbody tr:hover td { background: rgba(255,255,255,.03) !important; }
.rekap-page .table tbody tr:last-child td { border-bottom: none !important; }
.rekap-page .table tfoot td {
    background: #0c1530 !important;
    border-top: 1px solid rgba(255,255,255,.1) !important;
    padding: 14px 16px;
    color: #fff !important;
}
.rekap-page .badge.bg-success { background: #1f7a5c !important; }
.rekap-page .badge.bg-dark { background: #16224a !important; border: 1px solid rgba(255,255,255,.1); }
.rekap-page .text-muted { color: rgba(255,255,255,.45) !important; }
</style>

<div class="rekap-page">

<div class="card card-tirta mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" name="dari" class="form-control" value="<?= htmlspecialchars($dari) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" name="sampai" class="form-control" value="<?= htmlspecialchars($sampai) ?>">
            </div>
            <div class="col-md-3">
                <button class="btn btn-tirta w-100"><i class="bi bi-funnel me-1"></i> Tampilkan Rekap</button>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-outline-secondary w-100" onclick="cetakStruk()"><i class="bi bi-printer me-1"></i> Cetak Rekap</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="stat-card bg-grad-green">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <h3><?= rupiah($totalPendapatan) ?></h3>
            <span class="label">Total Pendapatan Periode Ini</span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card bg-grad-dark">
            <div class="stat-icon"><i class="bi bi-receipt"></i></div>
            <h3><?= $totalTransaksi ?></h3>
            <span class="label">Total Transaksi Periode Ini</span>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="stat-card bg-grad-green">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <h3><?= rupiah($totalTunai) ?></h3>
            <span class="label">Pendapatan Tunai</span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card bg-grad-dark">
            <div class="stat-icon"><i class="bi bi-qr-code"></i></div>
            <h3><?= rupiah($totalQris) ?></h3>
            <span class="label">Pendapatan QRIS</span>
        </div>
    </div>
</div>

<div class="card card-tirta">
    <div class="card-header">
        Rekap Transaksi <?= date('d/m/Y', strtotime($dari)) ?> &ndash; <?= date('d/m/Y', strtotime($sampai)) ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>#</th><th>Plat Nomor</th><th>Jenis</th><th>Pemilik</th><th>Area</th><th>Petugas</th><th>Masuk</th><th>Keluar</th><th>Durasi</th><th>Metode</th><th>Biaya</th></tr></thead>
            <tbody>
            <?php foreach ($rekap as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['plat_nomor']) ?></td>
                    <td class="text-capitalize"><?= $r['jenis_kendaraan'] ?></td>
                    <td><?= htmlspecialchars($r['pemilik']) ?></td>
                    <td><?= htmlspecialchars($r['nama_area']) ?></td>
                    <td><?= htmlspecialchars($r['petugas']) ?></td>
                    <td><?= date('d/m/y H:i', strtotime($r['waktu_masuk'])) ?></td>
                    <td><?= date('d/m/y H:i', strtotime($r['waktu_keluar'])) ?></td>
                    <td><?= $r['durasi_jam'] ?> jam</td>
                    <td><span class="badge <?= $r['metode_bayar'] === 'qris' ? 'bg-dark' : 'bg-success' ?>"><?= strtoupper($r['metode_bayar']) ?></span></td>
                    <td class="fw-semibold"><?= rupiah($r['biaya_total']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rekap)): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">Tidak ada transaksi pada periode ini</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($rekap)): ?>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="10" class="text-end">TOTAL PENDAPATAN</td>
                    <td><?= rupiah($totalPendapatan) ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
        </div>
    </div>
</div>

</div><!-- /.rekap-page -->

<?php include __DIR__ . '/components/footer.php'; ?>