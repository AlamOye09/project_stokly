
<?php
require_once __DIR__ . '/config/database.php';

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $username === '' || $password === '') {
        $pesan = 'Semua kolom wajib diisi.';
    } elseif (strlen($password) < 8) {
        $pesan = 'Password minimal 8 karakter.';
    } else {
        $cek = $conn->prepare(
            'SELECT id FROM pengguna WHERE nama_pengguna = ?'
        );
        $cek->bind_param('s', $username);
        $cek->execute();
        $hasil = $cek->get_result();

        if ($hasil->num_rows > 0) {
            $pesan = 'Username sudah digunakan. Pilih username lain.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                'INSERT INTO pengguna (nama, nama_pengguna, kata_sandi)
                 VALUES (?, ?, ?)'
            );
            $stmt->bind_param('sss', $nama, $username, $hash);

            if ($stmt->execute()) {
                $pesan = 'Akun berhasil dibuat! Silakan login.';
            } else {
                $pesan = 'Akun gagal dibuat. Periksa kembali database.';
            }

            $stmt->close();
        }

        $cek->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buat Akun Stokly</title>
</head>
<body>
    <h2>Buat Akun Pengujian Stokly</h2>

    <p><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></p>

    <form method="POST">
        <p>Nama lengkap</p>
        <input type="text" name="nama" required>

        <p>Username</p>
        <input type="text" name="username" required>

        <p>Password (minimal 8 karakter)</p>
        <input type="password" name="password" minlength="8" required>

        <p><button type="submit">Buat Akun</button></p>
    </form>
</body>
</html>
