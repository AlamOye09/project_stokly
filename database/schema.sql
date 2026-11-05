CREATE DATABASE IF NOT EXISTS stokly_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE stokly_db;

CREATE TABLE IF NOT EXISTS pengguna (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    nama_pengguna VARCHAR(50) NOT NULL,
    kata_sandi VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'petugas') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pengguna_nama_pengguna (nama_pengguna)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS barang (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_barang VARCHAR(50) NOT NULL,
    nama_barang VARCHAR(150) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    stok INT UNSIGNED NOT NULL DEFAULT 0,
    satuan VARCHAR(30) NOT NULL DEFAULT 'pcs',
    harga DECIMAL(15, 2) UNSIGNED NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_barang_kode_barang (kode_barang),
    KEY idx_barang_nama_barang (nama_barang),
    KEY idx_barang_kategori (kategori),
    KEY idx_barang_stok (stok)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transaksi_stok (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    barang_id INT UNSIGNED NOT NULL,
    jenis ENUM('masuk', 'keluar') NOT NULL,
    jumlah INT UNSIGNED NOT NULL,
    keterangan VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_transaksi_barang (barang_id),
    KEY idx_transaksi_jenis_created (jenis, created_at),
    KEY idx_transaksi_created (created_at),
    CONSTRAINT fk_transaksi_barang
        FOREIGN KEY (barang_id) REFERENCES barang (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_transaksi_jumlah CHECK (jumlah > 0)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
