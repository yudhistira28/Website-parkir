<?php
require_once __DIR__ . '/../config/koneksi.php';
cekLogin(['petugas']);

$id = $_GET['id'] ?? 0;
$mode = $_GET['mode'] ?? 'masuk';

$stmt = $koneksi->prepare("
    SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.pemilik, k.warna, a.nama_area, u.nama_lengkap AS petugas, tf.tarif_per_jam,
           bk.jam_booking_masuk AS booking_jam_masuk, bk.jam_booking_keluar AS booking_jam_keluar
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
    JOIN tb_area_parkir a ON a.id_area = t.id_area
    JOIN tb_user u ON u.id_user = t.id_user
    JOIN tb_tarif tf ON tf.id_tarif = t.id_tarif
    LEFT JOIN tb_booking bk ON bk.id_booking = t.id_booking
    WHERE t.id_parkir = ?
");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    header("Location: index.php?gagal=Transaksi tidak ditemukan");
    exit;
}

$page_title = 'Cetak Struk';
include __DIR__ . '/components/header.php';
?>
<script>
// Halaman ini hanya muncul kalau transaksi masuk/keluar berhasil diproses.
// mode=keluar berarti pembayaran baru saja berhasil -> suara khusus pembayaran.
// mode=masuk (kendaraan baru masuk) -> tetap pakai suara generik.
document.addEventListener('DOMContentLoaded', function () {
    const mode = <?= json_encode($mode) ?>;
    if (mode === 'keluar' && typeof mainkanSuaraPembayaranBerhasil === 'function') {
        mainkanSuaraPembayaranBerhasil();
    } else if (typeof mainkanSuaraBenar === 'function') {
        mainkanSuaraBenar();
    }
});
function cetakStruk() { window.print(); }
</script>

<style>
    /* ===== Tampilan tiket/struk gaya thermal parkir asli ===== */
    .struk-wrap {
        display: flex;
        justify-content: center;
        padding: 20px 12px 60px;
    }
    .struk-box {
        position: relative;
        width: 320px;
        max-width: 100%;
        background: #fff;
        color: #1a1a1a;
        font-family: 'Courier New', 'IBM Plex Mono', monospace;
        font-size: 13px;
        line-height: 1.5;
        padding: 24px 18px 18px;
        box-shadow: 0 4px 18px rgba(0,0,0,.12);
    }
    /* efek tepi gerigi kertas thermal (hanya di tepi atas & bawah, tidak menimpa konten) */
    .struk-box::before,
    .struk-box::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        height: 10px;
        background-image: radial-gradient(circle at 8px 8px, #eef2f7 7px, transparent 7.5px);
        background-size: 16px 16px;
        background-repeat: repeat-x;
    }
    .struk-box::before {
        top: -1px;
        background-position: 0 -3px;
    }
    .struk-box::after {
        bottom: -1px;
        background-position: 0 3px;
    }

    .struk-box .logo-parkir {
        width: 50px;
        height: 50px;
        margin: 0 auto 8px;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
    }
    .struk-box .logo-parkir img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .struk-box .logo-parkir .logo-fallback {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: linear-gradient(135deg, #2fe6c8, #8b7cfa);
        color: #fff;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .struk-box .toko-nama {
        text-align: center;
        font-size: 17px;
        font-weight: 700;
        letter-spacing: 1px;
        margin: 0 0 2px;
    }
    .struk-box .toko-alamat {
        text-align: center;
        font-size: 11.5px;
        color: #444;
        margin: 0 0 6px;
    }
    .struk-box .sub-badge {
        display: block;
        text-align: center;
        width: fit-content;
        margin: 0 auto 4px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        color: #1ba893;
        border: 1.5px solid #1ba893;
        border-radius: 999px;
        padding: 3px 14px;
    }
    .struk-box .sub-badge.bayar { color: #16a34a; border-color: #16a34a; }

    .struk-box .nomor-tiket {
        text-align: center;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: 4px;
        margin: 10px 0 2px;
    }
    .struk-box .nomor-tiket small {
        display: block;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 2px;
        color: #777;
        margin-bottom: 2px;
    }

    .struk-box .garis {
        border: none;
        border-top: 1px dashed #999;
        margin: 10px 0;
    }
    .struk-box .row-line {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin: 3px 0;
    }
    .struk-box .row-line span:first-child {
        color: #333;
        flex-shrink: 0;
    }
    .struk-box .row-line span:last-child {
        text-align: right;
        font-weight: 600;
        word-break: break-word;
    }
    .struk-box .row-line.small {
        font-size: 11px;
    }
    .struk-box .total-line {
        display: flex;
        justify-content: space-between;
        font-size: 15px;
        font-weight: 800;
        margin: 6px 0 2px;
    }
    .struk-box .footer-note {
        text-align: center;
        font-size: 11px;
        color: #555;
        margin: 2px 0;
    }
    .struk-box .status-chip {
        display: block;
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .5px;
        padding: 6px 0;
        margin: 10px 0 4px;
        border: 1px dashed #999;
        background: #fffbe8;
    }

    /* Stempel LUNAS khusus nota pembayaran (mode keluar) */
    .struk-box .stempel-lunas {
        position: absolute;
        top: 60px;
        right: 14px;
        border: 3px solid #16a34a;
        color: #16a34a;
        font-weight: 800;
        font-size: 15px;
        letter-spacing: 2px;
        padding: 4px 10px;
        border-radius: 6px;
        transform: rotate(-14deg);
        opacity: .75;
        pointer-events: none;
    }

    /* Blok rincian biaya bergaya nota parkir umum */
    .struk-box .nota-judul {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #555;
        text-align: center;
        margin: 10px 0 6px;
    }
    .struk-box .nota-rincian {
        border-top: 1.5px solid #1a1a1a;
        border-bottom: 1.5px solid #1a1a1a;
        padding: 6px 0;
        margin-bottom: 6px;
    }
    .struk-box .total-box {
        background: #f4faf6;
        border: 1.5px solid #16a34a;
        border-radius: 6px;
        padding: 8px 12px;
        margin: 8px 0 4px;
    }
    .struk-box .total-box .total-line { margin: 0; color: #14532d; }

    /* Barcode palsu (representasi visual, bukan barcode terbaca) */
    .struk-box .barcode {
        height: 42px;
        margin: 10px 0 4px;
        background-image: repeating-linear-gradient(
            90deg,
            #1a1a1a 0px, #1a1a1a 2px,
            transparent 2px, transparent 3px,
            #1a1a1a 3px, #1a1a1a 4px,
            transparent 4px, transparent 6px,
            #1a1a1a 6px, #1a1a1a 9px,
            transparent 9px, transparent 11px
        );
        background-size: 100% 100%;
    }
    .struk-box .barcode-code {
        text-align: center;
        font-size: 11px;
        letter-spacing: 3px;
        color: #333;
        margin-top: 2px;
    }

    /* ===== Mode cetak: hilangkan semua kecuali struk, ukuran kertas thermal ===== */
    @media print {
        body * { visibility: hidden; }
        .struk-wrap, .struk-wrap * { visibility: visible; }
        .struk-wrap {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            padding: 0;
        }
        .struk-box {
            width: 80mm;
            max-width: 80mm;
            box-shadow: none;
            padding: 6mm 4mm;
            font-size: 12px;
        }
        .no-print { display: none !important; }
        @page { margin: 0; size: 80mm auto; }
    }
</style>

<div class="text-center mb-3 no-print">
    <button class="btn btn-tirta" onclick="cetakStruk()"><i class="bi bi-printer me-1"></i> Cetak Struk</button>
    <a href="<?= $mode === 'masuk' ? 'index.php' : 'transaksi_keluar.php' ?>" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="struk-wrap">
<div class="struk-box">
    <?php if ($trx['status'] === 'keluar'): ?>
        <div class="stempel-lunas">LUNAS</div>
    <?php endif; ?>

    <div class="logo-parkir">
        <img src="<?= BASE_URL ?>img/kolam.jpg" alt="Logo Tirta Tamansari"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <span class="logo-fallback"><i class="bi bi-p-circle-fill"></i></span>
    </div>
    <p class="toko-nama">TIRTA TAMANSARI</p>
    <p class="toko-alamat">Kolam &amp; Pool Parking System</p>
    <span class="sub-badge <?= $mode === 'masuk' ? '' : 'bayar' ?>"><?= $mode === 'masuk' ? 'TIKET MASUK' : 'NOTA PEMBAYARAN PARKIR' ?></span>

    <?php if ($mode === 'masuk'): ?>
        <p class="nomor-tiket">
            <small>NO. TIKET</small>
            #<?= str_pad($trx['id_parkir'], 6, '0', STR_PAD_LEFT) ?>
        </p>
    <?php else: ?>
        <div class="row-line small" style="margin-top:10px;"><span>No. Transaksi</span><span>#<?= str_pad($trx['id_parkir'], 6, '0', STR_PAD_LEFT) ?></span></div>
    <?php endif; ?>

    <hr class="garis">

    <div class="row-line"><span>Plat Nomor</span><span><?= htmlspecialchars($trx['plat_nomor']) ?></span></div>
    <div class="row-line"><span>Jenis</span><span class="text-capitalize"><?= $trx['jenis_kendaraan'] ?></span></div>
    <div class="row-line"><span>Pemilik</span><span><?= htmlspecialchars($trx['pemilik']) ?></span></div>
    <div class="row-line"><span>Area</span><span><?= htmlspecialchars($trx['nama_area']) ?></span></div>
    <div class="row-line"><span>Petugas</span><span><?= htmlspecialchars($trx['petugas']) ?></span></div>

    <hr class="garis">

    <div class="row-line"><span>Waktu Masuk</span><span><?= date('d/m/Y H:i', strtotime($trx['waktu_masuk'])) ?></span></div>
    <?php if ($trx['booking_jam_masuk']): ?>
        <div class="row-line small"><span>Jadwal Booking Masuk</span><span><?= date('H:i', strtotime($trx['booking_jam_masuk'])) ?></span></div>
    <?php endif; ?>
    <?php if ((float) $trx['denda_telat_masuk'] > 0): ?>
        <div class="row-line"><span>Denda Telat Masuk</span><span><?= rupiah($trx['denda_telat_masuk']) ?></span></div>
    <?php endif; ?>

    <?php if ($trx['status'] === 'keluar'): ?>
        <div class="row-line"><span>Waktu Keluar</span><span><?= date('d/m/Y H:i', strtotime($trx['waktu_keluar'])) ?></span></div>
        <?php if ($trx['booking_jam_keluar']): ?>
            <div class="row-line small"><span>Jadwal Booking Keluar</span><span><?= date('H:i', strtotime($trx['booking_jam_keluar'])) ?></span></div>
        <?php endif; ?>
        <div class="row-line"><span>Durasi</span><span><?= $trx['durasi_jam'] ?> jam</span></div>

        <p class="nota-judul">— RINCIAN BIAYA —</p>
        <div class="nota-rincian">
            <div class="row-line"><span>Tarif/Jam</span><span><?= rupiah($trx['tarif_per_jam']) ?></span></div>
            <div class="row-line"><span>Biaya Parkir (<?= $trx['durasi_jam'] ?> jam)</span><span><?= rupiah($trx['durasi_jam'] * $trx['tarif_per_jam']) ?></span></div>
            <?php if ((float) $trx['denda_telat_keluar'] > 0): ?>
                <div class="row-line"><span>Denda Telat Keluar</span><span><?= rupiah($trx['denda_telat_keluar']) ?></span></div>
            <?php endif; ?>
            <div class="row-line"><span>Metode Bayar</span><span><?= strtoupper($trx['metode_bayar'] ?? 'TUNAI') ?></span></div>
        </div>

        <div class="total-box">
            <div class="total-line"><span>TOTAL BAYAR</span><span><?= rupiah($trx['biaya_total']) ?></span></div>
        </div>
    <?php else: ?>
        <span class="status-chip">SIMPAN TIKET INI UNTUK PROSES KELUAR</span>
    <?php endif; ?>

    <div class="barcode"></div>
    <p class="barcode-code">*<?= str_pad($trx['id_parkir'], 10, '0', STR_PAD_LEFT) ?>*</p>

    <hr class="garis">
    <p class="footer-note">Terima kasih telah menggunakan<br>layanan parkir Tirta Tamansari!</p>
</div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>