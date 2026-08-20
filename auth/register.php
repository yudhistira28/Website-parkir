<?php
require_once __DIR__ . '/../config/koneksi.php';

// Jika sudah login, langsung arahkan ke dashboard sesuai role
if (isset($_SESSION['id_user'])) {
    switch ($_SESSION['role']) {
        case 'admin': header("Location: " . BASE_URL . "admin/index.php"); exit;
        case 'petugas': header("Location: " . BASE_URL . "operator/index.php"); exit;
        case 'owner': header("Location: " . BASE_URL . "owner/index.php"); exit;
        case 'member': header("Location: " . BASE_URL . "member/index.php"); exit;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    if ($nama_lengkap === '' || $username === '' || $password === '' || $konfirmasi_password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi_password) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek username sudah dipakai atau belum
        $cek = $koneksi->prepare("SELECT id_user FROM tb_user WHERE username = ? LIMIT 1");
        $cek->execute([$username]);

        if ($cek->fetch()) {
            $error = 'Username sudah digunakan, silakan pilih username lain.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'member'; // registrasi mandiri publik HANYA untuk role member

            $stmt = $koneksi->prepare(
                "INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif) 
                 VALUES (?, ?, ?, ?, 1)"
            );
            $stmt->execute([$nama_lengkap, $username, $hash, $role]);

            $success = 'Registrasi berhasil! Silakan login menggunakan akun anda.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi - Parkir Tirta Tamansari</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        /* ============================================================
           Halaman Registrasi — versi minimalis, satu kartu terpusat
           ============================================================ */
        :root {
            --ink: #070b1a;
            --ink-2: #0e1830;
            --aqua: #2fe6c8;
            --aqua-dim: #1ba893;
            --violet: #8b7cfa;
            --stone: #eef1f6;
            --stone-2: #dfe4ee;
            --text-soft: #566178;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--ink);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 90px 20px 40px;
            -webkit-font-smoothing: antialiased;
        }
        h1, h3, .brand-name { font-family: 'Space Grotesk', system-ui, sans-serif; }

        .auth-wrapper { width: 100%; max-width: 400px; }

        .link-back-login {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--aqua-dim);
            font-weight: 600;
            font-size: .86rem;
            text-decoration: none;
            transition: color .15s ease;
        }
        .link-back-login:hover { color: var(--ink); }

        .auth-brand {
            text-align: center;
            margin-bottom: 26px;
        }
        .auth-brand .name {
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: .3px;
        }
        .auth-brand .sub {
            color: rgba(255,255,255,.45);
            font-size: .82rem;
            margin-top: 2px;
        }

        .auth-card {
            background: var(--stone);
            border-radius: 20px;
            padding: 36px 32px;
            color: var(--ink);
        }
        .auth-card h3 { font-weight: 700; font-size: 1.25rem; margin: 0 0 4px; color: var(--ink); }
        .auth-card .subtitle { color: var(--text-soft); font-size: .86rem; margin-bottom: 22px; }

        .auth-card .form-label {
            font-weight: 600; font-size: .82rem; color: var(--ink); margin-bottom: 5px;
        }
        .auth-card .form-control {
            border: 1px solid var(--stone-2);
            border-radius: 10px;
            padding: 10px 13px;
            font-size: .92rem;
            background: #fff;
            color: var(--ink);
        }
        .auth-card .form-control:focus {
            border-color: var(--aqua);
            box-shadow: 0 0 0 .2rem rgba(47,230,200,.15);
        }
        .auth-card .form-control::placeholder { color: #a7b0c0; }

        .btn-tirta {
            background: linear-gradient(120deg, var(--aqua), var(--violet));
            color: var(--ink);
            border: none;
            font-weight: 700;
            border-radius: 999px;
            padding: 11px 22px;
            font-size: .92rem;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .btn-tirta:hover {
            color: var(--ink);
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(47,230,200,.3);
        }

        .auth-card .alert { border-radius: 10px; font-size: .84rem; padding: 10px 14px; }
        .auth-card .text-muted { color: var(--text-soft) !important; }
        .auth-card a { color: var(--aqua-dim); font-weight: 600; text-decoration: none; }
        .auth-card a:hover { color: var(--ink); text-decoration: underline; }

        @media (max-width: 480px) {
            .auth-card { padding: 30px 24px; }
        }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-brand">
        <div class="name">TIRTA TAMANSARI</div>
        <div class="sub">Sistem Manajemen Parkir</div>
    </div>

    <div class="auth-card">
        <h3>Buat Akun Baru</h3>
        <p class="subtitle">Daftar sebagai member untuk menikmati layanan parkir Tirta Tamansari</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap"
                       value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn btn-tirta w-100 py-2 mt-2">Daftar <i class="bi bi-person-plus"></i></button>
        </form>
        <div class="text-center mt-4">
            <small class="text-muted">Sudah punya akun? <a href="<?= BASE_URL ?>auth/login.php">Masuk di sini</a></small>
        </div>
        <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>auth/login.php" class="link-back-login">
                <i class="bi bi-arrow-left"></i> Kembali ke Login
            </a>
        </div>
    </div>
</div>
</body>
</html>