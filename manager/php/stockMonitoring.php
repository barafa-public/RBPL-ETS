<?php
session_start();
if (!isset($_SESSION['manager'])) {
    header("Location: login.php");
    exit;
}
include '../../config/connection.php';

// Periode filter
$periode = $_GET['periode'] ?? 'harian';

// Tentukan filter tanggal berdasarkan periode
if ($periode === 'harian') {
    $date_filter = "DATE(created_at) = CURDATE()";
} elseif ($periode === 'mingguan') {
    $date_filter = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
} else {
    $date_filter = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
}

// Total produk (semua stok)
$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock) as total FROM products"))['total'] ?? 0;

// Produk rusak (orders dibatalkan pada periode ini)
$produk_rusak = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM orders 
    WHERE status = 'Dibatalkan' AND $date_filter
"))['total'] ?? 0;

// Stok per produk
$produk_result = mysqli_query($conn, "SELECT product_name, stock FROM products ORDER BY id ASC");
$produk_list = [];
while ($row = mysqli_fetch_assoc($produk_result))
    $produk_list[] = $row;

// Data chart: jumlah terjual per produk pada periode ini
$chart_data = [];
foreach ($produk_list as $p) {
    $nama = mysqli_real_escape_string($conn, $p['product_name']);
    $terjual = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COALESCE(SUM(quantity), 0) as total 
        FROM orders 
        WHERE product_name = '$nama' AND status != 'Dibatalkan' AND $date_filter
    "))['total'] ?? 0;

    $pengadaan = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COALESCE(SUM(jumlah), 0) as total 
        FROM procurement 
        WHERE nama_barang = '$nama' AND $date_filter
    "))['total'] ?? 0;

    $chart_data[] = [
        'label' => $p['product_name'],
        'terjual' => (int) $terjual,
        'pengadaan' => (int) $pengadaan,
        'stok' => (int) $p['stock'],
    ];
}

$chart_json = json_encode($chart_data);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Laporan Stok</title>
    <link rel="stylesheet" href="../css/stockMonitoring.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>

<body>

    <div class="header">
        <button class="btn-back" onclick="window.location.href='dashboard.php'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Laporan Stok</h2>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="content">

        <!-- Periode Filter -->
        <div class="filter-card">
            <p class="filter-label">Periode:</p>
            <div class="filter-tabs">
                <a href="?periode=harian" class="filter-tab <?= $periode === 'harian' ? 'active' : '' ?>">Harian</a>
                <a href="?periode=mingguan"
                    class="filter-tab <?= $periode === 'mingguan' ? 'active' : '' ?>">Mingguan</a>
                <a href="?periode=bulanan" class="filter-tab <?= $periode === 'bulanan' ? 'active' : '' ?>">Bulanan</a>
            </div>
        </div>

        <!-- Chart Card -->
        <div class="chart-card">
            <p class="chart-title">Laporan Stok</p>
            <div class="chart-wrap">
                <canvas id="stockChart"></canvas>
            </div>
        </div>

        <!-- Statistik Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <p class="stat-label">Total Produk</p>
                <p class="stat-value"><?= number_format($total_produk, 0, ',', '.') ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Produk Rusak</p>
                <p class="stat-value"><?= $produk_rusak ?></p>
            </div>
            <?php foreach ($produk_list as $p): ?>
                <div class="stat-card">
                    <p class="stat-label"><?= htmlspecialchars($p['product_name']) ?></p>
                    <p class="stat-value"><?= number_format($p['stock'], 0, ',', '.') ?></p>
                </div>
          <?php endforeach; ?>
        </div>

    </div>

    <script>
        const rawData = <?= $chart_json ?>;

        const labels = rawData.map(d => d.label);
        const terjual = rawData.map(d => d.terjual);
        const pengadaan = rawData.map(d => d.pengadaan);

        // Bangun dataset bar bergantian: terjual & pengadaan per produk
        const datasets = [];
        rawData.forEach((d, i) => {
            datasets.push({
                label: d.label + ' - Terjual',
                data: rawData.map((_, j) => j === i ? d.terjual : 0),
                backgroundColor: '#3ab87a',
                borderRadius: 6,
                barPercentage: 0.5,
                categoryPercentage: 0.7,
            });
            datasets.push({
                label: d.label + ' - Pengadaan',
                data: rawData.map((_, j) => j === i ? d.pengadaan : 0),
                backgroundColor: '#3ab87a',
                borderRadius: 6,
                barPercentage: 0.5,
                categoryPercentage: 0.7,
            });
        });

        const ctx = document.getElementById('stockChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Nunito', size: 12 },
                            color: '#888'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 160,
                        ticks: {
                            stepSize: 40,
                            font: { family: 'Nunito', size: 11 },
                            color: '#aaa'
                        },
                        grid: {
                            color: '#e8e8e8',
                            borderDash: [4, 4]
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>