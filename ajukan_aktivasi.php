<?php
require_once __DIR__ . '/config/koneksi.php';

// Jika sudah login, tidak perlu mengajukan aktivasi
if (isset($_SESSION['id_user'])) {
    switch ($_SESSION['role']) {
        case 'admin': header("Location: " . BASE_URL . "admin/index.php"); exit;
        case 'petugas': header("Location: " . BASE_URL . "operator/index.php"); exit;
        case 'owner': header("Location: " . BASE_URL . "owner/index.php"); exit;
        case 'member': header("Location: " . BASE_URL . "member/index.php"); exit;
    }
}

$error   = '';
$sukses  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $alasan   = trim($_POST['alasan'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $koneksi->prepare("SELECT * FROM tb_user WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Username atau password salah.';
        } elseif ((int) $user['status_aktif'] === 1) {
            $error = 'Akun ini masih aktif. Silakan langsung login.';
        } else {
            // Cek apakah sudah ada pengajuan yang masih menunggu untuk akun ini
            $cekPengajuan = $koneksi->prepare(
                "SELECT id_pengajuan FROM tb_pengajuan_aktivasi WHERE id_user = ? AND status = 'menunggu' LIMIT 1"
            );
            $cekPengajuan->execute([$user['id_user']]);

            if ($cekPengajuan->fetch()) {
                $error = 'Anda sudah pernah mengajukan aktivasi dan pengajuan masih menunggu persetujuan admin. Mohon tunggu.';
            } else {
                $stmtInsert = $koneksi->prepare(
                    "INSERT INTO tb_pengajuan_aktivasi (id_user, alasan, status, waktu_pengajuan) VALUES (?, ?, 'menunggu', NOW())"
                );
                $stmtInsert->execute([$user['id_user'], $alasan !== '' ? $alasan : null]);

                catatLog($koneksi, $user['id_user'], 'Mengajukan aktivasi ulang akun (akun nonaktif)');

                $sukses = 'Pengajuan aktivasi berhasil dikirim. Admin akan meninjau dan menghubungkan status akun Anda setelah disetujui.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ajukan Aktivasi Akun - Parkir Tirta Tamansari</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>img/kolam.jpg">

    <style>
        :root {
            --ink: #060a16;
            --ink-2: #0d1730;
            --ink-3: #101c3c;
            --aqua: #2fe6c8;
            --aqua-dim: #1ba893;
            --violet: #8b7cfa;
            --brass: #f2b84b;
            --text-soft: rgba(255,255,255,.6);
            --glass-border: rgba(255,255,255,.09);
        }
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--ink);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
            position: relative;
            overflow: hidden;
        }
        h1, h3, .brand { font-family: 'Space Grotesk', system-ui, sans-serif; }

        .ambient-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 0;
            opacity: .55;
        }
        .ambient-orb.o1 {
            width: 480px; height: 480px;
            background: radial-gradient(circle, rgba(242,184,75,.45), transparent 70%);
            top: -180px; right: -120px;
            animation: floatOrb 14s ease-in-out infinite;
        }
        .ambient-orb.o2 {
            width: 420px; height: 420px;
            background: radial-gradient(circle, rgba(47,230,200,.4), transparent 70%);
            bottom: -160px; left: -140px;
            animation: floatOrb 16s ease-in-out infinite reverse;
        }
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -25px) scale(1.06); }
        }

        .auth-wrapper { width: 100%; max-width: 560px; position: relative; z-index: 1; animation: riseIn .6s cubic-bezier(.2,.8,.2,1); }
        @keyframes riseIn {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-card {
            background: linear-gradient(180deg, rgba(16,28,60,.7), rgba(13,23,48,.7));
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 40px 90px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.05);
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 34px), calc(100% - 34px) 100%, 0 100%);
            position: relative;
        }
        .auth-card::after {
            content: "";
            position: absolute;
            right: 0; bottom: 0;
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--brass), var(--violet));
            clip-path: polygon(100% 0, 100% 100%, 0 100%);
        }

        .auth-head {
            padding: 30px 34px 6px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .side-logo {
            width: 46px; height: 46px;
            border-radius: 14px;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: #fff;
            box-shadow: 0 8px 22px rgba(0,0,0,.35);
            flex-shrink: 0;
        }
        .side-logo img { width: 100%; height: 100%; object-fit: cover; }
        .side-logo .side-logo-fallback {
            width: 100%; height: 100%;
            display: none;
            align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--brass), var(--violet));
            color: var(--ink);
            font-weight: 800;
        }
        .badge-tag {
            display: inline-flex; align-items: center; gap: 8px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: .68rem; letter-spacing: 1.6px; text-transform: uppercase;
            color: var(--brass);
        }
        .badge-tag::before { content: ""; width: 18px; height: 1px; background: var(--brass); display: inline-block; }

        .auth-form { padding: 18px 34px 40px; }
        .auth-form h3 { font-size: 1.35rem; font-weight: 700; color: #fff; margin-bottom: 6px; }
        .auth-form .subtitle { color: var(--text-soft); font-size: .85rem; margin-bottom: 24px; line-height: 1.55; }

        .form-label { font-size: .78rem; font-weight: 600; color: rgba(255,255,255,.78); margin-bottom: 5px; }

        .input-wrap { position: relative; }
        .input-wrap i.field-icon {
            position: absolute;
            left: 14px; top: 18px;
            font-size: .95rem;
            color: rgba(255,255,255,.4);
            pointer-events: none;
        }
        textarea.form-control ~ .field-icon { top: 14px; }
        .form-control, textarea.form-control {
            border-radius: 12px;
            padding: 10px 14px 10px 38px;
            font-size: .875rem;
            border: 1.5px solid rgba(255,255,255,.12);
            background-color: rgba(255,255,255,.045);
            color: #fff;
            transition: all .2s ease;
        }
        .form-control::placeholder { color: rgba(255,255,255,.32); }
        .form-control:focus {
            background-color: rgba(255,255,255,.075);
            border-color: var(--brass);
            box-shadow: 0 0 0 4px rgba(242,184,75,.14);
            color: #fff;
        }

        .btn-tirta-brass {
            position: relative;
            overflow: hidden;
            background: linear-gradient(120deg, var(--brass), var(--violet));
            color: var(--ink);
            font-weight: 700;
            font-size: .88rem;
            border: none;
            border-radius: 12px;
            padding: 11px;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .btn-tirta-brass:hover {
            color: var(--ink);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(242,184,75,.28);
        }

        .auth-form small.text-muted { color: rgba(255,255,255,.45) !important; font-size: .78rem; }
        .auth-form small a { color: var(--aqua); }

        .alert-danger {
            font-size: .82rem;
            background: rgba(214,69,69,.12) !important;
            color: #ff9d9d !important;
            border: 1px solid rgba(214,69,69,.3);
            border-radius: 10px;
        }
        .alert-success {
            font-size: .82rem;
            background: rgba(47,230,200,.12) !important;
            color: #d9fff7 !important;
            border: 1px solid rgba(47,230,200,.35);
            border-radius: 10px;
        }
        .info-box {
            font-size: .78rem;
            color: rgba(255,255,255,.55);
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .info-box i { color: var(--brass); margin-right: 4px; }

        .btn-back-inside {
            display: inline-flex; align-items: center; gap: 6px;
            color: rgba(255,255,255,.55);
            font-size: .8rem;
            text-decoration: none;
            transition: color .15s ease;
        }
        .btn-back-inside:hover { color: #fff; }

        @media (max-width: 700px) {
            .auth-head { padding: 26px 24px 4px; }
            .auth-form { padding: 16px 24px 30px; }
        }
    </style>
</head>
<body>

<div class="ambient-orb o1"></div>
<div class="ambient-orb o2"></div>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-head">
            <div class="side-logo">
                <img src="<?= BASE_URL ?>img/kolam.jpg" alt="Logo Tirta Tamansari"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <span class="side-logo-fallback">TT</span>
            </div>
            <div>
                <div class="badge-tag">Pemulihan Akun</div>
                <h1 style="font-size:1.15rem; font-weight:700; margin:2px 0 0;">TIRTA TAMANSARI</h1>
            </div>
        </div>

        <div class="auth-form">
            <h3>Ajukan Aktivasi Akun</h3>
            <p class="subtitle">Akun Anda dinonaktifkan oleh admin sehingga tidak bisa login. Isi form di bawah ini untuk mengajukan permintaan aktivasi ulang.</p>

            <?php if ($sukses): ?>
                <div class="alert alert-success py-2 px-3 mb-3 border-0">
                    <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($sukses) ?>
                </div>
                <div class="text-center mt-2">
                    <a href="<?= BASE_URL ?>auth/login.php" class="btn-back-inside">
                        <i class="bi bi-box-arrow-in-right"></i> Kembali ke Halaman Login
                    </a>
                </div>
            <?php else: ?>

                <div class="info-box">
                    <i class="bi bi-info-circle-fill"></i>
                    Setelah pengajuan dikirim, admin akan menerima notifikasi dan meninjau permintaan Anda. Akun akan aktif kembali setelah disetujui admin.
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 px-3 mb-3 border-0">
                        <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-wrap">
                            <i class="bi bi-person field-icon"></i>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan username"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock field-icon"></i>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        </div>
                        <small class="text-muted d-block mt-1">Digunakan untuk memverifikasi bahwa ini benar akun Anda.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan Pengajuan (opsional)</label>
                        <div class="input-wrap">
                            <i class="bi bi-chat-left-text field-icon"></i>
                            <textarea name="alasan" class="form-control" rows="3" placeholder="Contoh: Akun saya dinonaktifkan, mohon diaktifkan kembali"><?= htmlspecialchars($_POST['alasan'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-tirta-brass w-100 mt-1">
                        Kirim Pengajuan <i class="bi bi-send ms-1"></i>
                    </button>
                </form>

            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>auth/login.php" class="btn-back-inside">
                    <i class="bi bi-arrow-left"></i> Kembali ke Halaman Login
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
