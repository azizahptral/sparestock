
<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

/* =========================
   STATISTIK INVENTARIS
========================= */

$stats = [
    'jenis' => 0,
    'stok' => 0,
    'menipis' => 0,
    'transaksi' => 0
];

$statsError = false;

try {
    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS jenis,
            COALESCE(SUM(stock), 0) AS stok,
            COALESCE(
                SUM(CASE WHEN stock <= min_stock THEN 1 ELSE 0 END),
                0
            ) AS menipis
        FROM products
    ");

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $stats['jenis'] = (int) $data['jenis'];
        $stats['stok'] = (int) $data['stok'];
        $stats['menipis'] = (int) $data['menipis'];
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
    $stats['transaksi'] = (int) $stmt->fetchColumn();

} catch (PDOException $e) {
    $statsError = true;
}

/* =========================
   DATA GRAFIK 6 BULAN
========================= */

$months = [];
$monthKeys = [];

for ($i = 5; $i >= 0; $i--) {
    $date = new DateTime('first day of this month');
    $date->modify("-$i months");

    $monthKeys[] = $date->format('Y-m');

    $months[] = [
        'key' => $date->format('Y-m'),
        'label' => $date->format('M Y')
    ];
}

$stockIn = array_fill(0, 6, 0);
$stockOut = array_fill(0, 6, 0);
$chartError = false;

try {
    $sql = "
        SELECT
            DATE_FORMAT(t.transaction_date, '%Y-%m') AS bulan,
            t.type,
            COALESCE(SUM(td.quantity), 0) AS total
        FROM transactions t
        INNER JOIN transaction_details td
            ON td.transaction_id = t.id
        WHERE t.transaction_date >= DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 5 MONTH
        )
        AND t.transaction_date < DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 1 MONTH
        )
        GROUP BY
            DATE_FORMAT(t.transaction_date, '%Y-%m'),
            t.type
        ORDER BY bulan ASC
    ";

    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $index = array_search($row['bulan'], $monthKeys, true);

        if ($index !== false) {
            if ($row['type'] === 'masuk') {
                $stockIn[$index] = (int) $row['total'];
            } elseif ($row['type'] === 'keluar') {
                $stockOut[$index] = (int) $row['total'];
            }
        }
    }

} catch (PDOException $e) {
    $chartError = true;
}

$monthLabels = array_column($months, 'label');

/* =========================
   TOP 5 STOK KELUAR
========================= */

$topProducts = [];
$topProductsError = false;

try {
    $sql = "
        SELECT
            p.code,
            p.name,
            SUM(td.quantity) AS total_keluar
        FROM transactions t
        INNER JOIN transaction_details td
            ON td.transaction_id = t.id
        INNER JOIN products p
            ON p.id = td.product_id
        WHERE t.type = 'keluar'
        GROUP BY p.id, p.code, p.name
        ORDER BY total_keluar DESC, p.name ASC
        LIMIT 5
    ";

    $stmt = $pdo->query($sql);
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $topProductsError = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard - SpareStock</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --sidebar: #172554;
            --background: #f1f5f9;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--background);
            color: var(--text);
        }

        a {
            text-decoration: none;
        }

        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            min-width: 250px;
            background: var(--sidebar);
            color: white;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 4px 10px 26px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            margin-bottom: 24px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            flex-shrink: 0;
        }

        .brand h2 {
            font-size: 20px;
            margin: 0;
        }

        .brand small {
            display: block;
            color: #bfdbfe;
            font-size: 11px;
            margin-top: 5px;
        }

        .menu-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.2px;
            padding: 0 12px;
            margin-bottom: 12px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 12px;
            color: #cbd5e1;
            border-radius: 9px;
            font-size: 14px;
            transition: background 0.2s, color 0.2s;
        }

        .menu-link:hover,
        .menu-link.active {
            background: #2563eb;
            color: white;
        }

        .menu-icon {
            width: 23px;
            text-align: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 24px;
        }

        .account-box {
            padding: 14px 12px;
            border-radius: 10px;
            background: rgba(255,255,255,0.08);
            margin-bottom: 12px;
            overflow-wrap: anywhere;
        }

        .account-box strong {
            display: block;
            font-size: 14px;
        }

        .account-box span {
            display: block;
            font-size: 12px;
            color: #bfdbfe;
            margin-top: 5px;
        }

        .logout-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 9px;
            color: #fecaca;
            font-size: 14px;
        }

        .logout-link:hover {
            background: rgba(220,38,38,0.2);
        }

        /* KONTEN UTAMA */
        .main-content {
            flex: 1;
            min-width: 0;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .topbar h1 {
            margin: 0;
            color: #1e293b;
            font-size: 27px;
        }

        .topbar p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .role-badge {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 20px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: bold;
        }

        /* SAMBUTAN */
        .welcome {
            padding: 24px;
            margin-bottom: 24px;
            background: linear-gradient(120deg, #dbeafe, #eff6ff);
            border: 1px solid #bfdbfe;
            border-radius: 15px;
            color: #1e40af;
        }

        .welcome h2 {
            margin: 0 0 10px;
            font-size: 21px;
        }

        .welcome p {
            margin: 7px 0;
            line-height: 1.6;
            font-size: 14px;
        }

        /* KARTU STATISTIK */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 26px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 21px;
            border: 1px solid var(--border);
            border-top: 4px solid #2563eb;
            box-shadow: 0 3px 12px rgba(15,23,42,0.04);
            min-width: 0;
        }

        .stat-card.stock {
            border-top-color: #0d9488;
        }

        .stat-card.warning {
            border-top-color: #ea580c;
        }

        .stat-card.transactions {
            border-top-color: #7c3aed;
        }

        .stat-icon {
            font-size: 23px;
            margin-bottom: 13px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }

        .stat-number {
            font-size: 29px;
            font-weight: bold;
            margin-top: 8px;
            overflow-wrap: anywhere;
        }

        .stat-note {
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.5;
            margin-top: 9px;
        }

        .stats-error,
        .chart-error {
            padding: 14px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 9px;
            margin-bottom: 20px;
        }

        /* LAYOUT DUA GRAFIK */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            align-items: stretch;
        }

        .charts-grid .panel {
            min-width: 0;
            padding: 20px;
            margin-bottom: 20px;
        }

        .panel {
            background: white;
            padding: 24px;
            border: 1px solid var(--border);
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(15,23,42,0.04);
        }

        .panel-header {
            margin-bottom: 18px;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 17px;
            line-height: 1.5;
            color: #1e293b;
        }

        .panel-header p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .chart-container {
            position: relative;
            height: 280px;
            width: 100%;
        }

        .footer-note {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            padding: 12px 0 4px;
        }

        /* TABLET */
        @media (max-width: 1100px) {
            .sidebar {
                width: 220px;
                min-width: 220px;
            }

            .main-content {
                padding: 22px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* LAYAR SEMPIT */
        @media (max-width: 900px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }

            .charts-grid .chart-container {
                height: 280px;
            }
        }

        /* HP */
        @media (max-width: 700px) {
            .app-layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                min-width: 0;
                padding: 16px;
            }

            .brand {
                margin-bottom: 16px;
                padding-bottom: 16px;
            }

            .sidebar-menu {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 7px;
            }

            .menu-label {
                margin-bottom: 10px;
            }

            .menu-link {
                padding: 11px 9px;
                font-size: 12px;
                gap: 7px;
            }

            .menu-icon {
                font-size: 16px;
                width: 19px;
            }

            .sidebar-bottom {
                margin-top: 16px;
                padding-top: 14px;
                border-top: 1px solid rgba(255,255,255,0.15);
            }

            .account-box {
                margin-bottom: 5px;
            }

            .main-content {
                padding: 18px 14px;
            }

            .topbar h1 {
                font-size: 23px;
            }

            .welcome {
                padding: 19px;
            }

            .stats-grid {
                gap: 11px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-number {
                font-size: 25px;
            }

            .charts-grid .panel {
                padding: 16px;
            }

            .charts-grid .chart-container {
                height: 260px;
            }
        }
    </style>
</head>

<body>

<div class="app-layout">

    <!-- SIDEBAR KIRI -->
    <aside class="sidebar">

        <div class="brand">
            <div class="brand-icon">📦</div>
            <div>
                <h2>SpareStock</h2>
                <small>Inventory Management</small>
            </div>
        </div>

        <div class="menu-label">MENU UTAMA</div>

        <nav class="sidebar-menu">

            <a href="index.php" class="menu-link active">
                <span class="menu-icon">🏠</span>
                <span>Dashboard</span>
            </a>

            <a href="products.php" class="menu-link">
                <span class="menu-icon">📦</span>
                <span>Data Sparepart</span>
            </a>

            <a href="transaction_add.php?type=masuk" class="menu-link">
                <span class="menu-icon">📥</span>
                <span>Stok Masuk</span>
            </a>

            <a href="transaction_add.php?type=keluar" class="menu-link">
                <span class="menu-icon">📤</span>
                <span>Stok Keluar</span>
            </a>

            <a href="transactions.php" class="menu-link">
                <span class="menu-icon">🕘</span>
                <span>Riwayat Transaksi</span>
            </a>

            <a href="reports.php" class="menu-link">
                <span class="menu-icon">📊</span>
                <span>Laporan</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="menu-label">AKUN</div>

            <div class="account-box">
                <strong><?= e($user['username']) ?></strong>
                <span><?= e(ucfirst($user['role'])) ?></span>
            </div>

            <a href="logout.php" class="logout-link">
                <span class="menu-icon">🚪</span>
                <span>Logout</span>
            </a>

        </div>

    </aside>

    <!-- KONTEN UTAMA -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Dashboard</h1>
                <p>Ringkasan inventaris gudang sparepart</p>
            </div>

            <span class="role-badge">
                👤 <?= e(ucfirst($user['role'])) ?>
            </span>
        </header>

        <!-- SAMBUTAN -->
        <section class="welcome">
            <h2>Selamat datang, <?= e($user['name']) ?>! 👋</h2>
            <p>
                Selamat bekerja. Kelola inventaris sparepart
                dengan mudah melalui SpareStock.
            </p>
        </section>

        <!-- STATISTIK -->
        <?php if ($statsError): ?>

            <div class="stats-error">
                Ringkasan statistik gagal dimuat. Periksa koneksi database.
            </div>

        <?php else: ?>

            <section class="stats-grid">

                <article class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-label">Total Jenis Sparepart</div>
                    <div class="stat-number">
                        <?= number_format($stats['jenis']) ?>
                    </div>
                    <div class="stat-note">Produk terdaftar</div>
                </article>

                <article class="stat-card stock">
                    <div class="stat-icon">🗃️</div>
                    <div class="stat-label">Total Stok Barang</div>
                    <div class="stat-number">
                        <?= number_format($stats['stok']) ?>
                    </div>
                    <div class="stat-note">Jumlah seluruh unit stok</div>
                </article>

                <article class="stat-card warning">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-label">Stok Menipis</div>
                    <div class="stat-number">
                        <?= number_format($stats['menipis']) ?>
                    </div>
                    <div class="stat-note">Mencapai batas stok minimum</div>
                </article>

                <article class="stat-card transactions">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-label">Total Transaksi</div>
                    <div class="stat-number">
                        <?= number_format($stats['transaksi']) ?>
                    </div>
                    <div class="stat-note">Transaksi tercatat</div>
                </article>

            </section>

        <?php endif; ?>

        <!-- DUA GRAFIK BERDAMPINGAN -->
        <div class="charts-grid">

            <!-- GRAFIK STOK MASUK DAN KELUAR -->
            <section class="panel">

                <div class="panel-header">
                    <h2>Grafik Stok Masuk dan Keluar</h2>
                    <p>
                        Perbandingan jumlah stok masuk dan keluar
                        selama enam bulan terakhir.
                    </p>
                </div>

                <?php if ($chartError): ?>

                    <div class="chart-error">
                        Data grafik gagal dimuat.
                        Periksa struktur tabel transaksi.
                    </div>

                <?php else: ?>

                    <div class="chart-container">
                        <canvas id="stockChart"></canvas>
                    </div>

                <?php endif; ?>

            </section>

            <!-- GRAFIK TOP 5 -->
            <section class="panel">

                <div class="panel-header">
                    <h2>🏆 Top 5 Sparepart Paling Sering Keluar</h2>
                    <p>
                        Lima sparepart dengan jumlah barang keluar terbanyak.
                    </p>
                </div>

                <?php if ($topProductsError): ?>

                    <div class="chart-error">
                        Data Top 5 gagal dimuat.
                        Periksa koneksi database.
                    </div>

                <?php elseif (empty($topProducts)): ?>

                    <p style="color: #64748b; font-size: 13px;">
                        Belum ada transaksi stok keluar untuk ditampilkan.
                    </p>

                <?php else: ?>

                    <div class="chart-container">
                        <canvas id="topProductsChart"></canvas>
                    </div>

                <?php endif; ?>

            </section>

        </div>

        <!-- FOOTER -->
        <footer class="footer-note">
            &copy; <?= date('Y') ?> SpareStock —
            Sistem Inventaris Gudang Sparepart
        </footer>

    </main>

</div>

<script>
/* =========================
   GRAFIK STOK MASUK/KELUAR
========================= */

const stockCanvas = document.getElementById('stockChart');

if (stockCanvas && typeof Chart !== 'undefined') {
    new Chart(stockCanvas, {
        type: 'bar',

        data: {
            labels: <?= json_encode(
                $monthLabels,
                JSON_HEX_TAG | JSON_HEX_APOS |
                JSON_HEX_QUOT | JSON_HEX_AMP
            ) ?>,

            datasets: [
                {
                    label: 'Stok Masuk',
                    data: <?= json_encode($stockIn) ?>,
                    backgroundColor: '#2563eb',
                    borderRadius: 5
                },
                {
                    label: 'Stok Keluar',
                    data: <?= json_encode($stockOut) ?>,
                    backgroundColor: '#f97316',
                    borderRadius: 5
                }
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                intersect: false,
                mode: 'index'
            },

            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: {
                            size: 11
                        }
                    }
                },

                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' +
                                context.parsed.y + ' barang';
                        }
                    }
                }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: {
                            size: 10
                        }
                    },
                    title: {
                        display: true,
                        text: 'Jumlah Barang',
                        font: {
                            size: 11
                        }
                    }
                },

                x: {
                    ticks: {
                        font: {
                            size: 10
                        }
                    },
                    title: {
                        display: true,
                        text: 'Bulan',
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
}

/* =========================
   GRAFIK TOP 5 STOK KELUAR
========================= */

const topProductsCanvas =
    document.getElementById('topProductsChart');

if (topProductsCanvas && typeof Chart !== 'undefined') {
    const topProducts = <?= json_encode(
        $topProducts,
        JSON_HEX_TAG | JSON_HEX_APOS |
        JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    new Chart(topProductsCanvas, {
        type: 'bar',

        data: {
            labels: topProducts.map(function(item) {
                return item.code + ' - ' + item.name;
            }),

            datasets: [{
                label: 'Total Stok Keluar',

                data: topProducts.map(function(item) {
                    return Number(item.total_keluar);
                }),

                backgroundColor: [
                    '#2563eb',
                    '#0d9488',
                    '#7c3aed',
                    '#f59e0b',
                    '#f97316'
                ],

                borderRadius: 5,
                barThickness: 24,
                maxBarThickness: 30
            }]
        },

        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Total keluar: ' +
                                context.parsed.x + ' barang';
                        }
                    }
                }
            },

            scales: {
                x: {
                    beginAtZero: true,

                    ticks: {
                        precision: 0,
                        font: {
                            size: 10
                        }
                    },

                    title: {
                        display: true,
                        text: 'Jumlah Barang Keluar',
                        font: {
                            size: 11
                        }
                    }
                },

                y: {
                    ticks: {
                        autoSkip: false,
                        font: {
                            size: 10
                        }
                    }
                }
            }
        }
    });
}
</script>

</body>
</html>