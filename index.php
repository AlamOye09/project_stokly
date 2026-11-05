
<?php
require_once __DIR__ . '/includes/auth.php';
wajibMasuk();
require_once __DIR__ . '/config/database.php';

$namaAplikasi = 'Stokly';
$namaPengguna = $_SESSION['nama'] ?? 'Pengelola';
$hurufNama = strtoupper(substr($namaPengguna, 0, 1));

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$errorDatabase = false;
$totalBarang = 0;
$totalStok = 0;
$stokRendah = 0;
$daftarBarang = [];
$daftarPeringatan = [];

try {
    // Menghitung total jenis barang
    $hasilBarang = $conn->query(
        "SELECT COUNT(*) AS total FROM barang"
    );
    $totalBarang = (int)$hasilBarang->fetch_assoc()['total'];

    // Menghitung total seluruh stok
    $hasilStok = $conn->query(
        "SELECT COALESCE(SUM(stok), 0) AS total FROM barang"
    );
    $totalStok = (int)$hasilStok->fetch_assoc()['total'];

    // Menghitung barang dengan stok 5 atau kurang
    $hasilStokRendah = $conn->query(
        "SELECT COUNT(*) AS total FROM barang WHERE stok <= 5"
    );
    $stokRendah = (int)$hasilStokRendah->fetch_assoc()['total'];

    // Mengambil 5 barang terbaru
    $hasilDaftarBarang = $conn->query(
        "SELECT kode_barang, nama_barang, kategori, stok, satuan, harga
         FROM barang
         ORDER BY id DESC
         LIMIT 5"
    );

    while ($barang = $hasilDaftarBarang->fetch_assoc()) {
        $daftarBarang[] = $barang;
    }

    // Mengambil seluruh barang yang stoknya menipis atau habis
    $hasilPeringatan = $conn->query(
        "SELECT kode_barang, nama_barang, stok, satuan
         FROM barang
         WHERE stok <= 5
         ORDER BY stok ASC, nama_barang ASC"
    );

    while ($barang = $hasilPeringatan->fetch_assoc()) {
        $daftarPeringatan[] = $barang;
    }

} catch (mysqli_sql_exception $ex) {
    $errorDatabase = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Stokly</title>

    <style>
        :root {
            --bg: #FFF1F5;
            --white: #FFFFFF;
            --pink: #F9A8D4;
            --pink-dark: #BE185D;
            --coral: #FB7185;
            --text: #3F2933;
            --muted: #92717E;
            --border: #FBCFE0;
            --soft: #FFE4EC;
            --green: #15803D;
            --red: #BE123C;
            --shadow: 0 8px 28px rgba(190,24,93,.07);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(ellipse at top right, #FFE0E9, transparent 42%),
                var(--bg);
            color: var(--text);
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            padding: 28px 18px;
            background: var(--white);
            border-right: 1px solid var(--border);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }

        .brand {
            font-size: 29px;
            font-weight: 850;
            letter-spacing: -1.5px;
            margin: 0 0 7px 8px;
        }

        .brand span {
            color: var(--coral);
        }

        .tagline {
            color: var(--muted);
            font-size: 12px;
            margin: 0 0 36px 8px;
        }

        .menu-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
            margin: 24px 8px 12px;
        }

        .nav {
            display: block;
            text-decoration: none;
            color: var(--muted);
            padding: 13px 14px;
            margin-bottom: 8px;
            border-radius: 12px;
            font-size: 14px;
            transition: .2s;
        }

        .nav:hover,
        .nav.active {
            color: var(--pink-dark);
            background: linear-gradient(100deg, #FFE4EC, #FFF1F5);
            border: 1px solid var(--border);
        }

        .sidebar-note {
            margin-top: auto;
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 15px;
            background: #FFF8FA;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .sidebar-note strong {
            display: block;
            color: var(--pink-dark);
            margin-bottom: 4px;
        }

        /* MAIN */
        .main {
            flex: 1;
            min-width: 0;
            padding: 30px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 28px;
        }

        .eyebrow {
            color: var(--pink-dark);
            font-size: 11px;
            letter-spacing: 2px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        h1 {
            font-size: clamp(25px, 3vw, 32px);
            letter-spacing: -1px;
        }

        .subtitle {
            color: var(--muted);
            font-size: 13px;
            margin-top: 8px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-info {
            text-align: right;
        }

        .profile-info strong {
            display: block;
            font-size: 12px;
        }

        .profile-info span {
            color: var(--muted);
            font-size: 10px;
        }

        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--coral), var(--pink));
            color: white;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 5px 16px #FB718533;
        }

        /* STATISTIK */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat {
            background: linear-gradient(145deg, #FFFFFF, #FFF9FB);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 21px;
            box-shadow: var(--shadow);
            transition: .2s;
        }

        .stat:hover {
            transform: translateY(-3px);
        }

        .stat-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .stat-value {
            font-size: 29px;
            font-weight: 800;
            margin: 12px 0 5px;
        }

        .stat-desc {
            color: var(--muted);
            font-size: 11px;
        }

        .purple { color: var(--pink-dark); }
        .pink { color: #E11D48; }
        .green { color: var(--green); }

        /* PANEL */
        .panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 23px;
            margin-bottom: 22px;
            min-width: 0;
            box-shadow: var(--shadow);
        }

        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }

        .panel-heading h2 {
            font-size: 18px;
        }

        .panel-heading p {
            color: var(--muted);
            font-size: 12px;
            margin-top: 6px;
        }

        /* NOTIFIKASI STOK */
        .notification-panel {
            margin-bottom: 24px;
            padding: 22px;
            border: 1px solid #FDA4AF;
            border-radius: 18px;
            background: linear-gradient(145deg, #FFF1F2, #FFFFFF);
            box-shadow: var(--shadow);
        }

        .notification-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 17px;
        }

        .notification-heading h2 {
            font-size: 17px;
            color: #9F1239;
        }

        .notification-heading p {
            margin-top: 6px;
            color: var(--muted);
            font-size: 11px;
        }

        .notification-heading a {
            color: var(--pink-dark);
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }

        .notification-heading a:hover {
            text-decoration: underline;
        }

        .notification-list {
            display: grid;
            gap: 9px;
        }

        .notification-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 15px;
            border: 1px solid #FBCFE0;
            border-radius: 11px;
            background: #FFFFFF;
        }

        .notification-info strong {
            display: block;
            color: var(--text);
            font-size: 12px;
        }

        .notification-info span {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 10px;
        }

        .warning-badge {
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .low-stock {
            background: #FFEDD5;
            color: #C2410C;
        }

        .empty-stock {
            background: #FFE4E6;
            color: #BE123C;
        }

        .notification-empty {
            padding: 17px;
            text-align: center;
            border-radius: 11px;
            background: #F0FDF4;
            color: #166534;
            font-size: 12px;
        }

        /* TABEL */
        .table-wrap {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        th {
            text-align: left;
            color: #795766;
            background: #FFF1F5;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 13px 12px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #FCE7EF;
            font-size: 12px;
            color: #59404B;
        }

        tbody tr:hover {
            background: #FFF8FA;
        }

        .item-name {
            color: var(--text);
            font-weight: 750;
            margin-bottom: 5px;
        }

        .item-code {
            color: var(--muted);
            font-size: 10px;
        }

        .category {
            display: inline-block;
            background: #FCE7F3;
            color: #9D174D;
            border-radius: 7px;
            padding: 6px 9px;
        }

        .stock-badge {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 7px;
            font-weight: 750;
        }

        .stock-low {
            background: #FFE4E6;
            color: #BE123C;
        }

        .stock-ok {
            background: #DCFCE7;
            color: #166534;
        }

        .empty {
            text-align: center;
            padding: 38px 15px;
            color: var(--muted);
            line-height: 1.8;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 750;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            transition: .2s;
        }

        .btn-primary {
            background: linear-gradient(100deg, var(--coral), #F472B6);
            color: white;
            box-shadow: 0 4px 12px #FB718522;
        }

        .btn-primary:hover {
            filter: brightness(.96);
            transform: translateY(-1px);
        }

        .alert {
            padding: 13px 15px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
            line-height: 1.5;
            background: #FFF1F2;
            border: 1px solid #FDA4AF;
            color: #9F1239;
        }

        .footer {
            text-align: center;
            color: var(--muted);
            font-size: 11px;
            padding: 10px;
        }

        /* RESPONSIVE */
        @media (max-width: 950px) {
            .sidebar { width: 195px; }
            .main { padding: 22px; }
        }

        @media (max-width: 650px) {
            .layout { display: block; }

            .sidebar {
                width: 100%;
                padding: 16px;
                border-right: 0;
                border-bottom: 1px solid var(--border);
            }

            .brand { margin-bottom: 3px; }
            .tagline { margin-bottom: 16px; }
            .menu-label, .sidebar-note { display: none; }

            .sidebar nav {
                display: flex;
                gap: 7px;
                overflow-x: auto;
            }

            .nav {
                white-space: nowrap;
                padding: 10px 12px;
                margin-bottom: 0;
            }

            .main { padding: 17px; }

            .stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .stat { padding: 16px; }
            .stat-value { font-size: 24px; }
            .panel { padding: 16px; }

            .notification-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .notification-item {
                align-items: flex-start;
                flex-direction: column;
            }

            .profile-info { display: none; }
        }
    </style>
</head>

<body>
<div class="layout">

    <aside class="sidebar">
        <div class="brand">stok<span>ly.</span></div>
        <div class="tagline">Smart inventory, less chaos.</div>

        <div class="menu-label">MENU UTAMA</div>

        <nav>
            <a class="nav active" href="index.php">▦ &nbsp; Dashboard</a>
            <a class="nav" href="barang.php">▤ &nbsp; Data Barang</a>
            <a class="nav" href="transaksi.php">⇄ &nbsp; Transaksi Stok</a>
            <a class="nav" href="laporan.php">▧ &nbsp; Laporan Stok</a>
            <a class="nav" href="logout.php">↪ &nbsp; Keluar Akun</a>
        </nav>

        <div class="sidebar-note">
            <strong>✦ Stokly Workspace</strong>
            Kelola inventaris lebih mudah, rapi, dan teratur.
        </div>
    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <div class="eyebrow">INVENTORY MANAGEMENT</div>
                <h1>Dashboard</h1>
                <p class="subtitle">
                    Selamat datang kembali, <?= e($namaPengguna) ?>!
                    Berikut ringkasan inventaris kamu.
                </p>
            </div>

            <div class="profile">
                <div class="profile-info">
                    <strong><?= e($namaPengguna) ?></strong>
                    <span>Administrator</span>
                </div>
                <div class="avatar"><?= e($hurufNama) ?></div>
            </div>
        </header>

        <?php if ($errorDatabase): ?>
            <div class="alert">
                Data belum dapat dibaca dari basis data.
                Pastikan MySQL menyala dan tabel barang sudah tersedia.
            </div>
        <?php endif; ?>

        <!-- RINGKASAN -->
        <section class="stats">

            <div class="stat">
                <div class="stat-label">TOTAL JENIS BARANG</div>
                <div class="stat-value purple">
                    <?= number_format($totalBarang) ?>
                </div>
                <div class="stat-desc">
                    Barang terdaftar di database
                </div>
            </div>

            <div class="stat">
                <div class="stat-label">TOTAL STOK</div>
                <div class="stat-value pink">
                    <?= number_format($totalStok) ?>
                </div>
                <div class="stat-desc">
                    Akumulasi seluruh stok barang
                </div>
            </div>

            <div class="stat">
                <div class="stat-label">STOK MENIPIS</div>
                <div class="stat-value green">
                    <?= number_format($stokRendah) ?>
                </div>
                <div class="stat-desc">
                    Barang dengan stok 5 atau kurang
                </div>
            </div>

        </section>

        <!-- NOTIFIKASI STOK -->
        <?php if (!$errorDatabase): ?>
            <section class="notification-panel">

                <div class="notification-heading">
                    <div>
                        <h2>⚠️ Notifikasi Stok Barang</h2>
                        <p>
                            Pantau barang yang perlu segera diperiksa.
                        </p>
                    </div>

                    <a href="barang.php">Kelola Barang →</a>
                </div>

                <?php if (count($daftarPeringatan) > 0): ?>

                    <div class="notification-list">

                        <?php foreach ($daftarPeringatan as $peringatan): ?>

                            <div class="notification-item">

                                <div class="notification-info">
                                    <strong>
                                        <?= e($peringatan['nama_barang']) ?>
                                    </strong>

                                    <span>
                                        <?= e($peringatan['kode_barang']) ?>
                                    </span>
                                </div>

                                <?php if ((int)$peringatan['stok'] === 0): ?>

                                    <span class="warning-badge empty-stock">
                                        STOK HABIS
                                    </span>

                                <?php else: ?>

                                    <span class="warning-badge low-stock">
                                        Tersisa
                                        <?= number_format((int)$peringatan['stok']) ?>
                                        <?= e($peringatan['satuan']) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="notification-empty">
                        ✓ Semua stok barang dalam kondisi aman.
                    </div>

                <?php endif; ?>

            </section>
        <?php endif; ?>

        <!-- DAFTAR BARANG TERBARU -->
        <section class="panel">

            <div class="panel-heading">
                <div>
                    <h2>Barang Terbaru</h2>
                    <p>
                        Lima barang terakhir yang ditambahkan ke inventaris.
                    </p>
                </div>

                <a href="barang.php" class="btn btn-primary">
                    + Kelola Barang
                </a>
            </div>

            <div class="table-wrap">
                <table>

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Barang</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Harga</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($daftarBarang) > 0): ?>

                        <?php $no = 1; ?>

                        <?php foreach ($daftarBarang as $barang): ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <div class="item-name">
                                        <?= e($barang['nama_barang']) ?>
                                    </div>

                                    <div class="item-code">
                                        <?= e($barang['kode_barang']) ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="category">
                                        <?= e($barang['kategori']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="stock-badge <?= (int)$barang['stok'] <= 5 ? 'stock-low' : 'stock-ok' ?>">
                                        <?= number_format((int)$barang['stok']) ?>
                                        <?= e($barang['satuan']) ?>
                                    </span>
                                </td>

                                <td>
                                    Rp <?= number_format((float)$barang['harga'], 0, ',', '.') ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5">
                                <div class="empty">
                                    <div style="font-size:30px">📦</div>
                                    <strong>Belum ada barang</strong><br>
                                    Silakan tambahkan barang melalui menu Data Barang.
                                </div>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </section>

        <div class="footer">
            © <?= date('Y') ?> Stokly Inventory System · Manage smart, grow better.
        </div>

    </main>
</div>
</body>
</html>
