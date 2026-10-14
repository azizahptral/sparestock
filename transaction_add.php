
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Pastikan pengguna sudah login.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$role = strtolower(trim($user['role'] ?? ''));

// Admin dan karyawan boleh melakukan transaksi.
if (!in_array($role, ['admin', 'karyawan'], true)) {
    http_response_code(403);
    exit('Anda tidak memiliki akses untuk menambahkan transaksi.');
}

$error = '';
$allowedTypes = ['masuk', 'keluar'];
$type = $_POST['type'] ?? $_GET['type'] ?? '';

if (!in_array($type, $allowedTypes, true)) {
    $type = '';
}

// Ambil daftar sparepart.
$products = $pdo->query(
    'SELECT id, code, name, stock
     FROM products
     ORDER BY name ASC'
)->fetchAll(PDO::FETCH_ASSOC);

// Proses penyimpanan transaksi.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );

    $quantity = filter_input(
        INPUT_POST,
        'quantity',
        FILTER_VALIDATE_INT
    );

    $notes = trim($_POST['notes'] ?? '');

    if (
        !in_array($type, $allowedTypes, true) ||
        !$productId ||
        !$quantity ||
        $quantity < 1
    ) {
        $error = 'Jenis transaksi, sparepart, atau jumlah tidak valid.';
    } else {
        try {
            $pdo->beginTransaction();

            // Kunci baris produk selama transaksi agar stok konsisten.
            $stmt = $pdo->prepare(
                'SELECT id, stock
                 FROM products
                 WHERE id = ?
                 FOR UPDATE'
            );

            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new RuntimeException(
                    'Sparepart tidak ditemukan.'
                );
            }

            $currentStock = (int) $product['stock'];

            // Stok keluar tidak boleh melebihi stok tersedia.
            if (
                $type === 'keluar' &&
                $quantity > $currentStock
            ) {
                throw new RuntimeException(
                    'Stok tidak mencukupi. Stok saat ini: '
                    . $currentStock
                );
            }

            $newStock = $type === 'masuk'
                ? $currentStock + $quantity
                : $currentStock - $quantity;

            // Simpan transaksi.
            $stmt = $pdo->prepare(
                'INSERT INTO transactions (user_id, type, notes)
                 VALUES (?, ?, ?)'
            );

            $stmt->execute([
                $user['id'],
                $type,
                $notes !== '' ? $notes : null
            ]);

            $transactionId = (int) $pdo->lastInsertId();

            // Simpan detail transaksi.
            $stmt = $pdo->prepare(
                'INSERT INTO transaction_details
                    (transaction_id, product_id, quantity)
                 VALUES (?, ?, ?)'
            );

            $stmt->execute([
                $transactionId,
                $productId,
                $quantity
            ]);

            // Perbarui stok sparepart.
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET stock = ?
                 WHERE id = ?'
            );

            $stmt->execute([$newStock, $productId]);

            $pdo->commit();

            header('Location: transactions.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($exception instanceof RuntimeException) {
                $error = $exception->getMessage();
            } else {
                $error = 'Transaksi gagal disimpan. Periksa struktur tabel database.';
            }
        }
    }
}

function e_add_transaction($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$selectedProductId = (string) ($_POST['product_id'] ?? '');
$selectedProduct = null;

foreach ($products as $product) {
    if ((string) $product['id'] === $selectedProductId) {
        $selectedProduct = $product;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Tambah Transaksi - SpareStock</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .transaction-page {
            width: 100%;
            max-width: 850px;
            margin: 35px auto;
            padding: 10px;
        }

        .transaction-card {
            padding: 30px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 5px 25px rgba(15, 23, 42, .06);
        }

        .transaction-header {
            margin-bottom: 25px;
        }

        .transaction-header h1 {
            margin: 0 0 10px;
            color: #2563eb;
            font-size: 30px;
        }

        .transaction-header p {
            margin: 0;
            color: #64748b;
            line-height: 1.6;
        }

        .transaction-back {
            display: inline-block;
            margin-bottom: 22px;
            font-weight: bold;
        }

        .transaction-form label {
            display: block;
            margin: 20px 0 8px;
            color: #334155;
            font-weight: bold;
        }

        .transaction-form input,
        .transaction-form select,
        .transaction-form textarea {
            display: block;
            box-sizing: border-box;
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background: #fff;
            color: #1e293b;
            font: inherit;
            outline: none;
        }

        .transaction-form input:focus,
        .transaction-form select:focus,
        .transaction-form textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        .transaction-form textarea {
            resize: vertical;
        }

        .transaction-submit {
            display: inline-block;
            width: 100%;
            margin-top: 25px;
            padding: 14px 18px;
            border: none;
            border-radius: 9px;
            background: #2563eb;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            transition: background .2s ease;
        }

        .transaction-submit:hover {
            background: #1d4ed8;
        }

        .transaction-error {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 9px;
            background: #fee2e2;
            color: #991b1b;
        }

        .transaction-hint {
            margin-top: 15px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .search-results {
            display: none;
            max-height: 240px;
            margin-top: 5px;
            overflow-y: auto;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background: #fff;
        }

        .search-option {
            display: block;
            width: 100%;
            margin: 0;
            padding: 12px;
            border: none;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 0;
            background: #fff;
            color: #0f172a;
            text-align: left;
            cursor: pointer;
        }

        .search-option:hover {
            background: #eff6ff;
        }

        .search-option:disabled {
            color: #94a3b8;
            cursor: not-allowed;
        }

        .selected-product {
            margin-top: 10px;
            color: #166534;
            font-size: 14px;
            line-height: 1.6;
        }

        .no-results {
            padding: 12px;
            color: #64748b;
        }

        .no-products {
            padding: 20px;
            border-radius: 10px;
            background: #f8fafc;
            color: #64748b;
            line-height: 1.7;
        }

        @media (max-width: 768px) {
            .transaction-page {
                margin: 20px auto;
            }

            .transaction-card {
                padding: 20px;
            }

            .transaction-header h1 {
                font-size: 25px;
            }
        }

        @media (max-width: 480px) {
            .transaction-page {
                padding: 5px;
            }

            .transaction-card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <main class="transaction-page">

        <a class="transaction-back" href="transactions.php">
            &larr; Kembali ke Riwayat Transaksi
        </a>

        <section class="transaction-card">

            <header class="transaction-header">
                <h1>Tambah Transaksi</h1>
                <p>
                    Catat sparepart yang masuk atau keluar dari gudang.
                </p>
            </header>

            <?php if ($error !== ''): ?>
                <div class="transaction-error" role="alert">
                    <?= e_add_transaction($error) ?>
                </div>
            <?php endif; ?>

            <?php if (count($products) === 0): ?>

                <div class="no-products">
                    <strong>Belum ada sparepart.</strong>
                    <p>
                        Tambahkan data sparepart terlebih dahulu sebelum
                        membuat transaksi.
                    </p>

                    <a href="products.php" class="button button-blue">
                        Buka Data Sparepart
                    </a>
                </div>

            <?php else: ?>

                <form
                    method="post"
                    id="transactionForm"
                    class="transaction-form"
                >

                    <label for="type">Jenis Transaksi</label>

                    <select name="type" id="type" required>
                        <option value="">-- Pilih Jenis --</option>

                        <option
                            value="masuk"
                            <?= $type === 'masuk' ? 'selected' : '' ?>
                        >
                            Barang Masuk
                        </option>

                        <option
                            value="keluar"
                            <?= $type === 'keluar' ? 'selected' : '' ?>
                        >
                            Barang Keluar
                        </option>
                    </select>

                    <label for="productSearch">Cari Sparepart</label>

                    <input
                        type="search"
                        id="productSearch"
                        placeholder="Ketik kode atau nama sparepart..."
                        autocomplete="off"
                        aria-describedby="selectedProduct"
                    >

                    <input
                        type="hidden"
                        name="product_id"
                        id="productId"
                        value="<?= e_add_transaction($selectedProductId) ?>"
                        required
                    >

                    <div
                        id="searchResults"
                        class="search-results"
                    ></div>

                    <div
                        id="selectedProduct"
                        class="selected-product"
                        aria-live="polite"
                    >
                        <?php if ($selectedProduct): ?>
                            Dipilih:
                            <?= e_add_transaction($selectedProduct['code']) ?>
                            -
                            <?= e_add_transaction($selectedProduct['name']) ?>
                            (Stok: <?= (int) $selectedProduct['stock'] ?>)
                        <?php else: ?>
                            Belum ada sparepart yang dipilih.
                        <?php endif; ?>
                    </div>

                    <label for="quantity">Jumlah</label>

                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        min="1"
                        step="1"
                        value="<?= e_add_transaction($_POST['quantity'] ?? '1') ?>"
                        required
                    >

                    <label for="notes">Catatan (opsional)</label>

                    <textarea
                        name="notes"
                        id="notes"
                        rows="3"
                        placeholder="Tambahkan catatan transaksi..."
                    ><?= e_add_transaction($_POST['notes'] ?? '') ?></textarea>

                    <p class="transaction-hint">
                        Pilih sparepart dari hasil pencarian. Stok akan
                        diperbarui setelah transaksi berhasil disimpan.
                    </p>

                    <button
                        type="submit"
                        class="transaction-submit"
                    >
                        Simpan Transaksi
                    </button>

                </form>

            <?php endif; ?>

        </section>
    </main>
</div>

<script>
const products = <?= json_encode(
    array_map(
        static function ($product) {
            return [
                'id' => (int) $product['id'],
                'code' => $product['code'],
                'name' => $product['name'],
                'stock' => (int) $product['stock']
            ];
        },
        $products
    ),
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const searchInput = document.getElementById('productSearch');
const resultsBox = document.getElementById('searchResults');
const productIdInput = document.getElementById('productId');
const selectedLabel = document.getElementById('selectedProduct');
const transactionType = document.getElementById('type');

function renderResults() {
    const keyword = searchInput.value.trim().toLowerCase();

    resultsBox.replaceChildren();

    if (!keyword) {
        resultsBox.style.display = 'none';
        return;
    }

    const matches = products.filter(product =>
        String(product.code).toLowerCase().includes(keyword) ||
        String(product.name).toLowerCase().includes(keyword)
    );

    resultsBox.style.display = 'block';

    if (matches.length === 0) {
        const message = document.createElement('div');
        message.className = 'no-results';
        message.textContent = 'Sparepart tidak ditemukan.';
        resultsBox.appendChild(message);
        return;
    }

    matches.forEach(product => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'search-option';

        const isOut = transactionType.value === 'keluar';
        button.disabled = isOut && product.stock < 1;

        button.textContent =
            product.code + ' - ' + product.name +
            ' (Stok: ' + product.stock + ')' +
            (button.disabled ? ' - Stok habis' : '');

        button.addEventListener('click', () => {
            productIdInput.value = product.id;
            searchInput.value = product.code + ' - ' + product.name;

            selectedLabel.textContent =
                'Dipilih: ' + product.code + ' - ' + product.name +
                ' (Stok: ' + product.stock + ')';

            resultsBox.style.display = 'none';
        });

        resultsBox.appendChild(button);
    });
}

if (
    searchInput &&
    resultsBox &&
    productIdInput &&
    selectedLabel &&
    transactionType
) {
    searchInput.addEventListener('input', () => {
        productIdInput.value = '';
        selectedLabel.textContent =
            'Pilih sparepart dari hasil pencarian.';
        renderResults();
    });

    transactionType.addEventListener('change', () => {
        // Pilihan produk direset ketika jenis transaksi berubah,
        // supaya pengguna memilih ulang dengan aturan stok terbaru.
        productIdInput.value = '';
        selectedLabel.textContent =
            'Pilih sparepart dari hasil pencarian.';

        if (searchInput.value.trim() !== '') {
            renderResults();
        }
    });

    document.addEventListener('click', event => {
        if (
            event.target !== searchInput &&
            !resultsBox.contains(event.target)
        ) {
            resultsBox.style.display = 'none';
        }
    });

    document.getElementById('transactionForm')?.addEventListener(
        'submit',
        event => {
            if (!productIdInput.value) {
                event.preventDefault();
                alert('Silakan cari dan pilih sparepart terlebih dahulu.');
                searchInput.focus();
            }
        }
    );

    if (productIdInput.value) {
        const selected = products.find(
            product => String(product.id) === productIdInput.value
        );

        if (selected) {
            searchInput.value =
                selected.code + ' - ' + selected.name;
        } else {
            productIdInput.value = '';
            selectedLabel.textContent =
                'Pilih sparepart dari hasil pencarian.';
        }
    }
}
</script>

</body>
</html>