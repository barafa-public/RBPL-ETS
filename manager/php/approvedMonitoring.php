<?php
session_start();
if (!isset($_SESSION['manager'])) {
    header("Location: login.php");
    exit;
}
include '../../config/connection.php';

$result = mysqli_query($conn, "
    SELECT o.*, c.username as customer_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.status IN ('Diproses', 'Dikirim', 'Selesai')
    ORDER BY o.created_at DESC
");
$orders = [];
while ($row = mysqli_fetch_assoc($result))
    $orders[] = $row;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monitoring - Disetujui</title>
    <link rel="stylesheet" href="../css/monitoring.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <div class="header">
        <button class="btn-back" onclick="window.location.href='dashboard.php'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Monitoring Pesanan</h2>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="tab-wrap">
        <a href="allMonitoring.php" class="tab">Semua</a>
        <a href="awaitedMonitoring.php" class="tab">Menunggu</a>
        <a href="approvedMonitoring.php" class="tab active">Disetujui</a>
        <a href="rejectedMonitoring.php" class="tab">Ditolak</a>
    </div>

    <div class="content">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <p>Tidak ada pesanan disetujui</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order):
                $order_id = 'ORD' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
                ?>
                <div class="order-block">
                    <div class="order-card">
                        <div class="card-top">
                            <div>
                                <p class="order-id"><?= $order_id ?></p>
                                <p class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></p>
                            </div>
                            <span class="badge badge-green">Disetujui</span>
                        </div>
                        <div class="card-detail">
                            <div class="detail-row">
                                <span class="detail-label">Produk:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['product_name']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Jumlah:</span>
                                <span class="detail-value"><?= $order['quantity'] ?> unit</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Total:</span>
                                <span class="detail-value green">Rp <?= number_format($order['total'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>

</html>