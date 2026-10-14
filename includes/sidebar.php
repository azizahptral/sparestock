```php
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = $_SESSION['user'] ?? [];
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">📦</div>
        <div>
            <h2>SpareStock</h2>
            <p>Inventory Management</p>
        </div>
    </div>

    <div class="sidebar-section-title">MENU UTAMA</div>

    <nav class="sidebar-menu">
        <a href="index.php"
           class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
            🏠 <span>Dashboard</span>
        </a>

        <a href="products.php"
           class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
            📦 <span>Data Sparepart</span>
        </a>

        <a href="transaction_add.php?type=masuk"
           class="<?= $currentPage === 'transaction_add.php' && ($_GET['type'] ?? '') === 'masuk' ? 'active' : '' ?>">
            📥 <span>Stok Masuk</span>
        </a>

        <a href="transaction_add.php?type=keluar"
           class="<?= $currentPage === 'transaction_add.php' && ($_GET['type'] ?? '') === 'keluar' ? 'active' : '' ?>">
            📤 <span>Stok Keluar</span>
        </a>

        <a href="transactions.php"
           class="<?= $currentPage === 'transactions.php' ? 'active' : '' ?>">
            🕒 <span>Riwayat Transaksi</span>
        </a>

        <a href="reports.php"
           class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>">
            📊 <span>Laporan</span>
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="user-name">
            👤 <?= e($user['name'] ?? 'Pengguna') ?>
        </div>
        <div class="user-role">
            <?= e(ucfirst($user['role'] ?? '')) ?>
        </div>
        <a href="logout.php" class="logout-link">
            🚪 Keluar
        </a>
    </div>
</aside>
```