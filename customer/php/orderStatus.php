<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
include '../../config/connection.php';

$customer_id = $_SESSION['id'];

// Ambil semua pesanan milik customer ini
$result = mysqli_query($conn, "SELECT * FROM orders WHERE customer_id='$customer_id' ORDER BY created_at DESC");

// Cek apakah ada pesanan yang sedang dikirim
$has_shipping = false;
$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['status'] === 'Dikirim')
        $has_shipping = true;
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Status Pesanan</title>
    <link rel="stylesheet" href="../css/orderStatus.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <!-- Header -->
    <div class="header">
        <button class="btn-back" onclick="window.location.href='dashboard.php'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Status Pesanan</h2>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="content">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <p>Belum ada pesanan</p>
            </div>
        <?php else: ?>

            <?php foreach ($orders as $order):
                $status = $order['status'];

                // Tentukan ikon berdasarkan status
                if ($status === 'Dikirim') {
                    $icon = 'fa-truck';
                } else {
                    $icon = 'fa-box';
                }

                // Badge label & warna
                $badge_map = [
                    'Menunggu Konfirmasi' => ['label' => 'Menunggu Verif', 'class' => 'badge-yellow'],
                    'Diproses' => ['label' => 'Sedang Diproses', 'class' => 'badge-green'],
                    'Dikirim' => ['label' => 'Dalam Pengiriman', 'class' => 'badge-blue'],
                    'Selesai' => ['label' => 'Selesai', 'class' => 'badge-gray'],
                    'Dibatalkan' => ['label' => 'Dibatalkan', 'class' => 'badge-red'],
                ];
                $badge = $badge_map[$status] ?? ['label' => $status, 'class' => 'badge-gray'];

                // Timeline steps dan status aktif
                $steps = ['Pesanan Telah Dibuat', 'Diproses', 'Dalam Pengiriman', 'Selesai'];
                if ($status === 'Menunggu Konfirmasi') {
                    $steps = ['Menunggu Verifikasi', 'Diproses', 'Dalam Pengiriman', 'Selesai'];
                    $active_count = 1;
                } elseif ($status === 'Diproses') {
                    $steps = ['Pesanan Telah Dibuat', 'Diproses', 'Dalam Pengiriman', 'Selesai'];
                    $active_count = 2;
                } elseif ($status === 'Dikirim') {
                    $steps = ['Pesanan Diterima', 'Diproses', 'Dalam Pengiriman', 'Selesai'];
                    $active_count = 3;
                } elseif ($status === 'Selesai') {
                    $steps = ['Pesanan Diterima', 'Diproses', 'Dalam Pengiriman', 'Selesai'];
                    $active_count = 4;
                } else {
                    $active_count = 0;
                }

                $order_date = date('d M Y', strtotime($order['created_at']));
                $order_id = 'ORD' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
                ?>

                <div class="order-card">
                    <div class="order-header">
                        <div class="order-left">
                            <i class="fa-solid <?= $icon ?> order-icon"></i>
                            <div>
                                <p class="order-id">ID: <?= $order_id ?></p>
                                <p class="order-product"><?= htmlspecialchars($order['product_name']) ?></p>
                            </div>
                        </div>
                        <span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                    </div>

                    <!-- Timeline -->
                    <div class="timeline">
                        <?php foreach ($steps as $i => $step):
                            $is_active = ($i + 1) <= $active_count;
                            $is_last = $i === count($steps) - 1;
                            ?>
                            <div class="timeline-item">
                                <div class="timeline-left">
                                    <div class="dot <?= $is_active ? 'dot-active' : 'dot-inactive' ?>"></div>
                                    <?php if (!$is_last): ?>
                                        <div
                                            class="line <?= $is_active && ($i + 2) <= $active_count ? 'line-active' : 'line-inactive' ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p class="step-label <?= $is_active ? 'step-active' : 'step-inactive' ?>"><?= $step ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <p class="order-date"><?= $order_date ?></p>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>

</html>