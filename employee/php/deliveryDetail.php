<?php
session_start();
if (!isset($_SESSION['employee'])) {
  header("Location: index.php");
  exit;
}
include '../../config/connection.php';

$id = (int) $_GET['id'];
$employee_id = $_SESSION['employee_id'];

// Handle aksi tombol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];

  if ($action === 'ambil') {
    // Dikirim → Pengambilan Barang
    // Kurangi stok saat barang diambil dari gudang
    $ord = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id='$id'"));
    $nama = mysqli_real_escape_string($conn, $ord['product_name']);
    $qty = (int) $ord['quantity'];
    mysqli_query($conn, "UPDATE products SET stock = stock - $qty WHERE product_name = '$nama'");
    mysqli_query($conn, "UPDATE orders SET status='Pengambilan Barang' WHERE id='$id' AND employee_id='$employee_id'");
    header("Location: deliveryDetail.php?id=$id");
    exit;

  } elseif ($action === 'kirim') {
    // Pengambilan Barang → Dalam Pengiriman
    mysqli_query($conn, "UPDATE orders SET status='Dalam Pengiriman' WHERE id='$id' AND employee_id='$employee_id'");
    header("Location: deliveryDetail.php?id=$id");
    exit;

  } elseif ($action === 'selesai') {
    // Dalam Pengiriman → Selesai
    $ord = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id='$id'"));
    if (stripos($ord['payment_method'], 'cod') !== false) {
      // COD → wajib ke halaman setoran dulu, status BELUM diubah
      header("Location: codDeposit.php?id=$id");
    } else {
      // Non-COD → langsung selesai
      mysqli_query($conn, "UPDATE orders SET status='Selesai' WHERE id='$id' AND employee_id='$employee_id'");
      header("Location: deliverySuccess.php?id=$id");
    }
    exit;
  }
}

// Ambil detail pesanan
$result = mysqli_query($conn, "
    SELECT o.*, c.username as customer_name, c.address, c.phone_number
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = '$id' AND o.employee_id = '$employee_id'
");
$order = mysqli_fetch_assoc($result);
if (!$order) {
  header("Location: taskList.php");
  exit;
}

$status = $order['status'];

// Step index sesuai status
// Step 0 (Tugas Diterima) selalu done karena sudah ditugaskan manager
// Maka current_step dimulai dari 1
$step_map = [
  'Dikirim' => 1,
  'Pengambilan Barang' => 2,
  'Dalam Pengiriman' => 3,
  'Selesai' => 4,
];
$current_step = $step_map[$status] ?? 0;

$steps = [
  ['label' => 'Tugas Diterima', 'sub' => 'Pesanan telah ditugaskan kepada Anda'],
  ['label' => 'Ambil Barang', 'sub' => 'Ambil barang dari gudang'],
  ['label' => 'Dalam Pengiriman', 'sub' => 'Menuju lokasi pelanggan'],
  ['label' => 'Selesai', 'sub' => 'Barang telah diterima pelanggan'],
];

// Status bar
if ($status === 'Dikirim') {
  $status_icon = '📋';
  $status_label = 'Siap Mengambil Barang';
  $status_class = 'status-yellow';
} elseif ($status === 'Pengambilan Barang') {
  $status_icon = '📦';
  $status_label = 'Barang Sudah Diambil - Siap Dikirim';
  $status_class = 'status-green';
} elseif ($status === 'Dalam Pengiriman') {
  $status_icon = '🚚';
  $status_label = 'Sedang Dalam Pengiriman';
  $status_class = 'status-green';
} else {
  $status_icon = '✅';
  $status_label = 'Pengiriman Selesai';
  $status_class = 'status-green';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Detail Pengiriman</title>
  <link rel="stylesheet" href="../css/deliveryDetail.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

  <div class="header">
    <button class="btn-back" onclick="window.location.href='taskList.php'">
      <i class="fa-solid fa-arrow-left"></i>
    </button>
    <h2 class="header-title">Detail Pengiriman</h2>
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>
  </div>

  <div class="content">

    <!-- Status Bar -->
    <div class="status-bar <?= $status_class ?>">
      <?= $status_icon ?> <span><?= $status_label ?></span>
    </div>

    <!-- Progress -->
    <div class="section-card">
      <p class="section-title">Progress Pengiriman</p>
      <div class="progress-list">
        <?php foreach ($steps as $i => $step):
          $done = ($i < $current_step);
          $active = ($i === $current_step);
          $pending = ($i > $current_step);
          ?>
          <div class="progress-item">
            <div class="step-left">
              <div class="step-dot <?= $done ? 'dot-done' : ($active ? 'dot-active' : 'dot-pending') ?>">
                <?php if ($done): ?>
                  <i class="fa-solid fa-check"></i>
                <?php else: ?>
                  <?= $i + 1 ?>
                <?php endif; ?>
              </div>
              <?php if ($i < count($steps) - 1): ?>
                <div class="step-line <?= $done ? 'line-done' : 'line-pending' ?>"></div>
              <?php endif; ?>
            </div>
            <div class="step-info <?= $pending ? 'text-pending' : '' ?>">
              <p class="step-label"><?= $step['label'] ?></p>
              <p class="step-sub"><?= $step['sub'] ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Lokasi Pengambilan (hanya saat status Dikirim) -->
    <?php if ($status === 'Dikirim'): ?>
      <div class="section-card">
        <p class="section-title">Lokasi Pengambilan Barang</p>
        <div class="info-row">
          <i class="fa-solid fa-store info-icon"></i>
          <div>
            <p class="info-label">Ambil barang di:</p>
            <p class="info-value">Gudang Pusat - Jl. Kaliurang No. 7, Sleman</p>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Informasi Pelanggan -->
    <div class="section-card">
      <p class="section-title">Informasi Pelanggan</p>
      <div class="info-row">
        <i class="fa-regular fa-user info-icon"></i>
        <div>
          <p class="info-label">Nama</p>
          <p class="info-value"><?= htmlspecialchars($order['customer_name']) ?></p>
        </div>
      </div>
      <div class="info-row">
        <i class="fa-solid fa-box info-icon"></i>
        <div>
          <p class="info-label">Produk</p>
          <p class="info-value"><?= htmlspecialchars($order['product_name']) ?> (<?= $order['quantity'] ?> unit)</p>
        </div>
      </div>
      <div class="info-row">
        <i class="fa-solid fa-location-dot info-icon"></i>
        <div>
          <p class="info-label">Alamat Pengiriman</p>
          <p class="info-value"><?= htmlspecialchars($order['address']) ?></p>
        </div>
      </div>
    </div>

    <!-- Kontak -->
    <div class="section-card">
      <p class="section-title">Kontak</p>
      <div class="contact-row">
        <span class="contact-label">No. HP:</span>
        <a href="tel:<?= htmlspecialchars($order['phone_number']) ?>" class="contact-phone">
          <?= htmlspecialchars($order['phone_number']) ?>
        </a>
      </div>
    </div>

    <!-- Catatan -->
    <div class="note-card">
      <span class="note-icon">💡</span>
      <span class="note-text"><b>Catatan:</b> Mohon hubungi sebelum pengiriman</span>
    </div>

  </div>

  <!-- Tombol Aksi sesuai status -->
  <?php if ($status === 'Dikirim'): ?>
    <div class="action-bar">
      <form method="POST">
        <button type="submit" name="action" value="ambil" class="btn-action">Ambil Barang</button>
      </form>
    </div>

  <?php elseif ($status === 'Pengambilan Barang'): ?>
    <div class="action-bar">
      <form method="POST">
        <button type="submit" name="action" value="kirim" class="btn-action">Mulai Pengiriman</button>
      </form>
    </div>

  <?php elseif ($status === 'Dalam Pengiriman'): ?>
    <div class="action-bar">
      <form method="POST">
        <button type="submit" name="action" value="selesai" class="btn-action">Tandai Selesai</button>
      </form>
    </div>
  <?php endif; ?>

</body>

</html>