<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'db_stokly';

try {
    $koneksi = new mysqli($host, $user, $pass, $db);
    $koneksi->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('Koneksi database Stokly gagal: ' . $exception->getMessage());
    http_response_code(500);
    exit('Koneksi ke database gagal. Silakan coba lagi nanti.');
}