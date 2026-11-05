CREATE DATABASE IF NOT EXISTS db_stokly
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_stokly;

CREATE TABLE IF NOT EXISTS users (
    id_user INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'Admin',
    PRIMARY KEY (id_user),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS barang (
    id_barang INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_barang VARCHAR(50) NOT NULL,
    nama_barang VARCHAR(150) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    satuan VARCHAR(30) NOT NULL DEFAULT 'Pcs',
    harga_barang DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    stok INT UNSIGNED NOT NULL DEFAULT 0,
    tanggal_input DATE NOT NULL,
    PRIMARY KEY (id_barang),
    UNIQUE KEY uq_barang_kode (kode_barang),
    KEY idx_barang_nama (nama_barang),
    CONSTRAINT chk_barang_harga CHECK (harga_barang >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transaksi (
    id_transaksi INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_barang INT UNSIGNED NOT NULL,
    jenis_transaksi ENUM('masuk', 'keluar') NOT NULL,
    jumlah INT UNSIGNED NOT NULL,
    keterangan TEXT NULL,
    tanggal_transaksi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_transaksi),
    KEY idx_transaksi_barang_tanggal (id_barang, tanggal_transaksi),
    CONSTRAINT chk_transaksi_jumlah CHECK (jumlah > 0),
    CONSTRAINT fk_transaksi_barang FOREIGN KEY (id_barang)
        REFERENCES barang (id_barang)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Hash bcrypt untuk kata sandi awal "admin123"; ganti kata sandi setelah login pertama.
INSERT INTO users (nama, username, password, role)
VALUES (
    'Administrator',
    'admin',
    '$2y$10$jc4O4tFXmtwK2Th54y86OOCga8j0AuJpnpwBH0kmUgcaaQ9tck1Ey',
    'Admin'
)
ON DUPLICATE KEY UPDATE username = VALUES(username);