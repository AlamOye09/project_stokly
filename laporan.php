
<?php
require_once __DIR__ . '/includes/auth.php';
wajibMasuk();
require_once __DIR__ . '/config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$totalJenis = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM barang"
)->fetch_assoc()["total"];

$totalUnit = (int)$conn->query(
    "SELECT COALESCE(SUM(stok),0) AS total FROM barang"
)->fetch_assoc()["total"];

$totalNilai = (float)$conn->query(
    "SELECT COALESCE(SUM(stok * harga),0) AS total FROM barang"
)->fetch_assoc()["total"];

$totalRendah = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM barang WHERE stok <= 5"
)->fetch_assoc()["total"];

$dataBarang = $conn->query(
    "SELECT kode_barang, nama_barang, kategori, stok, satuan, harga,
            (stok * harga) AS nilai_persediaan
     FROM barang
     ORDER BY nama_barang ASC"
);

$riwayat = $conn->query(
    "SELECT t.jenis, t.jumlah, t.keterangan, t.created_at,
            b.kode_barang, b.nama_barang, b.satuan
     FROM transaksi_stok t
     INNER JOIN barang b ON t.barang_id = b.id
     ORDER BY t.id DESC
     LIMIT 20"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Stok | Stokly</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

:root {
    --pink: #F9A8D4;
    --pink-dark: #BE185D;
    --coral: #FB7185;
    --bg: #FFF1F5;
    --white: #FFFFFF;
    --text: #3F2933;
    --muted: #92717E;
    --border: #FBCFE0;
    --green: #16845B;
    --green-bg: #E4F8EE;
    --red: #D93652;
    --red-bg: #FFE4E8;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
}

.layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 240px;
    flex-shrink: 0;
    padding: 28px 18px;
    background: var(--white);
    border-right: 1px solid var(--border);
}

.brand {
    font-size: 29px;
    font-weight: 800;
    margin: 0 0 7px 8px;
    color: var(--text);
}

.brand span {
    color: var(--coral);
}

.tagline {
    font-size: 12px;
    color: var(--muted);
    margin: 0 0 35px 8px;
}

.menu-label {
    font-size: 10px;
    letter-spacing: 2px;
    color: var(--muted);
    margin: 24px 8px 12px;
}

.nav {
    display: block;
    padding: 13px 14px;
    margin-bottom: 8px;
    border-radius: 12px;
    color: var(--muted);
    text-decoration: none;
    font-size: 14px;
    transition: .2s;
}

.nav:hover,
.nav.active {
    background: linear-gradient(110deg, var(--pink), var(--coral));
    color: var(--white);
    font-weight: 700;
}

.main {
    flex: 1;
    min-width: 0;
    padding: 30px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 25px;
}

.eyebrow {
    font-size: 11px;
    letter-spacing: 2px;
    color: var(--pink-dark);
    font-weight: 700;
    margin-bottom: 8px;
}

h1 {
    font-size: 30px;
    letter-spacing: -1px;
}

.subtitle {
    font-size: 13px;
    color: var(--muted);
    margin-top: 7px;
    line-height: 1.6;
}

.avatar {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--pink), var(--coral));
    color: white;
    font-weight: 800;
    flex-shrink: 0;
}

.stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 22px;
}

.stat,
.panel {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 21px;
    box-shadow: 0 5px 18px rgba(190, 24, 93, .045);
}

.stat-label {
    font-size: 12px;
    color: var(--muted);
}

.stat-value {
    font-size: 27px;
    font-weight: 800;
    margin: 12px 0 5px;
    overflow-wrap: anywhere;
}

.pink {
    color: var(--pink-dark);
}

.coral {
    color: var(--coral);
}

.green {
    color: var(--green);
}

.red {
    color: var(--red);
}

.stat-desc {
    font-size: 11px;
    color: var(--muted);
    line-height: 1.5;
}

.panel {
    margin-bottom: 22px;
    min-width: 0;
}

.panel h2 {
    font-size: 18px;
    margin-bottom: 7px;
}

.hint {
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 18px;
    line-height: 1.6;
}

.actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.btn {
    display: inline-block;
    border: 0;
    border-radius: 11px;
    padding: 12px 16px;
    background: linear-gradient(110deg, var(--pink), var(--coral));
    color: white;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: .2s;
}

.btn:hover {
    transform: translateY(-1px);
    filter: brightness(.98);
}

.btn.secondary {
    background: var(--white);
    color: var(--pink-dark);
    border: 1px solid var(--border);
}

.table-wrap {
    overflow-x: auto;
    margin-top: 14px;
}

table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

th {
    text-align: left;
    color: var(--muted);
    background: #FFFAFC;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 13px 11px;
    border-bottom: 1px solid var(--border);
}

td {
    font-size: 12px;
    padding: 14px 11px;
    border-bottom: 1px solid #FCE7EF;
}

tbody tr:hover {
    background: #FFF7FA;
}

.badge {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 11px;
}

.in {
    background: var(--green-bg);
    color: var(--green);
}

.out {
    background: var(--red-bg);
    color: var(--red);
}

.low {
    color: var(--red);
    font-weight: 700;
}

.ok {
    color: var(--green);
    font-weight: 700;
}

.empty {
    text-align: center;
    color: var(--muted);
    padding: 25px;
}

.footer {
    text-align: center;
    color: var(--muted);
    font-size: 11px;
    padding: 10px;
}

@media (max-width: 800px) {
    .sidebar {
        width: 200px;
    }

    .main {
        padding: 20px;
    }
}

@media (max-width: 600px) {
    .layout {
        display: block;
    }

    .sidebar {
        width: 100%;
        padding: 15px;
    }

    .tagline {
        margin-bottom: 15px;
    }

    .menu-label {
        display: none;
    }

    .sidebar nav {
        display: flex;
        gap: 6px;
        overflow-x: auto;
    }

    .nav {
        white-space: nowrap;
        margin: 0;
        padding: 10px;
    }

    .main {
        padding: 15px;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .panel,
    .stat {
        padding: 16px;
    }

    h1 {
        font-size: 25px;
    }
}

@media print {
    @page {
        margin: 15mm;
    }

    body {
        background: white;
        color: #222;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .sidebar,
    .actions {
        display: none !important;
    }

    .layout {
        display: block;
    }

    .main {
        padding: 0;
    }

    .panel,
    .stat {
        background: white;
        color: #222;
        border: 1px solid #ddd;
        box-shadow: none;
        break-inside: avoid;
    }

    .stat-label,
    .stat-desc,
    .hint,
    .subtitle {
        color: #555;
    }

    th,
    td {
        color: #222;
        border-color: #ddd;
    }

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .table-wrap {
        overflow: visible;
    }

    table {
        white-space: normal;
    }

    .footer {
        color: #555;
    }
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
        <a class="nav" href="index.php">▦ &nbsp; Dashboard</a>
        <a class="nav" href="barang.php">▤ &nbsp; Data Barang</a>
        <a class="nav" href="transaksi.php">↕ &nbsp; Transaksi Stok</a>
        <a class="nav active" href="laporan.php">▧ &nbsp; Laporan Stok</a>
    </nav>
</aside>

<main class="main">

<header class="topbar">
    <div>
        <div class="eyebrow">INVENTORY REPORT</div>
        <h1>Laporan Stok 📊</h1>
        <p class="subtitle">
            Pantau jumlah barang dan nilai persediaan saat ini.
        </p>
    </div>

    <div class="avatar">S</div>
</header>

<div class="actions">
    <button class="btn" type="button" onclick="window.print()">
        Cetak / Simpan PDF
    </button>

    <a class="btn secondary" href="transaksi.php">
        ＋ Catat Transaksi
    </a>
</div>

<section class="stats">

    <div class="stat">
        <div class="stat-label">TOTAL JENIS BARANG</div>
        <div class="stat-value pink">
            <?= number_format($totalJenis) ?>
        </div>
        <div class="stat-desc">Jumlah barang terdaftar</div>
    </div>

    <div class="stat">
        <div class="stat-label">TOTAL UNIT PERSEDIAAN</div>
        <div class="stat-value coral">
            <?= number_format($totalUnit) ?>
        </div>
        <div class="stat-desc">Total unit seluruh barang</div>
    </div>

    <div class="stat">
        <div class="stat-label">TOTAL NILAI PERSEDIAAN</div>
        <div class="stat-value green">
            Rp <?= number_format($totalNilai, 0, ",", ".") ?>
        </div>
        <div class="stat-desc">
            Jumlah stok dikalikan harga satuan
        </div>
    </div>

    <div class="stat">
        <div class="stat-label">BARANG STOK MENIPIS</div>
        <div class="stat-value red">
            <?= number_format($totalRendah) ?>
        </div>
        <div class="stat-desc">
            Barang dengan stok 5 unit atau kurang
        </div>
    </div>

</section>

<section class="panel">
    <h2>📦 Rincian Persediaan</h2>
    <p class="hint">
        Nilai persediaan dihitung berdasarkan stok saat ini
        dikalikan harga satuan yang tersimpan.
    </p>

    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Harga Satuan</th>
                <th>Nilai Persediaan</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($dataBarang && $dataBarang->num_rows > 0): ?>

            <?php while ($b = $dataBarang->fetch_assoc()): ?>
                <tr>
                    <td><?= e($b["kode_barang"]) ?></td>

                    <td>
                        <strong><?= e($b["nama_barang"]) ?></strong>
                    </td>

                    <td><?= e($b["kategori"]) ?></td>

                    <td class="<?= (int)$b["stok"] <= 5 ? 'low' : 'ok' ?>">
                        <?= number_format((int)$b["stok"]) ?>
                        <?= e($b["satuan"]) ?>
                    </td>

                    <td>
                        Rp <?= number_format((float)$b["harga"], 0, ",", ".") ?>
                    </td>

                    <td>
                        <strong>
                            Rp <?= number_format(
                                (float)$b["nilai_persediaan"],
                                0,
                                ",",
                                "."
                            ) ?>
                        </strong>
                    </td>
                </tr>
            <?php endwhile; ?>

            <tr>
                <td colspan="5" style="text-align:right">
                    <strong>TOTAL NILAI PERSEDIAAN</strong>
                </td>

                <td>
                    <strong class="green">
                        Rp <?= number_format($totalNilai, 0, ",", ".") ?>
                    </strong>
                </td>
            </tr>

        <?php else: ?>

            <tr>
                <td colspan="6" class="empty">
                    Belum ada data barang.
                </td>
            </tr>

        <?php endif; ?>
        </tbody>
    </table>
    </div>
</section>

<section class="panel">
    <h2>◷ Riwayat Transaksi Terbaru</h2>
    <p class="hint">
        Menampilkan maksimal 20 transaksi terakhir.
    </p>

    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Barang</th>
                <th>Jenis</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($riwayat && $riwayat->num_rows > 0): ?>

            <?php while ($t = $riwayat->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?= e(date(
                            "d/m/Y H:i",
                            strtotime($t["created_at"])
                        )) ?>
                    </td>

                    <td>
                        <strong><?= e($t["nama_barang"]) ?></strong><br>
                        <span style="color:var(--muted)">
                            <?= e($t["kode_barang"]) ?>
                        </span>
                    </td>

                    <td>
                        <?php if ($t["jenis"] === "masuk"): ?>
                            <span class="badge in">↑ Masuk</span>
                        <?php else: ?>
                            <span class="badge out">↓ Keluar</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= number_format((int)$t["jumlah"]) ?>
                        <?= e($t["satuan"]) ?>
                    </td>

                    <td><?= e($t["keterangan"] ?: "-") ?></td>
                </tr>
            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="5" class="empty">
                    Belum ada transaksi yang tercatat.
                </td>
            </tr>

        <?php endif; ?>
        </tbody>
    </table>
    </div>
</section>

<div class="footer">
    © <?= date("Y") ?> Stokly Inventory System
</div>

</main>
</div>
</body>
</html>
