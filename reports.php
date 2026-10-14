
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$role = strtolower(trim($user['role'] ?? ''));

// Laporan hanya boleh diakses admin
if ($role !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Laporan hanya dapat diakses oleh admin.');
}

function e_report($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Ambil data sparepart
$stmt = $pdo->query(
    'SELECT id, code, name, category, stock, min_stock
     FROM products
     ORDER BY name ASC'
);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung ringkasan stok
$totalProducts = count($products);
$totalStock = 0;
$lowStockCount = 0;

foreach ($products as $product) {
    $stock = (int) $product['stock'];
    $minimum = (int) $product['min_stock'];

    $totalStock += $stock;

    if ($stock <= $minimum) {
        $lowStockCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Stok - SpareStock</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .report-page {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin: 20px 0 28px;
        }

        .report-header h1 {
            color: #2563eb;
            margin-bottom: 8px;
        }

        .report-header p,
        .report-user,
        .report-footer {
            color: #64748b;
        }

        .report-user {
            font-size: 14px;
            margin-top: 10px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin: 24px 0;
        }

        .summary-card {
            padding: 22px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .06);
            border: 1px solid #e2e8f0;
        }

        .summary-card p {
            color: #64748b;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-card strong {
            display: block;
            font-size: 30px;
            color: #1e293b;
        }

        .summary-card.products {
            border-left: 4px solid #2563eb;
        }

        .summary-card.stock {
            border-left: 4px solid #16a34a;
        }

        .summary-card.low-stock {
            border-left: 4px solid #dc2626;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 24px 0;
        }

        .report-button {
            display: inline-block;
            padding: 10px 16px;
            border: none;
            border-radius: 7px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            font: inherit;
        }

        .report-button:hover {
            background: #1d4ed8;
        }

        .report-button.secondary {
            background: #475569;
        }

        .report-button.secondary:hover {
            background: #334155;
        }

        .table-wrapper {
            overflow-x: auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .06);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        .report-table th,
        .report-table td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .report-table th {
            background: #eff6ff;
            color: #1e40af;
        }

        .report-table tbody tr:hover {
            background: #f8fafc;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .status.safe {
            background: #dcfce7;
            color: #166534;
        }

        .status.low {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            padding: 24px;
            text-align: center;
            color: #64748b;
        }

        .report-footer {
            margin-top: 20px;
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .report-page {
                margin: 16px auto;
                padding: 12px;
            }

            .report-header {
                align-items: flex-start;
            }

            .summary {
                grid-template-columns: 1fr;
            }

            .summary-card {
                padding: 18px;
            }

            .actions {
                flex-direction: column;
            }

            .report-button {
                text-align: center;
            }
        }

        @media print {
            @page {
                size: landscape;
                margin: 12mm;
            }

            body {
                background: #fff !important;
            }

            .sidebar,
            .actions,
            .back-link,
            .report-user {
                display: none !important;
            }

            .main-content {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .report-page {
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .summary-card,
            .table-wrapper {
                box-shadow: none;
                break-inside: avoid;
            }

            .report-table {
                min-width: 0;
            }

            .report-table th,
            .report-table td {
                padding: 8px;
                font-size: 11px;
            }

            .report-table thead {
                display: table-header-group;
            }

            .report-table tr {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <main class="report-page">

        <header class="report-header">
            <div>
                <h1>Laporan Stok Sparepart</h1>
                <p>Ringkasan persediaan sparepart di gudang.</p>

                <div class="report-user">
                    Login sebagai:
                    <strong>
                        <?= e_report($user['name'] ?? 'Pengguna') ?>
                    </strong>
                </div>
            </div>
        </header>

        <section class="summary">
            <article class="summary-card products">
                <p>Jenis Sparepart</p>
                <strong>
                    <?= number_format($totalProducts, 0, ',', '.') ?>
                </strong>
            </article>

            <article class="summary-card stock">
                <p>Total Stok Barang</p>
                <strong>
                    <?= number_format($totalStock, 0, ',', '.') ?>
                </strong>
            </article>

            <article class="summary-card low-stock">
                <p>Stok Menipis</p>
                <strong>
                    <?= number_format($lowStockCount, 0, ',', '.') ?>
                </strong>
            </article>
        </section>

        <div class="actions">
            <button
                type="button"
                class="report-button"
                onclick="window.print()"
            >
                Cetak Laporan
            </button>

            <a class="report-button secondary" href="products.php">
                Data Sparepart
            </a>

            <a class="report-button secondary" href="transactions.php">
                Riwayat Transaksi
            </a>
        </div>

        <div class="table-wrapper">
            <?php if (!empty($products)): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode</th>
                            <th>Nama Sparepart</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Stok Minimum</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($products as $index => $product): ?>
                            <?php
                            $stock = (int) $product['stock'];
                            $minimum = (int) $product['min_stock'];
                            $isLow = $stock <= $minimum;
                            ?>

                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e_report($product['code'] ?? '-') ?></td>
                                <td><?= e_report($product['name'] ?? '-') ?></td>
                                <td><?= e_report($product['category'] ?? '-') ?></td>
                                <td><?= number_format($stock, 0, ',', '.') ?></td>
                                <td><?= number_format($minimum, 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($isLow): ?>
                                        <span class="status low">
                                            Stok Menipis
                                        </span>
                                    <?php else: ?>
                                        <span class="status safe">
                                            Aman
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty">
                    Belum ada data sparepart untuk ditampilkan.
                </div>
            <?php endif; ?>
        </div>

        <p class="report-footer">
            Laporan ini menampilkan kondisi stok berdasarkan data
            sparepart yang tersimpan di database.
        </p>

    </main>
</div>

</body>
</html>
