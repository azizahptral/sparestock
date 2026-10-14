
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

/* =========================
   PENCARIAN SPAREPART
========================= */
$search = trim($_GET['q'] ?? '');

/* =========================
   RINGKASAN SELURUH INVENTARIS
========================= */
$summaryStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_products,
        COALESCE(SUM(stock), 0) AS total_stock,
        COALESCE(
            SUM(CASE WHEN stock <= min_stock THEN 1 ELSE 0 END),
            0
        ) AS low_stock_count
    FROM products
");

$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

$totalProducts = (int) $summary['total_products'];
$totalStock = (int) $summary['total_stock'];
$lowStockCount = (int) $summary['low_stock_count'];

/* =========================
   DAFTAR STOK MENIPIS
========================= */
$lowStockStmt = $pdo->query("
    SELECT id, code, name, category, stock, min_stock
    FROM products
    WHERE stock <= min_stock
    ORDER BY name ASC
");

$lowStockProducts = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   AMBIL DATA BERDASARKAN PENCARIAN
========================= */
$sql = "
    SELECT
        id,
        code,
        name,
        category,
        stock,
        purchase_price,
        selling_price,
        min_stock
    FROM products
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE code LIKE :code
           OR name LIKE :name
           OR category LIKE :category
    ";

    $keyword = '%' . $search . '%';

    $params[':code'] = $keyword;
    $params[':name'] = $keyword;
    $params[':category'] = $keyword;
}

$sql .= "
    ORDER BY
        CASE
            WHEN stock <= min_stock THEN 0
            ELSE 1
        END ASC,
        name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   FORMAT RUPIAH
========================= */
function rupiah($amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Data Sparepart - SpareStock</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .products-page {
            max-width: 1400px;
            margin: 30px auto;
            padding: 10px;
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 24px;
        }

        .products-header h1 {
            margin: 0 0 10px;
            color: #2563eb;
            font-size: 32px;
        }

        .products-header p {
            margin: 7px 0;
            color: #64748b;
        }

        .stock-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .stock-summary-card {
            padding: 18px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        }

        .stock-summary-card p {
            margin: 0 0 8px;
            color: #64748b;
            font-size: 14px;
        }

        .stock-summary-card strong {
            font-size: 27px;
            color: #1e293b;
        }

        .stock-summary-card.total {
            border-left: 4px solid #2563eb;
        }

        .stock-summary-card.warning {
            border-left: 4px solid #dc2626;
        }

        .stock-alert {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            margin-bottom: 22px;
            padding: 18px;
            border: 1px solid #fecaca;
            border-radius: 12px;
            background: #fef2f2;
            color: #991b1b;
        }

        .stock-alert-icon {
            font-size: 25px;
            line-height: 1.2;
        }

        .stock-alert h3 {
            margin: 0 0 7px;
            font-size: 16px;
        }

        .stock-alert p {
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
        }

        .stock-alert-list {
            margin-top: 10px !important;
            padding-left: 20px;
        }

        .stock-alert-list li {
            margin-bottom: 4px;
        }

        .stock-alert.safe-alert {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .products-card {
            width: 100%;
            padding: 18px;
            overflow: hidden;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(15, 23, 42, 0.05);
            box-sizing: border-box;
        }

        .products-card-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }

        .products-card-heading h2 {
            margin: 0;
            color: #1e293b;
            font-size: 18px;
        }

        .products-card-heading span {
            color: #64748b;
            font-size: 13px;
        }

        /* FORM PENCARIAN */
        .products-search {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }

        .products-search input {
            flex: 1;
            min-width: 200px;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
        }

        .products-search input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .products-search button,
        .products-search .reset-search {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .products-search button {
            background: #2563eb;
            color: white;
        }

        .products-search button:hover {
            background: #1d4ed8;
        }

        .products-search .reset-search {
            background: #e2e8f0;
            color: #334155;
        }

        .search-result-info {
            margin: 0 0 14px;
            color: #64748b;
            font-size: 14px;
        }

        .products-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        .products-table th,
        .products-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        .products-table th {
            background: #eff6ff;
            color: #1e40af;
        }

        .products-table tbody tr:hover {
            background: #f8fafc;
        }

        .products-table tbody tr.low-stock-row {
            background: #fff7f7;
        }

        .products-table tbody tr.low-stock-row:hover {
            background: #feecec;
        }

        .stock-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .stock-low {
            background: #fee2e2;
            color: #b91c1c;
        }

        .stock-safe {
            background: #dcfce7;
            color: #15803d;
        }

        .stock-number-low {
            color: #b91c1c;
            font-weight: bold;
        }

        .stock-number-safe {
            color: #15803d;
            font-weight: bold;
        }

        .product-action-form {
            display: inline-block;
            margin: 0;
        }

        .products-header .button {
            white-space: nowrap;
        }

        .products-empty {
            padding: 35px 15px;
            text-align: center;
            color: #64748b;
        }

        .products-empty h3 {
            color: #334155;
        }

        @media (max-width: 768px) {
            .products-page {
                margin: 20px auto;
                padding: 5px;
            }

            .products-header h1 {
                font-size: 26px;
            }

            .products-card {
                padding: 10px;
            }

            .products-table th,
            .products-table td {
                padding: 10px;
            }

            .stock-summary {
                grid-template-columns: 1fr;
            }

            .products-search {
                flex-direction: column;
            }

            .products-search input {
                width: 100%;
                min-width: 0;
            }

            .products-search button,
            .products-search .reset-search {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <main class="products-page">

        <header class="products-header">
            <div>
                <h1>Data Sparepart</h1>
                <p>Daftar inventaris sparepart gudang</p>

                <p>
                    Login sebagai:
                    <strong><?= e($user['name'] ?? 'Pengguna') ?></strong>
                </p>
            </div>

            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a href="product_add.php" class="button button-blue">
                    + Tambah Sparepart
                </a>
            <?php endif; ?>
        </header>

        <!-- RINGKASAN STOK -->
        <section class="stock-summary">

            <article class="stock-summary-card total">
                <p>Total Jenis Sparepart</p>
                <strong><?= number_format($totalProducts, 0, ',', '.') ?></strong>
            </article>

            <article class="stock-summary-card warning">
                <p>Sparepart dengan Stok Menipis</p>
                <strong><?= number_format($lowStockCount, 0, ',', '.') ?></strong>
            </article>

        </section>

        <!-- PERINGATAN STOK -->
        <?php if ($lowStockCount > 0): ?>

            <section class="stock-alert">
                <div class="stock-alert-icon">⚠️</div>

                <div>
                    <h3>
                        Peringatan! Ada <?= $lowStockCount ?> sparepart
                        dengan stok menipis.
                    </h3>

                    <p>
                        Barang berikut sudah mencapai atau berada di bawah
                        batas stok minimum. Periksa kebutuhan pengisian stok.
                    </p>

                    <ul class="stock-alert-list">
                        <?php foreach ($lowStockProducts as $lowProduct): ?>
                            <li>
                                <strong><?= e($lowProduct['name']) ?></strong>
                                — stok <?= (int) $lowProduct['stock'] ?>
                                dari minimum <?= (int) $lowProduct['min_stock'] ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

        <?php else: ?>

            <section class="stock-alert safe-alert">
                <div class="stock-alert-icon">✅</div>

                <div>
                    <h3>Semua stok dalam kondisi aman!</h3>
                    <p>
                        Tidak ada sparepart yang mencapai batas stok minimum
                        saat ini.
                    </p>
                </div>
            </section>

        <?php endif; ?>

        <!-- TABEL DATA SPAREPART -->
        <section class="products-card">

            <div class="products-card-heading">
                <h2>Daftar Sparepart</h2>

                <span>
                    <?= number_format($totalProducts, 0, ',', '.') ?> barang terdaftar
                    <?php if ($lowStockCount > 0): ?>
                        · <?= $lowStockCount ?> stok menipis
                    <?php endif; ?>
                </span>
            </div>

            <!-- FORM PENCARIAN -->
            <form method="get" action="products.php" class="products-search">
                <input
                    type="search"
                    name="q"
                    placeholder="Cari kode, nama, atau kategori sparepart..."
                    value="<?= e($search) ?>"
                    aria-label="Cari sparepart berdasarkan kode, nama, atau kategori"
                >

                <button type="submit">Cari</button>

                <?php if ($search !== ''): ?>
                    <a href="products.php" class="reset-search">Reset</a>
                <?php endif; ?>
            </form>

            <p class="search-result-info">
                <?php if ($search !== ''): ?>
                    Ditemukan <?= count($products) ?> hasil untuk
                    "<strong><?= e($search) ?></strong>".
                <?php else: ?>
                    Menampilkan <?= count($products) ?> sparepart.
                <?php endif; ?>
            </p>

            <?php if (count($products) > 0): ?>

                <div class="products-table-wrapper">
                    <table class="products-table">

                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Kode</th>
                                <th>Nama Sparepart</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual</th>
                                <th>Status Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($products as $index => $product): ?>

                                <?php
                                $stock = (int) $product['stock'];
                                $minimum = (int) $product['min_stock'];
                                $isLow = $stock <= $minimum;
                                ?>

                                <tr class="<?= $isLow ? 'low-stock-row' : '' ?>">

                                    <td><?= $index + 1 ?></td>

                                    <td><?= e($product['code']) ?></td>

                                    <td><?= e($product['name']) ?></td>

                                    <td><?= e($product['category'] ?? '-') ?></td>

                                    <td>
                                        <span class="<?= $isLow ? 'stock-number-low' : 'stock-number-safe' ?>">
                                            <?= $stock ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= rupiah($product['purchase_price']) ?>
                                    </td>

                                    <td>
                                        <?= rupiah($product['selling_price']) ?>
                                    </td>

                                    <td>
                                        <?php if ($isLow): ?>
                                            <span class="stock-badge stock-low">
                                                ⚠ Stok Menipis
                                            </span>
                                        <?php else: ?>
                                            <span class="stock-badge stock-safe">
                                                ✓ Aman
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (($user['role'] ?? '') === 'admin'): ?>

                                            <a
                                                href="product_edit.php?id=<?= (int) $product['id'] ?>"
                                                class="button button-edit"
                                            >Edit</a>

                                            <form
                                                class="product-action-form"
                                                method="post"
                                                action="product_delete.php"
                                                onsubmit="return confirm('Yakin ingin menghapus sparepart ini?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $product['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="button button-red"
                                                >Hapus</button>
                                            </form>

                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            <?php else: ?>

                <div class="products-empty">
                    <?php if ($search !== ''): ?>

                        <h3>Sparepart tidak ditemukan</h3>
                        <p>
                            Tidak ada sparepart yang cocok dengan
                            "<?= e($search) ?>".
                        </p>
                        <p>Coba kata kunci lain atau periksa kembali ejaan pencarian.</p>

                        <a href="products.php" class="button button-blue">
                            Reset Pencarian
                        </a>

                    <?php else: ?>

                        <h3>Belum ada data sparepart</h3>
                        <p>Tambahkan sparepart untuk mulai mengelola inventaris.</p>

                        <?php if (($user['role'] ?? '') === 'admin'): ?>
                            <a href="product_add.php" class="button button-blue">
                                + Tambah Sparepart
                            </a>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </section>

    </main>
</div>

</body>
</html>
