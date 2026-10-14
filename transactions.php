
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Ambil seluruh riwayat transaksi
$sql = "
    SELECT
        t.id,
        t.type,
        t.transaction_date,
        t.notes,
        p.code,
        p.name AS product_name,
        td.quantity
    FROM transactions t
    LEFT JOIN transaction_details td
        ON td.transaction_id = t.id
    LEFT JOIN products p
        ON p.id = td.product_id
    ORDER BY t.transaction_date DESC, t.id DESC
";

$stmt = $pdo->query($sql);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e_transaction($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Riwayat Transaksi - SpareStock</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .transactions-page {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin: 24px 0;
        }

        .page-header h1 {
            color: #2563eb;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #64748b;
        }

        .page-description {
            margin-top: 5px;
            color: #64748b;
        }

        .table-wrapper {
            overflow-x: auto;
            background: #fff;
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        .transaction-table th,
        .transaction-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .transaction-table th {
            background: #eff6ff;
            color: #1e40af;
        }

        .transaction-table tbody tr:hover {
            background: #f8fafc;
        }

        .transaction-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
        }

        .transaction-badge.masuk {
            background: #dcfce7;
            color: #166534;
        }

        .transaction-badge.keluar {
            background: #fee2e2;
            color: #991b1b;
        }

        .transaction-button {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 7px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .transaction-button:hover {
            background: #1d4ed8;
        }

        .transaction-button.secondary {
            background: #e2e8f0;
            color: #334155;
        }

        .transaction-button.secondary:hover {
            background: #cbd5e1;
        }

        .empty {
            padding: 24px;
            text-align: center;
            color: #64748b;
        }

        .transaction-count {
            margin-bottom: 14px;
            color: #64748b;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .transactions-page {
                margin: 16px auto;
                padding: 12px;
            }

            .page-header {
                align-items: flex-start;
            }

            .table-wrapper {
                padding: 10px;
            }

            .transaction-table th,
            .transaction-table td {
                padding: 10px;
            }
        }
    </style>
</head>

<body>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <main class="transactions-page">

        <header class="page-header">
            <div>
                <h1>Riwayat Transaksi</h1>
                <p>
                    Login sebagai:
                    <strong>
                        <?= e_transaction($user['name'] ?? 'Pengguna') ?>
                    </strong>
                </p>
                <p class="page-description">
                    Lihat riwayat stok barang masuk dan barang keluar.
                </p>
            </div>

            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <div>
                    <a
                        href="transaction_add.php?type=masuk"
                        class="transaction-button"
                    >
                        + Stok Masuk
                    </a>

                    <a
                        href="transaction_add.php?type=keluar"
                        class="transaction-button secondary"
                    >
                        + Stok Keluar
                    </a>
                </div>
            <?php endif; ?>
        </header>

        <p class="transaction-count">
            Total data transaksi: <strong><?= count($transactions) ?></strong>
        </p>

        <div class="table-wrapper">
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Jenis</th>
                        <th>Kode Barang</th>
                        <th>Nama Sparepart</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                        <th>Catatan</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($transactions)): ?>

                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <?= (int) $transaction['id'] ?>
                                </td>

                                <td>
                                    <?php if ($transaction['type'] === 'masuk'): ?>
                                        <span class="transaction-badge masuk">
                                            Barang Masuk
                                        </span>
                                    <?php else: ?>
                                        <span class="transaction-badge keluar">
                                            Barang Keluar
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= e_transaction($transaction['code'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= e_transaction($transaction['product_name'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= $transaction['quantity'] !== null
                                        ? (int) $transaction['quantity']
                                        : '-' ?>
                                </td>

                                <td>
                                    <?= e_transaction($transaction['transaction_date'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= e_transaction($transaction['notes'] ?? '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty">
                                Belum ada transaksi.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

</body>
</html>