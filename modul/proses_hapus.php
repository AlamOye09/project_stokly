<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}

$idBarang = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($idBarang === false || $idBarang < 1) {
    header('Location: ../pages/data_barang.php?status=input_tidak_valid');
    exit;
}

try {
    $statement = $koneksi->prepare('DELETE FROM barang WHERE id_barang = ?');
    $statement->bind_param('i', $idBarang);
    $statement->execute();
    $deleted = $statement->affected_rows === 1;
    $statement->close();

    header('Location: ../pages/data_barang.php?status=' . ($deleted ? 'berhasil_hapus' : 'gagal_hapus'));
} catch (mysqli_sql_exception $exception) {
    // Database menolak penghapusan jika barang masih memiliki riwayat transaksi.
    error_log('Hapus barang gagal: ' . $exception->getMessage());
    header('Location: ../pages/data_barang.php?status=gagal_hapus');
}
exit;