<?php
session_start();
if (!isset($_SESSION['manager'])) {
    header("Location: login.php");
    exit;
}
include '../../config/connection.php';

$manager = $_SESSION['manager'];

// Statistik dari database
$total_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock) as total FROM products"))['total'] ?? 0;
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders"))['total'] ?? 0;
$total_approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status IN ('Diproses', 'Pengambilan Barang', 'Dalam Pengiriman', 'Dikirim', 'Selesai')"))['total'] ?? 0;
$total_rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status='Dibatalkan'"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Manager</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <!-- Header -->
    <div class="header">
        <h2 class="header-title">Dashboard Manager</h2>
        <a href="logout.php" class="btn-logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>

    <div class="content">

        <!-- Welcome Card -->
        <div class="welcome-card">
            <h2 class="welcome-title">Selamat Datang,
                <?= htmlspecialchars($manager) ?>
            </h2>
            <p class="welcome-text">Monitor dan kelola sistem dengan mudah</p>
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
        </div>

        <!-- Statistik -->
        <h3 class="section-label">Statistik Hari Ini</h3>
        <div class="stats-grid">

            <div class="stat-card">
                <i class="fa-solid fa-box stat-icon green"></i>
                <p class="stat-label">Total Stok</p>
                <p class="stat-value green">
                    <?= number_format($total_stock, 0, ',', '.') ?>
                </p>
                <div class="stat-circle"></div>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-clipboard-list stat-icon yellow"></i>
                <p class="stat-label">Pesanan Masuk</p>
                <p class="stat-value yellow">
                    <?= $total_orders ?>
                </p>
                <div class="stat-circle"></div>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-circle-check stat-icon green"></i>
                <p class="stat-label">Disetujui</p>
                <p class="stat-value green">
                    <?= $total_approved ?>
                </p>
                <div class="stat-circle"></div>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-circle-xmark stat-icon red"></i>
                <p class="stat-label">Ditolak</p>
                <p class="stat-value red">
                    <?= $total_rejected ?>
                </p>
                <div class="stat-circle"></div>
            </div>

        </div>

        <!-- Menu Utama -->
        <h3 class="section-label">Menu Utama</h3>
        <div class="menu-list">

            <div class="menu-item" onclick="window.location.href='../php/procurement.php'">
                <div class="menu-icon green">
                    <i class="fa-solid fa-box"></i>
                </div>
                <span class="menu-label">Pengadaan Barang</span>
                <i class="fa-solid fa-chevron-right menu-arrow"></i>
            </div>

            <div class="menu-item" onclick="window.location.href='../php/allMonitoring.php'">
                <div class="menu-icon yellow">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <span class="menu-label">Monitoring Laporan</span>
                <i class="fa-solid fa-chevron-right menu-arrow"></i>
            </div>

            <div class="menu-item" onclick="window.location.href='../php/employeeAssignment.php'">
                <div class="menu-icon blue">
                    <i class="fa-solid fa-users"></i>
                </div>
                <span class="menu-label">Penugasan Karyawan</span>
                <i class="fa-solid fa-chevron-right menu-arrow"></i>
            </div>

            <div class="menu-item" onclick="window.location.href='../php/stockMonitoring.php'">
                <div class="menu-icon purple">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <span class="menu-label">Monitoring Stok</span>
                <i class="fa-solid fa-chevron-right menu-arrow"></i>
            </div>

        </div>

    </div>

</body>

</html>