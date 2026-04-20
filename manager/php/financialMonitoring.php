<?php
session_start();
if (!isset($_SESSION['manager'])) {
    header("Location: login.php");
    exit;
}
include '../../config/connection.php';

$periode = $_GET['periode'] ?? 'harian';

// ── Subquery UNION semua sumber pemasukan ──
// Gunakan o.created_at dari tabel orders sebagai tanggal acuan,
// bukan created_at dari tabel deposit yang bisa ter-update otomatis (ON UPDATE CURRENT_TIMESTAMP)
$union_income = "
    SELECT d.nominal, o.created_at, d.order_id, 'COD' AS metode, NULL AS info
    FROM cod_deposits d
    JOIN orders o ON d.order_id = o.id

    UNION ALL

    SELECT d.nominal, o.created_at, d.order_id, 'Transfer Bank' AS metode, d.nama_bank AS info
    FROM bank_transfer_deposits d
    JOIN orders o ON d.order_id = o.id

    UNION ALL

    SELECT d.nominal, o.created_at, d.order_id, 'E-Wallet' AS metode, d.platform AS info
    FROM ewallet_deposits d
    JOIN orders o ON d.order_id = o.id

    UNION ALL

    SELECT d.nominal, o.created_at, d.order_id, 'QRIS' AS metode, NULL AS info
    FROM qris_deposits d
    JOIN orders o ON d.order_id = o.id
";

// ── Format label berdasarkan periode ──
if ($periode === 'harian') {
    $income_label_fmt = "DATE_FORMAT(created_at, '%d %b')";
    $income_group_fmt = "DATE(created_at)";
    $income_order_fmt = "DATE(created_at)";
    $expense_label_fmt = "DATE_FORMAT(tanggal, '%d %b')";
    $expense_group_fmt = "tanggal";
    $expense_order_fmt = "tanggal";
    $chart_limit = 7;
    $chart_title_suffix = "7 Hari Terakhir";

} elseif ($periode === 'mingguan') {
    $income_label_fmt = "CONCAT('Mg ', WEEK(created_at, 1))";
    $income_group_fmt = "YEAR(created_at), WEEK(created_at, 1)";
    $income_order_fmt = "YEAR(created_at) DESC, WEEK(created_at, 1) DESC";
    $expense_label_fmt = "CONCAT('Mg ', WEEK(tanggal, 1))";
    $expense_group_fmt = "YEAR(tanggal), WEEK(tanggal, 1)";
    $expense_order_fmt = "YEAR(tanggal) DESC, WEEK(tanggal, 1) DESC";
    $chart_limit = 6;
    $chart_title_suffix = "6 Minggu Terakhir";

} else {
    $income_label_fmt = "DATE_FORMAT(created_at, '%b %Y')";
    $income_group_fmt = "YEAR(created_at), MONTH(created_at)";
    $income_order_fmt = "YEAR(created_at) DESC, MONTH(created_at) DESC";
    $expense_label_fmt = "DATE_FORMAT(tanggal, '%b %Y')";
    $expense_group_fmt = "YEAR(tanggal), MONTH(tanggal)";
    $expense_order_fmt = "YEAR(tanggal) DESC, MONTH(tanggal) DESC";
    $chart_limit = 6;
    $chart_title_suffix = "6 Bulan Terakhir";
}

// ── Total pemasukan dari SEMUA metode pembayaran ──
$total_income = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(nominal) as total FROM ($union_income) AS all_income
"))['total'] ?? 0;

// ── Total pengeluaran ──
$total_expense = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(harga_total) as total FROM procurement
"))['total'] ?? 0;

$saldo = $total_income - $total_expense;

// ── Data chart pemasukan per periode ──
$income_map = [];
$res = mysqli_query($conn, "
    SELECT {$income_label_fmt} as label, SUM(nominal) as total
    FROM ({$union_income}) AS all_income
    GROUP BY {$income_group_fmt}
    ORDER BY {$income_order_fmt}
    LIMIT {$chart_limit}
");
while ($row = mysqli_fetch_assoc($res))
    $income_map[$row['label']] = (int) $row['total'];
$income_map = array_reverse($income_map, true);

// ── Data chart pengeluaran per periode ──
$expense_map = [];
$res2 = mysqli_query($conn, "
    SELECT {$expense_label_fmt} as label, SUM(harga_total) as total
    FROM procurement
    GROUP BY {$expense_group_fmt}
    ORDER BY {$expense_order_fmt}
    LIMIT {$chart_limit}
");
while ($row = mysqli_fetch_assoc($res2))
    $expense_map[$row['label']] = (int) $row['total'];
$expense_map = array_reverse($expense_map, true);

// ── Gabungkan label ──
$all_labels = array_unique(array_merge(array_keys($income_map), array_keys($expense_map)));
$chart_labels = array_values($all_labels);
$chart_income = array_map(fn($l) => $income_map[$l] ?? 0, $chart_labels);
$chart_expense = array_map(fn($l) => $expense_map[$l] ?? 0, $chart_labels);

// ── Transaksi terbaru — gunakan o.created_at dari orders sebagai tanggal ──
$transactions = [];

// Pemasukan: COD
$res3 = mysqli_query($conn, "
    SELECT
        'Pemasukan' as tipe,
        CONCAT('COD - Order #', LPAD(d.order_id, 3, '0')) as keterangan,
        d.nominal,
        DATE(o.created_at) as tanggal
    FROM cod_deposits d
    JOIN orders o ON d.order_id = o.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
while ($row = mysqli_fetch_assoc($res3))
    $transactions[] = $row;

// Pemasukan: Transfer Bank
$res4 = mysqli_query($conn, "
    SELECT
        'Pemasukan' as tipe,
        CONCAT('Transfer Bank (', d.nama_bank, ') - Order #', LPAD(d.order_id, 3, '0')) as keterangan,
        d.nominal,
        DATE(o.created_at) as tanggal
    FROM bank_transfer_deposits d
    JOIN orders o ON d.order_id = o.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
while ($row = mysqli_fetch_assoc($res4))
    $transactions[] = $row;

// Pemasukan: E-Wallet
$res5 = mysqli_query($conn, "
    SELECT
        'Pemasukan' as tipe,
        CONCAT('E-Wallet (', d.platform, ') - Order #', LPAD(d.order_id, 3, '0')) as keterangan,
        d.nominal,
        DATE(o.created_at) as tanggal
    FROM ewallet_deposits d
    JOIN orders o ON d.order_id = o.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
while ($row = mysqli_fetch_assoc($res5))
    $transactions[] = $row;

// Pemasukan: QRIS
$res6 = mysqli_query($conn, "
    SELECT
        'Pemasukan' as tipe,
        CONCAT('QRIS - Order #', LPAD(d.order_id, 3, '0')) as keterangan,
        d.nominal,
        DATE(o.created_at) as tanggal
    FROM qris_deposits d
    JOIN orders o ON d.order_id = o.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
while ($row = mysqli_fetch_assoc($res6))
    $transactions[] = $row;

// Pengeluaran: Procurement
$res7 = mysqli_query($conn, "
    SELECT
        'Pengeluaran' as tipe,
        CONCAT('Pembelian Stok - ', supplier) as keterangan,
        harga_total as nominal,
        tanggal
    FROM procurement
    ORDER BY tanggal DESC
    LIMIT 10
");
while ($row = mysqli_fetch_assoc($res7))
    $transactions[] = $row;

// Sort gabungan by tanggal DESC, ambil 10 terbaru
usort($transactions, fn($a, $b) => strcmp($b['tanggal'], $a['tanggal']));
$transactions = array_slice($transactions, 0, 10);

// ── Format singkat ──
function formatShort($num)
{
    if ($num >= 1000000)
        return 'Rp ' . rtrim(rtrim(number_format($num / 1000000, 1, '.', ''), '0'), '.') . 'M';
    if ($num >= 1000)
        return 'Rp ' . rtrim(rtrim(number_format($num / 1000, 1, '.', ''), '0'), '.') . 'K';
    return 'Rp ' . number_format($num, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monitoring Keuangan</title>
    <link rel="stylesheet" href="../css/financialMonitoring.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <div class="header">
        <button class="btn-back" onclick="window.location.href='allMonitoring.php'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Monitoring Keuangan</h2>
        <div style="width:38px;"></div>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="content">

        <!-- Periode Filter -->
        <div class="filter-card">
            <p class="filter-label">Periode:</p>
            <div class="filter-wrap">
                <a href="?periode=harian" class="filter-btn <?= $periode === 'harian' ? 'active' : '' ?>">Harian</a>
                <a href="?periode=mingguan"
                    class="filter-btn <?= $periode === 'mingguan' ? 'active' : '' ?>">Mingguan</a>
                <a href="?periode=bulanan" class="filter-btn <?= $periode === 'bulanan' ? 'active' : '' ?>">Bulanan</a>
            </div>
        </div>

        <!-- Chart -->
        <div class="chart-card">
            <p class="chart-title">
                Laporan Keuangan
                <span class="chart-subtitle"><?= $chart_title_suffix ?></span>
            </p>
            <div class="chart-legend">
                <span class="legend-dot green"></span>
                <span class="legend-text">Pemasukan</span>
                <span class="legend-dot red"></span>
                <span class="legend-text">Pengeluaran</span>
            </div>
            <canvas id="financeChart" height="200"></canvas>
        </div>

        <!-- Saldo -->
        <div class="saldo-card">
            <div>
                <p class="saldo-label">Saldo</p>
                <p class="saldo-value">Rp <?= number_format($saldo, 0, ',', '.') ?></p>
            </div>
            <div class="saldo-icon">Rp</div>
        </div>

        <!-- Pemasukan & Pengeluaran -->
        <div class="summary-wrap">
            <div class="summary-card">
                <div class="summary-top">
                    <i class="fa-solid fa-arrow-trend-up summary-icon green"></i>
                    <span class="summary-type">Pemasukan</span>
                </div>
                <p class="summary-value green"><?= formatShort($total_income) ?></p>
            </div>
            <div class="summary-card">
                <div class="summary-top">
                    <i class="fa-solid fa-arrow-trend-down summary-icon red"></i>
                    <span class="summary-type">Pengeluaran</span>
                </div>
                <p class="summary-value red"><?= formatShort($total_expense) ?></p>
            </div>
        </div>

        <!-- Transaksi Terbaru -->
        <p class="section-label">Transaksi Terbaru</p>

        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-receipt"></i>
                <p>Belum ada transaksi</p>
            </div>
        <?php else: ?>
            <?php foreach ($transactions as $trx):
                $is_income = $trx['tipe'] === 'Pemasukan';
                $sign = $is_income ? '+' : '-';
                $color = $is_income ? 'green' : 'red';
                $icon = $is_income ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
                $badge = $is_income ? 'badge-green' : 'badge-red';
                $tanggal = date('d M Y', strtotime($trx['tanggal']));
                ?>
                <div class="trx-card">
                    <div class="trx-top">
                        <span class="trx-badge <?= $badge ?>">
                            <i class="fa-solid <?= $icon ?>"></i>
                            <?= $trx['tipe'] ?>
                        </span>
                        <span class="trx-amount <?= $color ?>">
                            <?= $sign ?>Rp <?= number_format($trx['nominal'], 0, ',', '.') ?>
                        </span>
                    </div>
                    <p class="trx-desc"><?= htmlspecialchars($trx['keterangan']) ?></p>
                    <p class="trx-date"><?= $tanggal ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <script>
        const ctx = document.getElementById('financeChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: <?= json_encode($chart_income) ?>,
                        backgroundColor: '#3ab87a',
                        borderRadius: 6,
                        borderSkipped: false,
                        barPercentage: 0.55,
                        categoryPercentage: 0.75,
                    },
                    {
                        label: 'Pengeluaran',
                        data: <?= json_encode($chart_expense) ?>,
                        backgroundColor: '#e74c3c',
                        borderRadius: 6,
                        borderSkipped: false,
                        barPercentage: 0.55,
                        categoryPercentage: 0.75,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                let val = ctx.parsed.y;
                                if (val >= 1000000) return ctx.dataset.label + ': Rp ' + (val / 1000000).toFixed(1) + 'M';
                                if (val >= 1000) return ctx.dataset.label + ': Rp ' + (val / 1000).toFixed(0) + 'K';
                                return ctx.dataset.label + ': Rp ' + val.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11, family: 'Nunito', weight: '600' },
                            color: '#888',
                            maxRotation: 30,
                        }
                    },
                    y: {
                        grid: { color: '#f0f0f0' },
                        ticks: {
                            font: { size: 11, family: 'Nunito' },
                            color: '#888',
                            callback: val => {
                                if (val >= 1000000) return (val / 1000000) + 'M';
                                if (val >= 1000) return (val / 1000) + 'k';
                                return val;
                            }
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>