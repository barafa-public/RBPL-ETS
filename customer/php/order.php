<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit;
}
include '../../config/connection.php';

$username = $_SESSION['username'];
$customer_id = $_SESSION['id'];

$q = mysqli_query($conn, "SELECT * FROM customers WHERE id='$customer_id'");
$customer = mysqli_fetch_assoc($q);

// Ambil produk dari database
$product_query = mysqli_query($conn, "SELECT * FROM products");
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Buat Pesanan</title>
  <link rel="stylesheet" href="../css/order.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

  <div class="header">
    <button class="btn-back" onclick="window.location.href='dashboard.php'">
      <i class="fa-solid fa-arrow-left"></i>
    </button>
    <h2 class="header-title">Buat Pesanan</h2>
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>
  </div>

  <div class="content">

    <!-- Gambar Produk -->
    <div class="section-card">
      <h3 class="section-title">Gambar Produk</h3>
      <div class="product-grid">
        <?php while ($p = mysqli_fetch_assoc($product_query)): ?>
          <div class="product-item">
            <img src="../img/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>"
              onerror="this.src='https://placehold.co/140x150/e8f5e9/3ab87a?text=Produk'" />
            <p><?= htmlspecialchars($p['product_name']) ?></p>
            <?php if ($p['stock'] <= 0): ?>
              <span class="badge-habis">Habis</span>
            <?php elseif ($p['stock'] <= 10): ?>
              <span class="badge-menipis">Stok: <?= $p['stock'] ?></span>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      </div>
    </div>

    <!-- Informasi Pesanan -->
    <div class="section-card">
      <h3 class="section-title">Informasi Pesanan</h3>
      <form id="orderForm">
        <div class="form-group">
          <label>Nama Pemesan</label>
          <input type="text" value="<?= htmlspecialchars($username) ?>" readonly class="input-readonly" />
        </div>

        <div class="form-group">
          <label>Produk</label>
          <select id="selectProduct" required>
            <option value="" data-price="0" data-stock="0">Nama produk</option>
            <?php
            // Re-query karena pointer sudah habis dipakai untuk gambar
            $product_query2 = mysqli_query($conn, "SELECT * FROM products");
            while ($p = mysqli_fetch_assoc($product_query2)):
              $habis = $p['stock'] <= 0 ? ' (Stok Habis)' : ' - Stok: ' . $p['stock'];
              ?>
              <option value="<?= htmlspecialchars($p['product_name']) ?>" data-price="<?= $p['price'] ?>"
                data-stock="<?= $p['stock'] ?>" <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>
                <?= htmlspecialchars($p['product_name']) ?> - Rp
                <?= number_format($p['price'], 0, ',', '.') ?>    <?= $habis ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Info stok produk yang dipilih -->
        <div id="stockInfo"
          style="display:none; margin: -8px 0 12px; padding: 8px 12px; border-radius: 10px; font-size: 13px; font-weight: 600;">
        </div>

        <div class="form-group">
          <label>Jumlah</label>
          <input type="number" id="inputQuantity" placeholder="Jumlah pesanan (dalam karton)" min="1" required />
        </div>

        <div class="form-group">
          <label>Alamat Pengiriman</label>
          <textarea id="inputAddress" rows="2" required><?= htmlspecialchars($customer['address']) ?></textarea>
        </div>
      </form>
    </div>

    <!-- Metode Pembayaran -->
    <div class="section-card">
      <h3 class="section-title">Metode Pembayaran</h3>
      <div class="payment-options">
        <label class="payment-option">
          <input type="radio" name="payment_method" value="Transfer Bank" checked />
          <span>Transfer Bank</span>
        </label>
        <label class="payment-option">
          <input type="radio" name="payment_method" value="QRIS" />
          <span>QRIS</span>
        </label>
        <label class="payment-option">
          <input type="radio" name="payment_method" value="E-Wallet" />
          <span>E-Wallet</span>
        </label>
        <label class="payment-option">
          <input type="radio" name="payment_method" value="COD (Cash on Delivery)" />
          <span>COD (Cash on Delivery)</span>
        </label>
      </div>
    </div>

    <!-- Ringkasan Pesanan -->
    <div class="section-card">
      <h3 class="section-title">Ringkasan Pesanan</h3>
      <div class="summary">
        <div class="summary-row">
          <span>Produk:</span>
          <span id="summary-product">-</span>
        </div>
        <div class="summary-row">
          <span>Jumlah:</span>
          <span id="summary-quantity">-</span>
        </div>
        <div class="summary-row">
          <span>Metode Bayar:</span>
          <span id="summary-payment">-</span>
        </div>
      </div>
    </div>

  </div>

  <!-- Tombol fixed di bawah -->
  <div class="bottom-bar">
    <button class="btn-order" onclick="createOrder()">Buat Pesanan</button>
  </div>

  <script>
    function updateSummary() {
      const product = document.getElementById('selectProduct');
      const quantity = document.getElementById('inputQuantity').value;
      const payment = document.querySelector('input[name="payment_method"]:checked').value;
      const stock = parseInt(product.options[product.selectedIndex]?.dataset.stock ?? 0);

      document.getElementById('summary-product').textContent = product.value || '-';
      document.getElementById('summary-quantity').textContent = quantity || '-';
      document.getElementById('summary-payment').textContent = payment || '-';

      // Tampilkan info stok
      const stockInfo = document.getElementById('stockInfo');
      if (product.value) {
        stockInfo.style.display = 'block';
        if (stock <= 0) {
          stockInfo.textContent = 'Stok produk ini habis.';
          stockInfo.style.background = '#fdecea';
          stockInfo.style.color = '#c0392b';
        } else if (stock <= 10) {
          stockInfo.textContent = 'Perhatian: stok tersisa ' + stock + ' karton.';
          stockInfo.style.background = '#fff8e1';
          stockInfo.style.color = '#e67e22';
        } else {
          stockInfo.textContent = 'Stok tersedia: ' + stock + ' karton.';
          stockInfo.style.background = '#e8f5e9';
          stockInfo.style.color = '#2e7d32';
        }
      } else {
        stockInfo.style.display = 'none';
      }
    }

    document.getElementById('selectProduct').addEventListener('change', updateSummary);
    document.getElementById('inputQuantity').addEventListener('input', updateSummary);
    document.querySelectorAll('input[name="payment_method"]').forEach(r => r.addEventListener('change', updateSummary));

    function createOrder() {
      const productEl = document.getElementById('selectProduct');
      const quantity = parseInt(document.getElementById('inputQuantity').value);
      const address = document.getElementById('inputAddress').value;
      const payment = document.querySelector('input[name="payment_method"]:checked').value;
      const price = productEl.options[productEl.selectedIndex].dataset.price;
      const stock = parseInt(productEl.options[productEl.selectedIndex].dataset.stock);
      const product = productEl.value;

      if (!product || !quantity || !address) {
        alert('Lengkapi semua data pesanan!');
        return;
      }

      if (stock <= 0) {
        alert('Stok produk ini sudah habis. Silakan pilih produk lain.');
        return;
      }

      if (quantity > stock) {
        alert('Jumlah pesanan (' + quantity + ') melebihi stok tersedia (' + stock + ' karton). Silakan kurangi jumlah pesanan.');
        return;
      }

      const total = price * quantity;
      sessionStorage.setItem('order', JSON.stringify({ product, quantity, address, payment, price, total }));
      window.location.href = 'payment.php';
    }
  </script>

</body>

</html>