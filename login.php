
<?php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!empty($_SESSION['pengguna_id'])) {
    header('Location: index.php');
    exit;
}

$pesan = '';
$jenisPesan = 'error';

if (isset($_GET['akun']) && $_GET['akun'] === 'berhasil') {
    $pesan = 'Akun berhasil dibuat. Silakan masuk.';
    $jenisPesan = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaPengguna = trim($_POST['nama_pengguna'] ?? '');
    $kataSandi = $_POST['kata_sandi'] ?? '';

    if ($namaPengguna === '' || $kataSandi === '') {
        $pesan = 'Nama pengguna dan kata sandi wajib diisi.';
    } else {
        $perintah = $conn->prepare(
            "SELECT id, nama, nama_pengguna, kata_sandi, `role`
             FROM pengguna
             WHERE nama_pengguna = ?
             LIMIT 1"
        );

        if (!$perintah) {
            $pesan = 'Terjadi kesalahan. Silakan coba kembali.';
        } else {
            $perintah->bind_param('s', $namaPengguna);
            $perintah->execute();

            $hasil = $perintah->get_result();
            $pengguna = $hasil->fetch_assoc();

            $perintah->close();

            if (
                $pengguna &&
                password_verify($kataSandi, $pengguna['kata_sandi'])
            ) {
                session_regenerate_id(true);

                $_SESSION['pengguna_id'] = $pengguna['id'];
                $_SESSION['nama_pengguna'] = $pengguna['nama_pengguna'];
                $_SESSION['nama'] = $pengguna['nama'];
                $_SESSION['role'] = $pengguna['role'];

                header('Location: index.php');
                exit;
            }

            $pesan = 'Nama pengguna atau kata sandi salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fff7fa">

    <title>Masuk | Stokly</title>

    <style>
        :root {
            --pink: #F9A8D4;
            --pink-dark: #BE185D;
            --coral: #FB7185;
            --bg: #FFF1F5;
            --white: #FFFFFF;
            --text: #3F2933;
            --muted: #92717E;
            --border: #FBCFE0;
            --green: #16845B;
            --green-bg: #E4F8EE;
            --red: #D93652;
            --red-bg: #FFE4E8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            padding: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(
                    circle at 5% 10%,
                    rgba(249, 168, 212, .35),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 95% 90%,
                    rgba(251, 113, 133, .20),
                    transparent 30%
                ),
                #FFF7FA;
        }

        .login-layout {
            width: 100%;
            max-width: 1120px;
            min-height: 650px;
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            overflow: hidden;
            background: var(--white);
            border: 1px solid rgba(251, 207, 224, .8);
            border-radius: 28px;
            box-shadow: 0 24px 80px rgba(111, 40, 70, .10);
        }

        /* PANEL BRANDING */

        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            overflow: hidden;
            background:
                radial-gradient(
                    circle at 90% 10%,
                    rgba(255, 255, 255, .9),
                    transparent 28%
                ),
                linear-gradient(145deg, #FFF1F5 0%, #FFE4EC 55%, #FCE7F3 100%);
        }

        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .brand-panel::before {
            width: 290px;
            height: 290px;
            right: -120px;
            bottom: 90px;
            background: rgba(251, 113, 133, .12);
        }

        .brand-panel::after {
            width: 170px;
            height: 170px;
            right: 65px;
            bottom: 35px;
            background: rgba(249, 168, 212, .22);
        }

        .brand-content,
        .brand-footer,
        .brand-top {
            position: relative;
            z-index: 1;
        }

        .brand-top {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            color: white;
            font-size: 23px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--pink-dark), var(--coral));
            box-shadow: 0 7px 18px rgba(190, 24, 93, .18);
        }

        .logo-name {
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -1.2px;
        }

        .logo-name span {
            color: var(--coral);
        }

        .brand-tagline {
            margin-top: 3px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.8px;
        }

        .brand-content {
            margin: 65px 0 45px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border: 1px solid #FBCFE0;
            border-radius: 30px;
            color: var(--pink-dark);
            background: rgba(255, 255, 255, .7);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .7px;
        }

        .eyebrow-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--coral);
        }

        .brand-content h1 {
            max-width: 430px;
            margin-top: 25px;
            font-size: clamp(36px, 4vw, 49px);
            line-height: 1.13;
            letter-spacing: -2px;
        }

        .brand-content h1 span {
            color: var(--pink-dark);
        }

        .brand-description {
            max-width: 390px;
            margin-top: 20px;
            color: #795765;
            font-size: 14px;
            line-height: 1.9;
        }

        .benefit-list {
            display: grid;
            gap: 14px;
            margin-top: 32px;
        }

        .benefit {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .benefit-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(251, 207, 224, .9);
            border-radius: 12px;
            background: rgba(255, 255, 255, .85);
            font-size: 18px;
        }

        .benefit strong {
            display: block;
            font-size: 13px;
        }

        .benefit small {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .brand-footer {
            color: var(--muted);
            font-size: 10px;
            letter-spacing: .3px;
        }

        /* PANEL LOGIN */

        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 55px 48px;
            background: var(--white);
        }

        .login-card {
            width: 100%;
            max-width: 365px;
        }

        .form-overline {
            margin-bottom: 12px;
            color: var(--pink-dark);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.8px;
        }

        .login-card h2 {
            font-size: 31px;
            line-height: 1.25;
            letter-spacing: -1px;
        }

        .form-description {
            margin-top: 12px;
            margin-bottom: 31px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .pesan {
            padding: 12px 14px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.6;
        }

        .pesan.error {
            color: var(--red);
            background: var(--red-bg);
            border: 1px solid #FFC4CE;
        }

        .pesan.success {
            color: var(--green);
            background: var(--green-bg);
            border: 1px solid #B7E8D0;
        }

        .field {
            margin-bottom: 21px;
        }

        .field label {
            display: block;
            margin-bottom: 9px;
            font-size: 12px;
            font-weight: 700;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap input {
            width: 100%;
            min-height: 49px;
            padding: 14px 15px;
            border: 1px solid #E9D9E0;
            border-radius: 11px;
            outline: none;
            background: #FFFCFD;
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .input-wrap input:focus {
            border-color: var(--coral);
            background: white;
            box-shadow: 0 0 0 4px rgba(251, 113, 133, .12);
        }

        .input-wrap input::placeholder {
            color: #B49AA5;
        }

        .password-input {
            padding-right: 75px !important;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 13px;
            width: auto;
            margin: 0;
            padding: 5px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            box-shadow: none;
            color: var(--pink-dark);
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .toggle-password:hover {
            transform: translateY(-50%);
            box-shadow: none;
            color: var(--coral);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 2px 0 24px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 11px;
            cursor: pointer;
        }

        .remember input {
            width: 15px;
            height: 15px;
            accent-color: var(--coral);
        }

        .form-options a {
            color: var(--pink-dark);
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }

        .form-options a:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            min-height: 49px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px;
            border: none;
            border-radius: 11px;
            color: white;
            background: linear-gradient(110deg, var(--pink-dark), var(--coral));
            box-shadow: 0 8px 20px rgba(251, 113, 133, .22);
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(251, 113, 133, .30);
        }

        .btn-login span {
            font-size: 17px;
            transition: transform .2s;
        }

        .btn-login:hover span {
            transform: translateX(4px);
        }

        .security-note {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin-top: 23px;
            color: var(--muted);
            font-size: 10px;
        }

        .security-note span {
            color: var(--green);
        }

        .form-footer {
            margin-top: 43px;
            padding-top: 20px;
            border-top: 1px solid #F3E7EC;
            color: #A28A94;
            font-size: 10px;
            text-align: center;
            line-height: 1.8;
        }

        @media (max-width: 850px) {
            body {
                padding: 20px;
            }

            .login-layout {
                max-width: 560px;
                min-height: auto;
                grid-template-columns: 1fr;
            }

            .brand-panel {
                padding: 30px;
            }

            .brand-content {
                margin: 35px 0 25px;
            }

            .brand-content h1 {
                max-width: 500px;
                font-size: 37px;
            }

            .brand-description {
                max-width: 500px;
            }

            .benefit-list {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .brand-footer {
                margin-top: 15px;
            }

            .form-panel {
                padding: 38px 30px;
            }

            .login-card {
                max-width: 480px;
            }

            .form-footer {
                margin-top: 30px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }

            .login-layout {
                border-radius: 20px;
            }

            .brand-panel {
                padding: 24px 21px;
            }

            .logo-icon {
                width: 40px;
                height: 40px;
            }

            .logo-name {
                font-size: 24px;
            }

            .brand-content {
                margin: 30px 0 23px;
            }

            .brand-content h1 {
                font-size: 32px;
                letter-spacing: -1.3px;
            }

            .brand-description {
                font-size: 12px;
            }

            .benefit-list {
                grid-template-columns: 1fr;
                gap: 13px;
            }

            .form-panel {
                padding: 32px 22px;
            }

            .login-card h2 {
                font-size: 27px;
            }

            .form-description {
                margin-bottom: 25px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition: none !important;
            }
        }
    </style>
</head>

<body>
    <main class="login-layout">

        <section class="brand-panel">
            <div class="brand-top">
                <div class="logo-icon">S</div>
                <div>
                    <div class="logo-name">stok<span>ly.</span></div>
                    <div class="brand-tagline">INVENTORY MANAGEMENT SYSTEM</div>
                </div>
            </div>

            <div class="brand-content">
                <div class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    SISTEM INFORMASI STOK BARANG
                </div>

                <h1>
                    Kelola Stok,<br>
                    <span>Tanpa Ribet.</span>
                </h1>

                <p class="brand-description">
                    Satu platform untuk mengelola persediaan barang,
                    mencatat transaksi, dan memantau laporan stok
                    dengan lebih mudah dan terorganisir.
                </p>

                <div class="benefit-list">
                    <div class="benefit">
                        <div class="benefit-icon">▦</div>
                        <div>
                            <strong>Stok Lebih Terkontrol</strong>
                            <small>Pantau persediaan barang dengan praktis.</small>
                        </div>
                    </div>

                    <div class="benefit">
                        <div class="benefit-icon">↗</div>
                        <div>
                            <strong>Transaksi Terorganisir</strong>
                            <small>Catat barang masuk dan keluar dengan mudah.</small>
                        </div>
                    </div>

                    <div class="benefit">
                        <div class="benefit-icon">▤</div>
                        <div>
                            <strong>Laporan Lebih Jelas</strong>
                            <small>Bantu pengelolaan data stok lebih efisien.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="brand-footer">
                SOLUSI CERDAS UNTUK PENGELOLAAN INVENTARIS
            </div>
        </section>

        <section class="form-panel">
            <div class="login-card">
                <div class="form-overline">SELAMAT DATANG KEMBALI</div>

                <h2>Masuk ke akunmu.</h2>

                <p class="form-description">
                    Silakan masukkan nama pengguna dan kata sandi
                    untuk melanjutkan ke dashboard Stokly.
                </p>

                <?php if ($pesan !== ''): ?>
                    <div
                        class="pesan <?= e($jenisPesan) ?>"
                        role="alert"
                    >
                        <?= e($pesan) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="field">
                        <label for="nama_pengguna">Nama Pengguna</label>

                        <div class="input-wrap">
                            <input
                                id="nama_pengguna"
                                type="text"
                                name="nama_pengguna"
                                placeholder="Masukkan nama pengguna"
                                required
                                autocomplete="username"
                                autofocus
                                value="<?= e($_POST['nama_pengguna'] ?? '') ?>"
                            >
                        </div>
                    </div>

                    <div class="field">
                        <label for="kata_sandi">Kata Sandi</label>

                        <div class="input-wrap">
                            <input
                                class="password-input"
                                id="kata_sandi"
                                type="password"
                                name="kata_sandi"
                                placeholder="Masukkan kata sandi"
                                required
                                autocomplete="current-password"
                            >

                            <button
                                class="toggle-password"
                                type="button"
                                id="togglePassword"
                                aria-label="Tampilkan kata sandi"
                                aria-pressed="false"
                            >
                                Lihat
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember">
                            <input type="checkbox" name="ingat_saya" value="1">
                            Ingat saya
                        </label>

                        <a
                            href="#"
                            onclick="return false;"
                            title="Hubungi administrator untuk bantuan akun"
                        >Butuh bantuan?</a>
                    </div>

                    <button class="btn-login" type="submit">
                        Masuk ke Stokly <span>→</span>
                    </button>
                </form>

                <div class="security-note">
                    <span>●</span>
                    Area login khusus pengguna terdaftar
                </div>

                <div class="form-footer">
                    © <?= date('Y') ?> Stokly Inventory System<br>
                    Sederhana · Efisien · Terorganisir
                </div>
            </div>
        </section>

    </main>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('kata_sandi');

        togglePassword.addEventListener('click', function () {
            const sedangTampil = passwordInput.type === 'text';

            passwordInput.type = sedangTampil ? 'password' : 'text';
            togglePassword.textContent = sedangTampil ? 'Lihat' : 'Sembunyikan';
            togglePassword.setAttribute('aria-pressed', String(!sedangTampil));
            togglePassword.setAttribute(
                'aria-label',
                sedangTampil ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi'
            );
        });
    </script>
</body>
</html>
