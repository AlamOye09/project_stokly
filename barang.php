
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

function redirectStatus($status) {
    header("Location: barang.php?status=" . urlencode($status));
    exit;
}

$error = "";
$editBarang = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $error = "Permintaan tidak valid. Silakan muat ulang halaman.";
    } else {
        $aksi = $_POST["aksi"] ?? "";

        try {
            if ($aksi === "tambah" || $aksi === "edit") {
                $kode = trim($_POST["kode_barang"] ?? "");
                $nama = trim($_POST["nama_barang"] ?? "");
                $kategori = trim($_POST["kategori"] ?? "");
                $stok = filter_var($_POST["stok"] ?? null, FILTER_VALIDATE_INT);
                $satuan = trim($_POST["satuan"] ?? "pcs");
                $harga = $_POST["harga"] ?? "";

                if (
                    $kode === "" || $nama === "" || $kategori === "" ||
                    $stok === false || $stok === null || $stok < 0 ||
                    $satuan === "" || !is_numeric($harga) || (float)$harga < 0
                ) {
                    throw new Exception("Lengkapi semua data dengan benar. Stok dan harga tidak boleh negatif.");
                }

                $harga = (float)$harga;

                if ($aksi === "tambah") {
                    $stmt = $conn->prepare(
                        "INSERT INTO barang
                        (kode_barang, nama_barang, kategori, stok, satuan, harga)
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param(
                        "sssisd", $kode, $nama, $kategori, $stok, $satuan, $harga
                    );
                    $stmt->execute();
                    $stmt->close();

                    redirectStatus("added");
                } else {
                    $id = (int)($_POST["id"] ?? 0);

                    if ($id <= 0) {
                        throw new Exception("ID barang tidak valid.");
                    }

                    $stmt = $conn->prepare(
                        "UPDATE barang
                        SET kode_barang=?, nama_barang=?, kategori=?,
                            stok=?, satuan=?, harga=?
                        WHERE id=?"
                    );
                    $stmt->bind_param(
                        "sssidsi", $kode, $nama, $kategori,
                        $stok, $satuan, $harga, $id
                    );
                    $stmt->execute();
                    $stmt->close();

                    redirectStatus("updated");
                }
            } elseif ($aksi === "hapus") {
                $id = (int)($_POST["id"] ?? 0);

                if ($id <= 0) {
                    throw new Exception("ID barang tidak valid.");
                }

                $stmt = $conn->prepare("DELETE FROM barang WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                redirectStatus("deleted");
            }
        } catch (mysqli_sql_exception $ex) {
            if ((int)$ex->getCode() === 1062) {
                $error = "Kode barang sudah digunakan. Gunakan kode yang berbeda.";
            } elseif ((int)$ex->getCode() === 1451) {
                $error = "Barang tidak dapat dihapus karena sudah memiliki riwayat transaksi.";
            } else {
                $error = "Terjadi kesalahan saat menyimpan data. Periksa input dan database.";
            }
        } catch (Exception $ex) {
            $error = $ex->getMessage();
        }
    }
}

if (isset($_GET["edit"]) && ctype_digit((string)$_GET["edit"])) {
    $idEdit = (int)$_GET["edit"];

    $stmt = $conn->prepare("SELECT * FROM barang WHERE id=?");
    $stmt->bind_param("i", $idEdit);
    $stmt->execute();
    $editBarang = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$cari = trim($_GET["cari"] ?? "");

if ($cari !== "") {
    $keyword = "%" . $cari . "%";
    $stmt = $conn->prepare(
        "SELECT * FROM barang
         WHERE kode_barang LIKE ? OR nama_barang LIKE ? OR kategori LIKE ?
         ORDER BY id DESC"
    );
    $stmt->bind_param("sss", $keyword, $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM barang ORDER BY id DESC");
}

$totalBarang = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM barang"
)->fetch_assoc()["total"];

$totalStok = (int)$conn->query(
    "SELECT COALESCE(SUM(stok), 0) AS total FROM barang"
)->fetch_assoc()["total"];

$stokRendah = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM barang WHERE stok <= 5"
)->fetch_assoc()["total"];

$status = $_GET["status"] ?? "";

$pesanSukses = [
    "added" => "Barang baru berhasil ditambahkan!",
    "updated" => "Data barang berhasil diperbarui!",
    "deleted" => "Barang berhasil dihapus."
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Barang | Stokly</title>

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
    background: radial-gradient(ellipse at top right, #FFE0E9, transparent 42%), var(--bg);
    color: var(--text);
}

.layout {
    display: flex;
    min-height: 100vh;
}

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

.form-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
}

.field label {
    display: block;
    color: #795766;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 8px;
}

.field input,
.field select,
.search-input {
    width: 100%;
    padding: 12px 13px;
    background: #FFFAFC;
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text);
    outline: none;
    font: inherit;
    font-size: 13px;
    transition: .2s;
}

.field input:focus,
.field select:focus,
.search-input:focus {
    border-color: var(--coral);
    box-shadow: 0 0 0 3px #FB71851A;
}

.field select option {
    background: white;
    color: var(--text);
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 18px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border: 0;
    border-radius: 10px;
    padding: 11px 15px;
    font-size: 12px;
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

.btn-secondary {
    background: #FFF1F5;
    color: var(--pink-dark);
    border: 1px solid var(--border);
}

.btn-secondary:hover {
    background: var(--soft);
}

.btn-edit {
    background: #FCE7F3;
    color: #9D174D;
    padding: 8px 10px;
}

.btn-edit:hover {
    background: #FBCFE8;
}

.btn-delete {
    background: #FFF1F2;
    color: #BE123C;
    padding: 8px 10px;
}

.btn-delete:hover {
    background: #FFE4E6;
}

.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 18px;
}

.toolbar p {
    color: var(--muted);
    font-size: 12px;
}

.search-form {
    display: flex;
    gap: 8px;
    width: min(100%, 390px);
}

.search-input {
    min-width: 0;
}

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

.actions {
    display: flex;
    align-items: center;
    gap: 7px;
}

.inline-form {
    display: inline;
}

.alert {
    padding: 13px 15px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 18px;
    line-height: 1.5;
}

.alert-success {
    background: #F0FDF4;
    border: 1px solid #BBF7D0;
    color: #166534;
}

.alert-error {
    background: #FFF1F2;
    border: 1px solid #FDA4AF;
    color: #9F1239;
}

.empty {
    text-align: center;
    padding: 38px 15px;
    color: var(--muted);
    line-height: 1.8;
}

.footer {
    text-align: center;
    color: var(--muted);
    font-size: 11px;
    padding: 10px;
}

@media (max-width: 950px) {
    .sidebar { width: 195px; }
    .main { padding: 22px; }
    .form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
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
    .stats { grid-template-columns: 1fr; gap: 10px; }
    .stat { padding: 16px; }
    .stat-value { font-size: 24px; }
    .form-grid { grid-template-columns: 1fr; }
    .panel { padding: 16px; }
    .toolbar {
        align-items: stretch;
        flex-direction: column;
    }
    .search-form { width: 100%; }
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
            <a class="nav active" href="barang.php">▤ &nbsp; Data Barang</a>
            <a class="nav" href="laporan.php">▧ &nbsp; Laporan Stok</a>
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
                <h1>Data Barang</h1>
                <p class="subtitle">Kelola semua barang dalam satu tempat.</p>
            </div>
            <div class="avatar">S</div>
        </header>

        <?php if (isset($pesanSukses[$status])): ?>
            <div class="alert alert-success">
                ✓ <?= e($pesanSukses[$status]) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <section class="stats">
            <div class="stat">
                <div class="stat-label">TOTAL JENIS BARANG</div>
                <div class="stat-value purple"><?= number_format($totalBarang) ?></div>
                <div class="stat-desc">Barang terdaftar di database</div>
            </div>
            <div class="stat">
                <div class="stat-label">TOTAL UNIT STOK</div>
                <div class="stat-value pink"><?= number_format($totalStok) ?></div>
                <div class="stat-desc">Akumulasi seluruh stok barang</div>
            </div>
            <div class="stat">
                <div class="stat-label">STOK MENIPIS</div>
                <div class="stat-value green"><?= number_format($stokRendah) ?></div>
                <div class="stat-desc">Barang dengan stok 5 atau kurang</div>
            </div>
        </section>

        <section class="panel" id="form-barang">
            <div class="panel-heading">
                <div>
                    <h2><?= $editBarang ? "Edit Barang" : "Tambah Barang Baru" ?></h2>
                    <p>
                        <?= $editBarang
                            ? "Perbarui informasi barang yang dipilih."
                            : "Masukkan detail barang yang ingin dicatat." ?>
                    </p>
                </div>
            </div>

            <form method="POST" action="barang.php<?= $editBarang ? '?edit=' . (int)$editBarang['id'] : '' ?>">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                <input type="hidden" name="aksi" value="<?= $editBarang ? 'edit' : 'tambah' ?>">

                <?php if ($editBarang): ?>
                    <input type="hidden" name="id" value="<?= (int)$editBarang['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="field">
                        <label for="kode_barang">Kode Barang *</label>
                        <input id="kode_barang" name="kode_barang" type="text"
                               maxlength="20" placeholder="Contoh: BRG004"
                               value="<?= e($editBarang['kode_barang'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label for="nama_barang">Nama Barang *</label>
                        <input id="nama_barang" name="nama_barang" type="text"
                               maxlength="100" placeholder="Nama barang"
                               value="<?= e($editBarang['nama_barang'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label for="kategori">Kategori *</label>
                        <input id="kategori" name="kategori" type="text"
                               maxlength="50" placeholder="Contoh: Aksesoris"
                               value="<?= e($editBarang['kategori'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label for="stok">Jumlah Stok *</label>
                        <input id="stok" name="stok" type="number" min="0" step="1"
                               placeholder="0"
                               value="<?= e($editBarang['stok'] ?? '0') ?>" required>
                    </div>

                    <div class="field">
                        <label for="satuan">Satuan *</label>
                        <?php
                        $pilihanSatuan = ["pcs", "unit", "buah", "box", "pack", "meter", "roll", "set"];
                        $satuanAktif = $editBarang['satuan'] ?? 'pcs';
                        ?>
                        <select id="satuan" name="satuan" required>
                            <?php foreach ($pilihanSatuan as $pilihan): ?>
                                <option value="<?= e($pilihan) ?>"
                                    <?= $satuanAktif === $pilihan ? 'selected' : '' ?>>
                                    <?= e(ucfirst($pilihan)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="harga">Harga per Satuan (Rp) *</label>
                        <input id="harga" name="harga" type="number" min="0" step="0.01"
                               placeholder="Contoh: 250000"
                               value="<?= e($editBarang['harga'] ?? '0') ?>" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">
                        <?= $editBarang ? "Simpan Perubahan" : "Tambahkan Barang" ?>
                    </button>

                    <?php if ($editBarang): ?>
                        <a class="btn btn-secondary" href="barang.php">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Daftar Inventaris</h2>
                    <p>Data barang yang tersimpan di database Stokly.</p>
                </div>
            </div>

            <div class="toolbar">
                <p>
                    Menampilkan <?= number_format($result->num_rows) ?>
                    barang<?= $cari !== "" ? " untuk pencarian: " . e($cari) : "" ?>
                </p>

                <form class="search-form" method="GET" action="barang.php">
                    <input class="search-input" type="search" name="cari"
                           placeholder="Cari nama, kode, kategori..."
                           value="<?= e($cari) ?>">
                    <button class="btn btn-primary" type="submit">Cari</button>
                    <?php if ($cari !== ""): ?>
                        <a class="btn btn-secondary" href="barang.php">Reset</a>
                    <?php endif; ?>
                </form>
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
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($barang = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="item-name"><?= e($barang["nama_barang"]) ?></div>
                                    <div class="item-code"><?= e($barang["kode_barang"]) ?></div>
                                </td>
                                <td><span class="category"><?= e($barang["kategori"]) ?></span></td>
                                <td>
                                    <span class="stock-badge <?= (int)$barang["stok"] <= 5 ? 'stock-low' : 'stock-ok' ?>">
                                        <?= number_format((int)$barang["stok"]) ?>
                                        <?= e($barang["satuan"]) ?>
                                    </span>
                                </td>
                                <td>Rp <?= number_format((float)$barang["harga"], 0, ",", ".") ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-edit"
                                           href="barang.php?edit=<?= (int)$barang["id"] ?>#form-barang">Edit</a>

                                        <form class="inline-form" method="POST" action="barang.php"
                                              onsubmit="return confirm('Yakin ingin menghapus barang ini? Tindakan ini tidak dapat dibatalkan.');">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= (int)$barang["id"] ?>">
                                            <button class="btn btn-delete" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty">
                                    <div style="font-size:30px">📦</div>
                                    <strong>Belum ada barang yang ditemukan</strong><br>
                                    Coba kata kunci lain atau tambahkan barang baru.
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="footer">
            © <?= date("Y") ?> Stokly Inventory System · Manage smart, grow better.
        </div>
    </main>
</div>
</body>
</html>
