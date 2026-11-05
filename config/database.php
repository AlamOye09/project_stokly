<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'stokly_db';
$port = (int) (getenv('DB_PORT') ?: 3306);

try {
    $conn = new mysqli($host, $username, $password, $database, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('Stokly database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit(
        'Koneksi database gagal. Pastikan MySQL aktif, konfigurasi DB_* benar, ' .
        'dan database stokly_db sudah dibuat dari database/schema.sql.'
    );
}
