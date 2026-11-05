<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/data_barang.php');
    exit;
}

$idBarang = filter_var($_POST['id_barang'] ?? null, FILTER_VALIDATE_INT);
$kodeBarang = $_POST['kode_barang'] ?? '';
$namaBarang = $_POST['nama_barang'] ?? '';
$kategori = $_POST['kategori'] ?? '';
$satuan = $_POST['satuan'] ?? 'Pcs';
$hargaBarang = $_POST['harga_barang'] ?? '';
$stokInput = filter_var($_POST['stok'] ?? null, FILTER_VALIDATE_INT);

if (
    $idBarang === false || $idBarang < 1 || !is_string($kodeBarang) || !is_string($namaBarang) ||
    !is_string($kategori) || !is_string($satuan) || !is_scalar($hargaBarang) ||
    trim($kodeBarang) === '' || trim($namaBarang) === '' || trim($kategori) === '' ||
    trim($satuan) === '' || preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', (string) $hargaBarang) !== 1 ||
    $stokInput === false || $stokInput < 0
) {
    header('Location: ../pages/data_barang.php?status=input_tidak_valid');
    exit;
}

$kodeBarang = trim($kodeBarang);
$namaBarang = trim($namaBarang);
$kategori = trim($kategori);
$satuan = trim($satuan);

try {
    $statement = $koneksi->prepare(
        'UPDATE barang
         SET kode_barang = ?, nama_barang = ?, kategori = ?, satuan = ?, harga_barang = ?, stok = ?
         WHERE id_barang = ?'
    );
    $statement->bind_param(
        'sssssii',
        $kodeBarang,
        $namaBarang,
        $kategori,
        $satuan,
        $hargaBarang,
        $stokInput,
        $idBarang
    );
    $statement->execute();
    $statement->close();

    header('Location: ../pages/data_barang.php?status=berhasil_edit');
} catch (mysqli_sql_exception $exception) {
    error_log('Edit barang gagal: ' . $exception->getMessage());
    header('Location: ../pages/data_barang.php?status=gagal_edit');
}
exit;