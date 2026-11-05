<?php
require_once __DIR__ . '/../config/koneksi.php';

// Dashboard menggunakan batas stok menipis 5 unit atau kurang.
$query = $koneksi->query(
    'SELECT
        COUNT(*) AS total_jenis,
        COALESCE(SUM(stok), 0) AS total_stok,
        COALESCE(SUM(CASE WHEN stok <= 5 THEN 1 ELSE 0 END), 0) AS stok_menipis,
        COALESCE(SUM(harga_barang * stok), 0) AS total_nilai
     FROM barang'
);
$ringkasan = $query->fetch_assoc();

$total_jenis = (int) $ringkasan['total_jenis'];
$total_stok = (int) $ringkasan['total_stok'];
$stok_menipis = (int) $ringkasan['stok_menipis'];
$total_nilai = (float) $ringkasan['total_nilai'];