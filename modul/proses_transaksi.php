<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/transaksi_stok.php');
    exit;
}

$idBarang = filter_var($_POST['id_barang'] ?? null, FILTER_VALIDATE_INT);
$jenisTransaksi = $_POST['jenis_transaksi'] ?? '';
$jumlah = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);
$keterangan = $_POST['keterangan'] ?? '';

if (
    $idBarang === false || $idBarang < 1 || $jumlah === false || $jumlah < 1 ||
    !is_string($jenisTransaksi) || !in_array($jenisTransaksi, ['masuk', 'keluar'], true) ||
    !is_string($keterangan)
) {
    header('Location: ../pages/transaksi_stok.php?status=input_tidak_valid');
    exit;
}

$transaksiDimulai = false;
try {
    $koneksi->begin_transaction();
    $transaksiDimulai = true;

    // Kunci baris agar dua transaksi serentak tidak memakai nilai stok yang sama.
    $statement = $koneksi->prepare('SELECT stok FROM barang WHERE id_barang = ? FOR UPDATE');
    $statement->bind_param('i', $idBarang);
    $statement->execute();
    $barang = $statement->get_result()->fetch_assoc();
    $statement->close();

    if ($barang === null) {
        $koneksi->rollback();
        $transaksiDimulai = false;
        header('Location: ../pages/transaksi_stok.php?status=barang_tidak_ditemukan');
        exit;
    }

    if ($jenisTransaksi === 'keluar' && (int) $barang['stok'] < $jumlah) {
        $koneksi->rollback();
        $transaksiDimulai = false;
        header('Location: ../pages/transaksi_stok.php?status=stok_tidak_cukup');
        exit;
    }

    $perubahanStok = $jenisTransaksi === 'masuk' ? $jumlah : -$jumlah;
    $statement = $koneksi->prepare('UPDATE barang SET stok = stok + ? WHERE id_barang = ?');
    $statement->bind_param('ii', $perubahanStok, $idBarang);
    $statement->execute();
    $statement->close();

    $statement = $koneksi->prepare(
        'INSERT INTO transaksi (id_barang, jenis_transaksi, jumlah, keterangan, tanggal_transaksi)
         VALUES (?, ?, ?, ?, NOW())'
    );
    $statement->bind_param('isis', $idBarang, $jenisTransaksi, $jumlah, $keterangan);
    $statement->execute();
    $statement->close();

    $koneksi->commit();
    $transaksiDimulai = false;
    header('Location: ../pages/transaksi_stok.php?status=berhasil');
} catch (Throwable $exception) {
    if ($transaksiDimulai) {
        $koneksi->rollback();
    }
    error_log('Transaksi stok gagal: ' . $exception->getMessage());
    header('Location: ../pages/transaksi_stok.php?status=gagal');
}
exit;