<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  include '../../config/connection.php';

  $customer_id = $_SESSION['id'];
  $product_name = $_POST['product_name'];
  $quantity = (int) $_POST['quantity'];
  $address = $_POST['address'];
  $payment_method = $_POST['payment_method'];
  $total = (int) $_POST['total'];

  // Simpan pesanan ke tabel orders
  $query = "INSERT INTO orders (customer_id, product_name, quantity, address, payment_method, total, status)
              VALUES ('$customer_id', '$product_name', '$quantity', '$address', '$payment_method', '$total', 'Menunggu Konfirmasi')";

  if (mysqli_query($conn, $query)) {
    $order_id = mysqli_insert_id($conn);

    // Simpan deposit sesuai metode pembayaran
    if (stripos($payment_method, 'transfer') !== false) {
      // Transfer Bank
      $nama_bank = 'BCA';
      $no_rek = '1234567890';
      $atas_nama = 'PT Online Order System';
      mysqli_query($conn, "
                INSERT INTO bank_transfer_deposits (order_id, nama_bank, no_rekening, atas_nama, nominal)
                VALUES ('$order_id', '$nama_bank', '$no_rek', '$atas_nama', '$total')
            ");

    } elseif (stripos($payment_method, 'qris') !== false) {
      // QRIS
      $kode_transaksi = 'QRIS-' . strtoupper(uniqid());
      mysqli_query($conn, "
                INSERT INTO qris_deposits (order_id, kode_transaksi, nominal)
                VALUES ('$order_id', '$kode_transaksi', '$total')
            ");

    } elseif (stripos($payment_method, 'wallet') !== false || stripos($payment_method, 'ewallet') !== false) {
      // E-Wallet
      $platform = 'GoPay';
      $no_pengirim = '-';
      $kode_transaksi = 'EW-' . strtoupper(uniqid());
      mysqli_query($conn, "
                INSERT INTO ewallet_deposits (order_id, platform, no_pengirim, kode_transaksi, nominal)
                VALUES ('$order_id', '$platform', '$no_pengirim', '$kode_transaksi', '$total')
            ");

    }
    // COD → deposit dicatat oleh karyawan di codDeposit.php

    header("Location: successPayment.php");
    exit;

  } else {
    $error = "Gagal menyimpan pesanan: " . mysqli_error($conn);
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pembayaran</title>
  <link rel="stylesheet" href="../css/payment.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

  <div class="header">
    <button class="btn-back" onclick="window.location.href='order.php'">
      <i class="fa-solid fa-arrow-left"></i>
    </button>
    <h2 class="header-title">Pembayaran</h2>
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>
  </div>

  <div class="content">

    <?php if (!empty($error)): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="section-card">
      <h3 class="section-title">Detail Pembayaran</h3>
      <div class="detail-row">
        <span class="detail-label">Produk:</span>
        <span class="detail-value" id="d-product">-</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Jumlah:</span>
        <span class="detail-value" id="d-quantity">-</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Metode Bayar:</span>
        <span class="detail-value" id="d-payment">-</span>
      </div>
      <div class="detail-divider"></div>
      <div class="detail-row">
        <span class="detail-label">Total:</span>
        <span class="detail-value total-green" id="d-total">-</span>
      </div>
    </div>

    <!-- Info Transfer Bank -->
    <div class="section-card" id="info-transfer" style="display:none">
      <h3 class="section-title">Informasi Transfer</h3>
      <div class="transfer-info">
        <p class="transfer-label">Bank:</p>
        <p class="transfer-value">BCA</p>
        <p class="transfer-label">No. Rekening:</p>
        <p class="transfer-value">1234567890</p>
        <p class="transfer-label">Atas Nama:</p>
        <p class="transfer-value">PT Online Order System</p>
      </div>
    </div>

    <!-- Info QRIS -->
    <div class="section-card" id="info-qris" style="display:none">
      <h3 class="section-title">Informasi QRIS</h3>
      <div class="transfer-info">
        <p class="transfer-label">Scan QR code berikut:</p>
        <div class="qris-placeholder">
          <i class="fa-solid fa-qrcode"></i>
          <p>QR Code Pembayaran</p>
        </div>
        <p class="transfer-label">atau gunakan aplikasi pembayaran digitalmu.</p>
      </div>
    </div>

    <!-- Info E-Wallet -->
    <div class="section-card" id="info-ewallet" style="display:none">
      <h3 class="section-title">Informasi E-Wallet</h3>
      <div class="transfer-info">
        <p class="transfer-label">Platform:</p>
        <p class="transfer-value">GoPay / OVO / Dana</p>
        <p class="transfer-label">No. Tujuan:</p>
        <p class="transfer-value">081234567890</p>
        <p class="transfer-label">Atas Nama:</p>
        <p class="transfer-value">PT Online Order System</p>
      </div>
    </div>

    <!-- Info COD -->
    <div class="section-card" id="info-cod" style="display:none">
      <h3 class="section-title">Informasi COD</h3>
      <div class="transfer-info">
        <p class="transfer-label">Metode:</p>
        <p class="transfer-value">Bayar Tunai saat barang diterima</p>
        <p class="transfer-label">Nominal:</p>
        <p class="transfer-value total-green" id="cod-total">-</p>
      </div>
    </div>

    <form id="paymentForm" method="POST">
      <input type="hidden" name="product_name" id="h-product" />
      <input type="hidden" name="quantity" id="h-quantity" />
      <input type="hidden" name="address" id="h-address" />
      <input type="hidden" name="payment_method" id="h-payment" />
      <input type="hidden" name="total" id="h-total" />
    </form>

    <button class="btn-konfirmasi" onclick="submitPayment()">Konfirmasi Pembayaran</button>

  </div>

  <script>
    const order = JSON.parse(sessionStorage.getItem('order') || '{}');
    if (!order.product) window.location.href = 'order.php';

    document.getElementById('d-product').textContent = order.product || '-';
    document.getElementById('d-quantity').textContent = order.quantity || '-';
    document.getElementById('d-payment').textContent = order.payment || '-';
    document.getElementById('d-total').textContent = 'Rp ' + Number(order.total).toLocaleString('id-ID');

    document.getElementById('h-product').value = order.product;
    document.getElementById('h-quantity').value = order.quantity;
    document.getElementById('h-address').value = order.address;
    document.getElementById('h-payment').value = order.payment;
    document.getElementById('h-total').value = order.total;

    // Tampilkan info sesuai metode
    const pm = (order.payment || '').toLowerCase();
    if (pm.includes('transfer')) document.getElementById('info-transfer').style.display = '';
    else if (pm.includes('qris')) document.getElementById('info-qris').style.display = '';
    else if (pm.includes('wallet')) document.getElementById('info-ewallet').style.display = '';
    else if (pm.includes('cod')) {
      document.getElementById('info-cod').style.display = '';
      document.getElementById('cod-total').textContent = 'Rp ' + Number(order.total).toLocaleString('id-ID');
    }

    function submitPayment() {
      document.getElementById('paymentForm').submit();
    }
  </script>

</body>

</html>