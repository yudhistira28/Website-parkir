<?php
require_once __DIR__ . '/../config/koneksi.php';
cekLogin(['admin']);

$page_title = 'Dashboard';

// ===== Query Data Statistik =====
$totalKendaraan    = $koneksi->query("SELECT COUNT(*) c FROM tb_kendaraan")->fetch()['c'];
$totalUser         = $koneksi->query("SELECT COUNT(*) c FROM tb_user")->fetch()['c'];
$kendaraanMasuk    = $koneksi->query("SELECT COUNT(*) c FROM tb_transaksi WHERE status='masuk'")->fetch()['c'];
$pendapatanHariIni = $koneksi->query("SELECT COALESCE(SUM(biaya_total),0) t FROM tb_transaksi WHERE status='keluar' AND DATE(waktu_keluar) = CURDATE()")->fetch()['t'];

$area = $koneksi->query("SELECT * FROM tb_area_parkir ORDER BY id_area")->fetchAll();

$transaksiTerbaru = $koneksi->query("
    SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.pemilik, u.nama_lengkap AS petugas
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
    JOIN tb_user u ON u.id_user = t.id_user
    ORDER BY t.id_parkir DESC LIMIT 8
")->fetchAll();

// ===== Data Grafik Pendapatan =====
$stmtGrafik = $koneksi->prepare("
    SELECT DATE(waktu_keluar) AS tanggal, COALESCE(SUM(biaya_total),0) AS total
    FROM tb_transaksi
    WHERE status = 'keluar' AND waktu_keluar >= (CURDATE() - INTERVAL 6 DAY)
    GROUP BY DATE(waktu_keluar)
");
$stmtGrafik->execute();
$hasilPendapatan = $stmtGrafik->fetchAll(PDO::FETCH_KEY_PAIR);

$labelGrafik     = [];
$dataPendapatan  = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl               = date('Y-m-d', strtotime("-$i day"));
    $labelGrafik[]     = date('d/m', strtotime($tgl));
    $dataPendapatan[]  = isset($hasilPendapatan[$tgl]) ? (float)$hasilPendapatan[$tgl] : 0;
}

// ===== Data Grafik Kendaraan Masuk =====
$stmtGrafik2 = $koneksi->prepare("
    SELECT DATE(waktu_masuk) AS tanggal, COUNT(*) AS jumlah
    FROM tb_transaksi
    WHERE waktu_masuk >= (CURDATE() - INTERVAL 6 DAY)
    GROUP BY DATE(waktu_masuk)
");
$stmtGrafik2->execute();
$hasilKendaraan = $stmtGrafik2->fetchAll(PDO::FETCH_KEY_PAIR);

$dataKendaraan = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl             = date('Y-m-d', strtotime("-$i day"));
    $dataKendaraan[] = isset($hasilKendaraan[$tgl]) ? (int)$hasilKendaraan[$tgl] : 0;
}

// Fungsi bantu format rupiah jika fungsi rupiah() belum terdefinisi global
function formatRupiah($angka) {
    if (function_exists('rupiah')) {
        return rupiah($angka);
    }
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

include __DIR__ . '/template/header.php'; 
?>

<!-- Overwrite CSS untuk merapatkan jarak yang kosong di atas + ANTI-KEPOTONG
     untuk kartu statistik, tabel, dan grafik di layar sempit (HP / window kecil).
     Rule layout sidebar (.app-wrapper, .main-content, margin-left, dsb) TIDAK
     disentuh di sini — itu sudah jadi tanggung jawab tunggal sidebar_admin.php. -->
<style>
    .tp-topbar {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
        margin-bottom: 0 !important;
    }
    .tp-body {
        padding-top: 1.1rem !important;
    }

    /* ===== ANTI-KEPOTONG: kartu statistik ===== */
    /* min-width:0 wajib di semua flex/grid item Bootstrap, karena default-nya
       min-width:auto yang bikin konten (angka rupiah panjang) memaksa lebar
       kartu melebihi kolomnya lalu terpotong oleh overflow parent. */
    .stat-tile {
        min-width: 0;
    }
    .stat-tile .stat-num {
        word-break: break-word;
        overflow-wrap: break-word;
        white-space: normal;
        line-height: 1.15;
    }
    /* Angka rupiah di kartu "Pendapatan Hari Ini" pakai ukuran fleksibel
       (clamp) supaya otomatis mengecil di layar sempit alih-alih terpotong. */
    .stat-tile .stat-num.stat-num-currency {
        font-size: clamp(1.05rem, 4.5vw, 1.4rem) !important;
    }

    /* Di layar sangat sempit (<576px), turunkan dari 2 kolom jadi 1 kolom
       penuh supaya tiap kartu tetap punya ruang cukup — sebelumnya col-6
       memaksa 2 kartu berdampingan walau layarnya cuma ~320-380px. */
    @media (max-width: 420px) {
        .stat-tile-col {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
    }

    /* ===== ANTI-KEPOTONG: tabel transaksi ===== */
    /* Pastikan scroll horizontal benar-benar aktif dan tidak ada elemen
       leluhur yang diam-diam memotongnya lewat overflow:hidden. */
    .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    .table-responsive table {
        min-width: 640px; /* biar kolom tabel tidak saling desak-desakan sampai terpotong */
    }

    /* ===== ANTI-KEPOTONG: grafik ===== */
    /* min-width:0 di kolom pembungkus canvas, alasan sama seperti stat-tile:
       tanpa ini, grid Bootstrap bisa mempertahankan lebar minimum bawaan
       canvas dan mendorong konten lain sampai terpotong / horizontal-scroll
       tak terduga di layar sempit. */
    .col-lg-7, .col-lg-5 {
        min-width: 0;
    }

    /* ===== Safety net umum ===== */
    /* Cegah body punya scrollbar horizontal tak terduga akibat elemen anak
       yang melebar tanpa sengaja (mis. canvas Chart.js sebelum resize). */
    .tp-body {
        overflow-x: hidden;
    }
</style>

<div class="mb-4">
    <span class="eyebrow">Ringkasan hari ini</span>
</div>

<!-- Kartu Statistik -->
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6 stat-tile-col">
        <div class="stat-tile accent-aqua">
            <div class="stat-icon"><i class="bi bi-car-front"></i></div>
            <div class="stat-num"><?= $totalKendaraan ?></div>
            <span class="stat-label">Total Kendaraan Terdaftar</span>
        </div>
    </div>
    <div class="col-md-3 col-6 stat-tile-col">
        <div class="stat-tile accent-violet">
            <div class="stat-icon"><i class="bi bi-p-circle"></i></div>
            <div class="stat-num"><?= $kendaraanMasuk ?></div>
            <span class="stat-label">Kendaraan di Area Parkir</span>
        </div>
    </div>
    <div class="col-md-3 col-6 stat-tile-col">
        <div class="stat-tile accent-brass">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-num"><?= $totalUser ?></div>
            <span class="stat-label">Total Akun Pengguna</span>
        </div>
    </div>
    <div class="col-md-3 col-6 stat-tile-col">
        <div class="stat-tile accent-aquadim">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-num stat-num-currency"><?= formatRupiah($pendapatanHariIni) ?></div>
            <span class="stat-label">Pendapatan Hari Ini</span>
        </div>
    </div>
</div>

<!-- Grafik -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card card-tirta h-100">
            <div class="card-header"><i class="bi bi-graph-up-arrow me-1" style="color:var(--aqua);"></i> Pendapatan 7 Hari Terakhir</div>
            <div class="card-body">
                <div style="position: relative; height: 280px;">
                    <canvas id="chartPendapatan"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-tirta h-100">
            <div class="card-header"><i class="bi bi-bar-chart-line me-1" style="color:var(--violet);"></i> Kendaraan Masuk 7 Hari Terakhir</div>
            <div class="card-body">
                <div style="position: relative; height: 280px;">
                    <canvas id="chartKendaraan"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Transaksi & Kapasitas Area -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-tirta h-100">
            <div class="card-header"><i class="bi bi-receipt me-1" style="color:var(--aqua);"></i> Transaksi Terbaru</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle table-hover">
                        <thead>
                            <tr>
                                <th>Plat Nomor</th>
                                <th>Jenis</th>
                                <th>Pemilik</th>
                                <th>Petugas</th>
                                <th>Masuk</th>
                                <th>Status</th>
                                <th>Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($transaksiTerbaru)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi</td></tr>
                        <?php else: ?>
                            <?php foreach ($transaksiTerbaru as $t): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($t['plat_nomor']) ?></td>
                                    <td><?= ucfirst($t['jenis_kendaraan']) ?></td>
                                    <td><?= htmlspecialchars($t['pemilik']) ?></td>
                                    <td><?= htmlspecialchars($t['petugas']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($t['waktu_masuk'])) ?></td>
                                    <td><span class="badge bg-secondary"><?= ucfirst($t['status']) ?></span></td>
                                    <td><?= $t['biaya_total'] ? formatRupiah($t['biaya_total']) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-tirta h-100">
            <div class="card-header"><i class="bi bi-p-square me-1" style="color:var(--brass);"></i> Kapasitas Area Parkir</div>
            <div class="card-body">
                <?php if (empty($area)): ?>
                    <p class="text-muted text-center py-3 mb-0">Data area parkir belum tersedia.</p>
                <?php else: ?>
                    <?php foreach ($area as $a):
                        $persen = $a['kapasitas'] > 0 ? round(($a['terisi'] / $a['kapasitas']) * 100) : 0;
                        $kelas  = $persen >= 90 ? 'full' : ($persen >= 60 ? 'warn' : 'ok');
                    ?>
                    <div class="lane-row">
                        <span class="lane-name"><?= htmlspecialchars($a['nama_area']) ?></span>
                        <div class="lane-track">
                            <div class="lane-fill <?= $kelas ?>" style="width: <?= $persen ?>%"></div>
                        </div>
                        <span class="lane-num"><?= $a['terisi'] ?> / <?= $a['kapasitas'] ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.color = 'rgba(255,255,255,.55)';
    Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

    let chartPendapatan, chartKendaraan;

    const ctxPendapatan = document.getElementById('chartPendapatan');
    if (ctxPendapatan) {
        const gradienPendapatan = ctxPendapatan.getContext('2d').createLinearGradient(0, 0, 0, 280);
        gradienPendapatan.addColorStop(0, 'rgba(47,230,200,.35)');
        gradienPendapatan.addColorStop(1, 'rgba(47,230,200,0)');

        chartPendapatan = new Chart(ctxPendapatan, {
            type: 'line',
            data: {
                labels: <?= json_encode($labelGrafik) ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?= json_encode($dataPendapatan) ?>,
                    borderColor: '#2fe6c8',
                    backgroundColor: gradienPendapatan,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointBackgroundColor: '#070b1a',
                    pointBorderColor: '#2fe6c8',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 400 },
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,.06)' },
                        ticks: {
                            callback: function (value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const ctxKendaraan = document.getElementById('chartKendaraan');
    if (ctxKendaraan) {
        chartKendaraan = new Chart(ctxKendaraan, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labelGrafik) ?>,
                datasets: [{
                    label: 'Kendaraan Masuk',
                    data: <?= json_encode($dataKendaraan) ?>,
                    backgroundColor: '#8b7cfa',
                    borderRadius: 6,
                    maxBarThickness: 36
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 400 },
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(255,255,255,.06)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // FIX grafik "salah ukur saat awal load": paksa resize begitu semua CSS,
    // font, dan layout (termasuk offset sidebar dari sidebar_admin.php) benar-benar
    // selesai dihitung browser — bukan cuma saat DOM siap.
    window.addEventListener('load', function () {
        if (chartPendapatan) chartPendapatan.resize();
        if (chartKendaraan) chartKendaraan.resize();
    });

    // Jika sidebar di-collapse/expand (lihat sidebar_admin.php), lebar area
    // grafik berubah tapi Chart.js tidak otomatis tahu — resize manual.
    document.body.addEventListener('transitionend', function (e) {
        if (e.propertyName === 'margin-left') {
            if (chartPendapatan) chartPendapatan.resize();
            if (chartKendaraan) chartKendaraan.resize();
        }
    });
</script>

<?php include __DIR__ . '/template/footer.php'; ?>