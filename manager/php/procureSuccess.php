<?php
session_start();
if (!isset($_SESSION['manager']) || !isset($_SESSION['procurement_success'])) {
    header("Location: dashboard.php");
    exit;
}
$data = $_SESSION['procurement_success'];
unset($_SESSION['procurement_success']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pengadaan Berhasil</title>
    <link rel="stylesheet" href="../css/procurement.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <!-- Header -->
    <div class="header">
        <button class="btn-back" onclick="window.location.href='dashboard.php'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Pengadaan Barang</h2>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="success-wrap">
        <div class="success-icon">
            <i class="fa-solid fa-check"></i>
        </div>
        <h2 class="success-title">Stok Berhasil Ditambahkan!</h2>
        <p class="success-status">Status: <?= htmlspecialchars($data['status']) ?></p>
    </div>

</body>

</html>