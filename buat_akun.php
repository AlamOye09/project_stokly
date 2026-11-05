
<?php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$jumlahAkun = $conn->query("SELECT COUNT(*) AS total FROM pengguna");
$dataAkun = $jumlahAkun->fetch_assoc();

if ((int) $dataAkun['total'] > 0) {
    header('Location: login.php');
    exit;
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $namaPengguna = trim($_POST['nama_pengguna'] ?? '');
    $kataSandi = $_POST['kata_sandi'] ?? '';
    $ulangiKataSandi = $_POST['ulangi_kata_sandi'] ?? '';

    if ($nama === '' || $namaPengguna === '' || $kataSandi === '') {
        $pesan = 'Semua kolom wajib diisi.';
    } elseif (strlen($namaPengguna) < 4) {
        $pesan = 'Nama pengguna minimal 4 karakter.';
    } elseif (strlen($kataSandi) < 8) {
        $pesan = 'Kata sandi minimal 8 karakter.';
    } elseif ($kataSandi !== $ulangiKataSandi) {
        $pesan = 'Kedua kata sandi tidak sama.';
    } else {
        $kataSandiAman = password_hash($kataSandi, PASSWORD_DEFAULT);

        $perintah = $conn->prepare(
            "INSERT INTO pengguna (nama, nama_pengguna, kata_sandi)
             VALUES (?, ?, ?)"
        );

        $perintah->bind_param(
            'sss',
            $nama,
            $namaPengguna,
            $kataSandiAman
        );

        if ($perintah->execute()) {
            header('Location: login.php?akun=berhasil');
            exit;
        } else {
            $pesan = 'Akun gagal dibuat. Coba gunakan nama pengguna lain.';
        }

        $perintah->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buat Akun | Stokly</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            color: #fff;
            background: linear-gradient(135deg, #130d20, #38204f, #761d69);
        }
        .kartu {
            width: 100%;
            max-width: 440px;
            padding: 32px;
            border: 1px solid #ffffff25;
            border-radius: 22px;
            background: #191324;
            box-shadow: 0 20px 60px #0005;
        }
        h1 { margin: 0 0 8px; }
        .keterangan { color: #c9bfd5; margin-bottom: 24px; }
        label { display: block; margin: 16px 0 7px; }
        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #554562;
            border-radius: 10px;
            background: #261d32;
            color: white;
        }
        button {
            width: 100%;
            margin-top: 22px;
            padding: 14px;
            border: 0;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            background: linear-gradient(90deg, #a855f7, #ec4899);
            cursor: pointer;
        }
        .pesan {
            padding: 12px;
            border-radius: 8px;
            background: #642b43;
            color: #ffd9e7;
        }
        .logo { color: #f0abfc; font-weight: bold; }
    </style>
</head>
<body>
    <main class="kartu">
        <div class="logo">STOKLY</div>
        <h1>Buat Akun Pengelola</h1>
        <p class="keterangan">Buat akun pertama untuk mengelola persediaan barang.</p>

        <?php if ($pesan !== ''): ?>
            <p class="pesan"><?= htmlspecialchars($pesan) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="nama">Nama lengkap</label>
            <input id="nama" name="nama" required maxlength="100"
                   value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">

            <label for="nama_pengguna">Nama pengguna</label>
            <input id="nama_pengguna" name="nama_pengguna"
                   required minlength="4" maxlength="50"
                   autocomplete="username"
                   value="<?= htmlspecialchars($_POST['nama_pengguna'] ?? '') ?>">

            <label for="kata_sandi">Kata sandi</label>
            <input id="kata_sandi" type="password" name="kata_sandi"
                   required minlength="8" autocomplete="new-password">

            <label for="ulangi_kata_sandi">Ulangi kata sandi</label>
            <input id="ulangi_kata_sandi" type="password"
                   name="ulangi_kata_sandi" required minlength="8"
                   autocomplete="new-password">

            <button type="submit">Buat Akun</button>
        </form>
    </main>
</body>
</html>
