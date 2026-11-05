# Database Stokly

## Membuat database baru

1. Pastikan MySQL/MariaDB aktif (contohnya melalui XAMPP).
2. Buka phpMyAdmin, pilih **Import**, lalu impor `database/schema.sql`.
   Alternatif melalui terminal:

   ```sh
   mysql -u root -p < database/schema.sql
   ```

3. Buka aplikasi melalui Apache/PHP. Koneksi bawaan memakai
   `127.0.0.1:3306`, database `stokly_db`, user `root`, dan password kosong
   (konfigurasi default XAMPP).
4. Untuk kredensial lain, atur environment variable `DB_HOST`, `DB_PORT`,
   `DB_NAME`, `DB_USER`, dan `DB_PASSWORD` sebelum menjalankan PHP.
5. Buka `buat_akun.php` untuk membuat akun pengelola pertama, lalu masuk
   melalui `login.php`.

## Isi database

- `pengguna`: akun dengan role `admin`/`petugas`; kata sandi disimpan sebagai
  hash oleh PHP.
- `barang`: kode, nama, kategori, stok, satuan, dan harga barang.
- `transaksi_stok`: riwayat stok masuk/keluar beserta jumlah dan waktu.

Riwayat transaksi memakai foreign key `RESTRICT`: barang yang sudah memiliki
riwayat tidak dapat dihapus agar catatan stok tetap utuh. Ekspor/backup database
sebelum menjalankan perubahan skema pada database yang sudah berisi data.
