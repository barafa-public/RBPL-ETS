<?php
session_start();
if (!isset($_SESSION['employee'])) {
    header("Location: login.php");
    exit;
}
include '../../config/connection.php';

$employee = $_SESSION['employee'];
$employee_id = $_SESSION['employee_id'];

// Tugas = semua order yang pernah ditugaskan (tidak berkurang meski selesai)
$total_tugas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM orders 
    WHERE employee_id='$employee_id'
"))['total'] ?? 0;

// Proses = sedang berjalan (belum selesai)
$tugas_proses = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM orders 
    WHERE employee_id='$employee_id' 
    AND status IN ('Dikirim', 'Pengambilan Barang', 'Dalam Pengiriman')
"))['total'] ?? 0;

// Selesai = sudah selesai dikirim
$tugas_selesai = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM orders 
    WHERE employee_id='$employee_id' 
    AND status = 'Selesai'
"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Karyawan</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <!-- Header -->
    <div class="header">
        <h2 class="header-title">Dashboard Karyawan</h2>
        <a href="logout.php" class="btn-logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>

    <div class="content">

        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="truck-icon">
                <i class="fa-solid fa-truck"></i>
            </div>
            <h2 class="welcome-title">Halo, <?= htmlspecialchars($employee) ?>!</h2>
            <p class="welcome-text">Semangat bekerja hari ini</p>
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
        </div>

        <!-- Notifikasi Tugas Baru (hanya muncul jika masih ada yang aktif) -->
        <?php if ($tugas_proses > 0): ?>
            <div class="notif-card">
                <div class="notif-icon">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <div class="notif-text">
                    <p class="notif-title">Tugas Baru!</p>
                    <p class="notif-sub">Anda memiliki <?= $tugas_proses ?> tugas pengiriman</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistik Tugas -->
        <h3 class="section-label">Statistik Tugas</h3>
        <div class="stats-grid">

            <div class="stat-card">
                <i class="fa-solid fa-clipboard-list stat-icon green"></i>
                <p class="stat-label">Tugas</p>
                <p class="stat-value green"><?= $total_tugas ?></p>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-bolt stat-icon green"></i>
                <p class="stat-label">Proses</p>
                <p class="stat-value green"><?= $tugas_proses ?></p>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-circle-check stat-icon green"></i>
                <p class="stat-label">Selesai</p>
                <p class="stat-value green"><?= $tugas_selesai ?></p>
            </div>

        </div>

        <!-- Tombol Lihat Daftar Tugas -->
        <button class="btn-tugas" onclick="window.location.href='taskList.php'">
            Lihat Daftar Tugas
        </button>

    </div>

</body>

</html>