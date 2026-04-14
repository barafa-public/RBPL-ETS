<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pembayaran Berhasil</title>
  <link rel="stylesheet" href="../css/payment.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

  <div class="header">
    <h2 class="header-title">Pembayaran</h2>
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>
  </div>

  <div class="success-wrap">
    <div class="success-icon">
      <i class="fa-solid fa-check"></i>
    </div>
    <h2 class="success-title">Pembayaran Berhasil!</h2>
    <p class="success-text">Pesanan Anda sedang diproses</p>
    <button class="btn-home" onclick="window.location.href='dashboard.php'">
      Kembali ke Beranda
    </button>
  </div>

  <script>
    sessionStorage.removeItem('order');
  </script>

</body>
</html>