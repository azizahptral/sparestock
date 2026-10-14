
<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengedit sparepart.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: products.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $purchasePrice = filter_input(INPUT_POST, 'purchase_price', FILTER_VALIDATE_FLOAT);
    $sellingPrice = filter_input(INPUT_POST, 'selling_price', FILTER_VALIDATE_FLOAT);
    $minStock = filter_input(INPUT_POST, 'min_stock', FILTER_VALIDATE_INT);

    if (
        $code === '' ||
        $name === '' ||
        $stock === false || $stock === null || $stock < 0 ||
        $purchasePrice === false || $purchasePrice === null || $purchasePrice < 0 ||
        $sellingPrice === false || $sellingPrice === null || $sellingPrice < 0 ||
        $minStock === false || $minStock === null || $minStock < 0
    ) {
        $error = 'Periksa kembali semua kolom dan masukkan angka yang valid.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'UPDATE products SET
                    code = :code,
                    name = :name,
                    category = :category,
                    stock = :stock,
                    purchase_price = :purchase_price,
                    selling_price = :selling_price,
                    min_stock = :min_stock
                 WHERE id = :id'
            );

            $stmt->execute([
                'code' => $code,
                'name' => $name,
                'category' => $category !== '' ? $category : null,
                'stock' => $stock,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'min_stock' => $minStock,
                'id' => $id
            ]);

            header('Location: products.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Kode sparepart sudah digunakan oleh produk lain.';
            } else {
                $error = 'Data gagal diperbarui. Periksa konfigurasi database.';
            }
        }
    }

    $product['code'] = $code;
    $product['name'] = $name;
    $product['category'] = $category;
    $product['stock'] = $stock;
    $product['purchase_price'] = $purchasePrice;
    $product['selling_price'] = $sellingPrice;
    $product['min_stock'] = $minStock;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Sparepart - SpareStock</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .form-container {
            max-width: 650px;
            margin: 40px auto;
            padding: 28px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, .07);
        }

        .product-form {
            display: grid;
            gap: 16px;
        }

        .product-form label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .product-form input {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }

        .product-form button {
            padding: 12px;
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .form-error {
            padding: 12px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 8px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<main class="form-container">

    <a class="back-link" href="products.php">
        &larr; Kembali ke Data Sparepart
    </a>

    <h1>Edit Sparepart</h1>
    <p>Perbarui informasi sparepart di bawah ini.</p>

    <?php if ($error !== ''): ?>
        <p class="form-error"><?= e($error) ?></p>
    <?php endif; ?>

    <form class="product-form" method="post">

        <div>
            <label for="code">Kode Sparepart *</label>
            <input type="text" id="code" name="code"
                   maxlength="50" required
                   value="<?= e($product['code']) ?>">
        </div>

        <div>
            <label for="name">Nama Sparepart *</label>
            <input type="text" id="name" name="name"
                   maxlength="150" required
                   value="<?= e($product['name']) ?>">
        </div>

        <div>
            <label for="category">Kategori</label>
            <input type="text" id="category" name="category"
                   maxlength="100"
                   value="<?= e($product['category'] ?? '') ?>">
        </div>

        <div>
            <label for="stock">Stok *</label>
            <input type="number" id="stock" name="stock"
                   min="0" step="1" required
                   value="<?= e($product['stock']) ?>">
        </div>

        <div>
            <label for="purchase_price">Harga Beli (Rp) *</label>
            <input type="number" id="purchase_price"
                   name="purchase_price" min="0" step="0.01"
                   required
                   value="<?= e($product['purchase_price']) ?>">
        </div>

        <div>
            <label for="selling_price">Harga Jual (Rp) *</label>
            <input type="number" id="selling_price"
                   name="selling_price" min="0" step="0.01"
                   required
                   value="<?= e($product['selling_price']) ?>">
        </div>

        <div>
            <label for="min_stock">Batas Minimum Stok *</label>
            <input type="number" id="min_stock" name="min_stock"
                   min="0" step="1" required
                   value="<?= e($product['min_stock']) ?>">
        </div>

        <button type="submit">Simpan Perubahan</button>

    </form>

</main>

</body>
</html>
