<?php
session_start();
if (!isset($_SESSION['manager'])) {
    header("Location: login.php");
    exit;
}
include '../../config/connection.php';

$action_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])) {
    $order_id = (int) $_POST['order_id'];

    if ($_POST['action'] === 'approve') {
        // Ambil data order terlebih dahulu
        $order_row = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT product_name, quantity FROM orders WHERE id='$order_id' AND status='Menunggu Konfirmasi' LIMIT 1"
        ));

        if ($order_row) {
            $product_name = mysqli_real_escape_string($conn, $order_row['product_name']);
            $order_quantity = (int) $order_row['quantity'];

            // Cek stok mencukupi
            $stok_row = mysqli_fetch_assoc(mysqli_query(
                $conn,
                "SELECT stock FROM products WHERE product_name='$product_name' LIMIT 1"
            ));

            if (!$stok_row || $stok_row['stock'] < $order_quantity) {
                $stok_tersisa = $stok_row['stock'] ?? 0;
                $action_error = 'Pesanan tidak dapat disetujui: stok "' . htmlspecialchars($order_row['product_name']) . '" tidak mencukupi (tersisa ' . $stok_tersisa . ', dibutuhkan ' . $order_quantity . ').';
            } else {
                // Setujui order + kurangi stok
                mysqli_query($conn, "UPDATE orders SET status='Diproses' WHERE id='$order_id'");
                mysqli_query($conn, "UPDATE products SET stock = stock - $order_quantity WHERE product_name='$product_name'");
                header("Location: orderMonitoring.php");
                exit;
            }
        }

    } elseif ($_POST['action'] === 'reject') {
        mysqli_query($conn, "UPDATE orders SET status='Dibatalkan' WHERE id='$order_id'");
        header("Location: orderMonitoring.php");
        exit;
    }
}

$result = mysqli_query($conn, "
    SELECT o.*, c.username as customer_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    ORDER BY o.created_at DESC
");
$orders = [];
while ($row = mysqli_fetch_assoc($result))
    $orders[] = $row;

// Ambil stok semua produk untuk ditampilkan di card
$stok_map = [];
$stok_res = mysqli_query($conn, "SELECT product_name, stock FROM products");
while ($s = mysqli_fetch_assoc($stok_res))
    $stok_map[$s['product_name']] = (int) $s['stock'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monitoring Pesanan</title>
    <link rel="stylesheet" href="../css/monitoring.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <div class="header">
        <button class="btn-back" onclick="window.location.href='allMonitoring.php'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Monitoring Pesanan</h2>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="tab-wrap">
        <a href="orderMonitoring.php" class="tab active">Semua</a>
        <a href="awaitedMonitoring.php" class="tab">Menunggu</a>
        <a href="approvedMonitoring.php" class="tab">Disetujui</a>
        <a href="rejectedMonitoring.php" class="tab">Ditolak</a>
    </div>

    <div class="content">

        <?php if (!empty($action_error)): ?>
                <div class="alert-error" style="margin-bottom:16px; padding:12px 14px; background:#fdecea; color:#c0392b; border-radius:12px; font-size:13.5px; font-weight:600;">
                    <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>
                    <?= $action_error ?>
                </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <p>Belum ada pesanan</p>
                </div>
        <?php else: ?>
                <?php foreach ($orders as $order):
                    $order_id = 'ORD' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
                    $status = $order['status'];
                    $stok_produk = $stok_map[$order['product_name']] ?? 0;
                    $stok_cukup = $stok_produk >= (int) $order['quantity'];

                    if ($status === 'Menunggu Konfirmasi') {
                        $badge_label = 'Menunggu';
                        $badge_class = 'badge-yellow';
                    } elseif (in_array($status, ['Diproses', 'Dikirim', 'Selesai'])) {
                        $badge_label = 'Disetujui';
                        $badge_class = 'badge-green';
                    } else {
                        $badge_label = 'Ditolak';
                        $badge_class = 'badge-red';
                    }
                    $show_action = ($status === 'Menunggu Konfirmasi');
                    ?>
                        <div class="order-block">

                            <div class="order-card">
                                <div class="card-top">
                                    <div>
                                        <p class="order-id"><?= $order_id ?></p>
                                        <p class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></p>
                                    </div>
                                    <span class="badge <?= $badge_class ?>"><?= $badge_label ?></span>
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
                                    <?php if ($show_action): ?>
                                            <div class="detail-row">
                                                <span class="detail-label">Stok saat ini:</span>
                                                <span class="detail-value"
                                                      style="font-weight:700; color:<?= $stok_cukup ? '#2e7d32' : '#c0392b' ?>;">
                                                    <?= $stok_produk ?> unit
                                                    <?= !$stok_cukup ? ' &#9888; Tidak cukup' : '' ?>
                                                </span>
                                            </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($show_action): ?>
                                        <div class="verify-label">Verifikasi Pesanan</div>
                                <?php endif; ?>
                            </div>

                            <?php if ($show_action): ?>
                                    <form method="POST" class="action-wrap">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>" />
                                        <?php if ($stok_cukup): ?>
                                                <button type="submit" name="action" value="approve" class="btn-approve">Setuju</button>
                                        <?php else: ?>
                                                <button type="button" class="btn-approve"
                                                    style="opacity:0.4; cursor:not-allowed;"
                                                    title="Stok tidak mencukupi untuk menyetujui pesanan ini"
                                                    disabled>Setuju</button>
                                        <?php endif; ?>
                                        <button type="submit" name="action" value="reject" class="btn-reject">Tolak</button>
                                    </form>
                            <?php endif; ?>

                        </div>
                <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>

</html>