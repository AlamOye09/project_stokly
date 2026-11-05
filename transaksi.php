
<?php
require_once __DIR__ . '/includes/auth.php';
wajibMasuk();
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (
            !isset($_POST["csrf_token"]) ||
            !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
        ) {
            throw new Exception("Form tidak valid. Muat ulang halaman.");
        }

        $barangId = filter_var(
            $_POST["barang_id"] ?? null,
            FILTER_VALIDATE_INT
        );
        $jenis = $_POST["jenis"] ?? "";
        $jumlah = filter_var(
            $_POST["jumlah"] ?? null,
            FILTER_VALIDATE_INT
        );
        $keterangan = trim($_POST["keterangan"] ?? "");

        if (!$barangId || $barangId < 1) {
            throw new Exception("Pilih barang terlebih dahulu.");
        }

        if (!in_array($jenis, ["masuk", "keluar"], true)) {
            throw new Exception("Pilih jenis transaksi yang benar.");
        }

        if ($jumlah === false || $jumlah === null || $jumlah < 1) {
            throw new Exception("Jumlah transaksi minimal 1.");
        }

        if (strlen($keterangan) > 255) {
            throw new Exception("Keterangan maksimal 255 karakter.");
        }

        $conn->begin_transaction();

        $stmt = $conn->prepare(
            "SELECT id, stok FROM barang WHERE id = ? FOR UPDATE"
        );
        $stmt->bind_param("i", $barangId);
        $stmt->execute();
        $barang = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$barang) {
            throw new Exception("Barang tidak ditemukan.");
        }

        $stokSekarang = (int)$barang["stok"];

        if ($jenis === "keluar" && $jumlah > $stokSekarang) {
            throw new Exception(
                "Stok tidak mencukupi. Stok tersedia: " .
                $stokSekarang . "."
            );
        }

        if ($jenis === "masuk") {
            $stokBaru = $stokSekarang + $jumlah;

            if ($stokBaru > 2147483647) {
                throw new Exception(
                    "Jumlah stok melebihi batas yang didukung."
                );
            }
        } else {
            $stokBaru = $stokSekarang - $jumlah;
        }

        $stmt = $conn->prepare(
            "UPDATE barang SET stok = ? WHERE id = ?"
        );
        $stmt->bind_param("ii", $stokBaru, $barangId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "INSERT INTO transaksi_stok
             (barang_id, jenis, jumlah, keterangan)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "isis",
            $barangId,
            $jenis,
            $jumlah,
            $keterangan
        );
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        header("Location: transaksi.php?status=success");
        exit;

    } catch (Throwable $ex) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
            // Tidak ada transaksi aktif yang perlu dibatalkan.
        }

        if ($ex instanceof mysqli_sql_exception) {
            $error = "Transaksi gagal disimpan. Periksa database dan coba lagi.";
        } else {
            $error = $ex->getMessage();
        }
    }
}

$barangList = $conn->query(
    "SELECT id, kode_barang, nama_barang, stok, satuan
     FROM barang ORDER BY nama_barang ASC"
);

$riwayat = $conn->query(
    "SELECT t.id, t.jenis, t.jumlah, t.keterangan, t.created_at,
            b.kode_barang, b.nama_barang, b.satuan
     FROM transaksi_stok t
     INNER JOIN barang b ON t.barang_id = b.id
     ORDER BY t.id DESC
     LIMIT 100"
);

$totalMasuk = (int)$conn->query(
    "SELECT COALESCE(SUM(jumlah), 0) AS total
     FROM transaksi_stok WHERE jenis = 'masuk'"
)->fetch_assoc()["total"];

$totalKeluar = (int)$conn->query(
    "SELECT COALESCE(SUM(jumlah), 0) AS total
     FROM transaksi_stok WHERE jenis = 'keluar'"
)->fetch_assoc()["total"];

$totalTransaksi = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM transaksi_stok"
)->fetch_assoc()["total"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transaksi Stok | Stokly</title>

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
    color: #FFFFFF;
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
    margin-bottom: 26px;
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
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
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
    font-size: 28px;
    font-weight: 800;
    margin: 12px 0 5px;
}

.pink {
    color: var(--pink-dark);
}

.green {
    color: var(--green);
}

.coral {
    color: var(--coral);
}

.stat-desc {
    font-size: 11px;
    color: var(--muted);
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
    margin-bottom: 20px;
    line-height: 1.6;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}

.field input,
.field select,
.field textarea {
    width: 100%;
    padding: 12px;
    background: #FFFAFC;
    border: 1px solid var(--border);
    border-radius: 11px;
    color: var(--text);
    font-family: inherit;
    font-size: 13px;
    outline: none;
    transition: .2s;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
    border-color: var(--coral);
    box-shadow: 0 0 0 3px rgba(251, 113, 133, .13);
}

.field textarea {
    resize: vertical;
    min-height: 75px;
}

.field select option {
    background: var(--white);
    color: var(--text);
}

.btn {
    display: inline-block;
    border: 0;
    border-radius: 11px;
    padding: 12px 17px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: .2s;
}

.btn:hover {
    transform: translateY(-1px);
    filter: brightness(.98);
}

.primary {
    background: linear-gradient(110deg, var(--pink), var(--coral));
    color: #FFFFFF;
    box-shadow: 0 5px 12px rgba(251, 113, 133, .18);
}

.secondary {
    background: #FFF0F5;
    color: var(--pink-dark);
    border: 1px solid var(--border);
}

.actions {
    margin-top: 18px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.alert {
    padding: 13px 15px;
    border-radius: 11px;
    margin-bottom: 18px;
    font-size: 13px;
    line-height: 1.5;
}

.success {
    background: var(--green-bg);
    border: 1px solid #B7E8D0;
    color: var(--green);
}

.error {
    background: var(--red-bg);
    border: 1px solid #FFC4CE;
    color: var(--red);
}

.table-wrap {
    overflow-x: auto;
    margin-top: 18px;
}

table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

th {
    text-align: left;
    color: var(--muted);
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 13px 11px;
    border-bottom: 1px solid var(--border);
    background: #FFFAFC;
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
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
}

.in {
    background: var(--green-bg);
    color: var(--green);
}

.out {
    background: var(--red-bg);
    color: var(--red);
}

.empty {
    text-align: center;
    color: var(--muted);
    padding: 30px;
}

.footer {
    text-align: center;
    color: var(--muted);
    font-size: 11px;
    padding: 10px;
}

@media (max-width: 850px) {
    .sidebar {
        width: 200px;
    }

    .main {
        padding: 20px;
    }

    .stats {
        grid-template-columns: 1fr;
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

    .form-grid {
        grid-template-columns: 1fr;
    }

    .panel,
    .stat {
        padding: 16px;
    }

    h1 {
        font-size: 25px;
    }

    .topbar {
        align-items: flex-start;
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
        <a class="nav active" href="transaksi.php">↕ &nbsp; Transaksi Stok</a>
        <a class="nav" href="laporan.php">▧ &nbsp; Laporan Stok</a>
    </nav>
</aside>

<main class="main">

<header class="topbar">
    <div>
        <div class="eyebrow">STOCK MOVEMENT</div>
        <h1>Transaksi Stok ↕</h1>
        <p class="subtitle">
            Catat barang masuk dan keluar dengan mudah.
        </p>
    </div>

    <div class="avatar">S</div>
</header>

<?php if (($_GET["status"] ?? "") === "success"): ?>
    <div class="alert success">
        ✓ Transaksi berhasil disimpan dan stok telah diperbarui.
    </div>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<section class="stats">

    <div class="stat">
        <div class="stat-label">TOTAL UNIT MASUK</div>
        <div class="stat-value green">
            <?= number_format($totalMasuk) ?>
        </div>
        <div class="stat-desc">
            Akumulasi seluruh transaksi masuk
        </div>
    </div>

    <div class="stat">
        <div class="stat-label">TOTAL UNIT KELUAR</div>
        <div class="stat-value coral">
            <?= number_format($totalKeluar) ?>
        </div>
        <div class="stat-desc">
            Akumulasi seluruh transaksi keluar
        </div>
    </div>

    <div class="stat">
        <div class="stat-label">TOTAL TRANSAKSI TERCATAT</div>
        <div class="stat-value pink">
            <?= number_format($totalTransaksi) ?>
        </div>
        <div class="stat-desc">
            Jumlah catatan transaksi
        </div>
    </div>

</section>

<section class="panel">
    <h2>＋ Catat Transaksi Baru</h2>
    <p class="hint">
        Pilih barang dan jenis transaksi. Stok akan berubah otomatis
        setelah transaksi berhasil disimpan.
    </p>

    <?php if ($barangList && $barangList->num_rows > 0): ?>

    <form method="POST" action="transaksi.php">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION["csrf_token"]) ?>"
        >

        <div class="form-grid">

            <div class="field">
                <label for="barang_id">Pilih Barang *</label>
                <select id="barang_id" name="barang_id" required>
                    <option value="">-- Pilih barang --</option>

                    <?php while ($b = $barangList->fetch_assoc()): ?>
                        <option value="<?= (int)$b["id"] ?>">
                            <?= e($b["kode_barang"]) ?> —
                            <?= e($b["nama_barang"]) ?>
                            (Stok: <?= (int)$b["stok"] ?>
                            <?= e($b["satuan"]) ?>)
                        </option>
                    <?php endwhile; ?>

                </select>
            </div>

            <div class="field">
                <label for="jenis">Jenis Transaksi *</label>
                <select id="jenis" name="jenis" required>
                    <option value="masuk">Barang Masuk (+ stok)</option>
                    <option value="keluar">Barang Keluar (- stok)</option>
                </select>
            </div>

            <div class="field">
                <label for="jumlah">Jumlah *</label>
                <input
                    id="jumlah"
                    type="number"
                    name="jumlah"
                    min="1"
                    step="1"
                    placeholder="Masukkan jumlah"
                    required
                >
            </div>

            <div class="field">
                <label for="keterangan">Keterangan</label>
                <textarea
                    id="keterangan"
                    name="keterangan"
                    maxlength="255"
                    placeholder="Contoh: pembelian stok atau penjualan barang"
                ></textarea>
            </div>

        </div>

        <div class="actions">
            <button class="btn primary" type="submit">
                Simpan Transaksi
            </button>

            <a class="btn secondary" href="barang.php">
                Lihat Data Barang
            </a>
        </div>
    </form>

    <?php else: ?>

        <p class="hint">
            Belum ada barang. Tambahkan barang terlebih dahulu di halaman Data Barang.
        </p>

        <a class="btn primary" href="barang.php">
            ＋ Tambah Barang
        </a>

    <?php endif; ?>
</section>

<section class="panel">
    <h2>◷ Riwayat Transaksi</h2>
    <p class="hint">
        Maksimal 100 transaksi terbaru ditampilkan.
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
                        Belum ada transaksi. Catat transaksi pertamamu di atas.
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
