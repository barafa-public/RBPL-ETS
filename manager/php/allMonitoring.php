<?php
session_start();
if (!isset($_SESSION['manager'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Laporan</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <div class="header">
        <button class="btn-back" onclick="window.location.href='dashboard.php'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Laporan</h2>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="content">

        <div class="menu-list">

            <div class="menu-item" onclick="window.location.href='financialMonitoring.php'">
                <div class="menu-icon green">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
                <span class="menu-label">Monitoring Keuangan</span>
                <i class="fa-solid fa-chevron-right menu-arrow"></i>
            </div>

            <div class="menu-item" onclick="window.location.href='orderMonitoring.php'">
                <div class="menu-icon purple">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <span class="menu-label">Monitoring Pesanan</span>
                <i class="fa-solid fa-chevron-right menu-arrow"></i>
            </div>

        </div>

    </div>

</body>

</html>