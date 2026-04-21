<?php
session_start();
if (!isset($_SESSION['employee'])) {
    header("Location: index.php");
    exit;
}
include '../../config/connection.php';

$employee_id = $_SESSION['employee_id'];

$result = mysqli_query($conn, "
    SELECT o.*, c.username as customer_name, c.address, c.phone_number
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.employee_id = '$employee_id'
    AND o.status IN ('Dikirim', 'Pengambilan Barang', 'Dalam Pengiriman')
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
    <title>Daftar Tugas</title>
    <link rel="stylesheet" href="../css/taskList.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <div class="header">
        <button class="btn-back" onclick="window.location.href='dashboard.php'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Daftar Tugas</h2>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="content">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <p>Tidak ada tugas saat ini</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order):
                $order_id = 'ORD' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
                $is_cod = (strtolower($order['payment_method']) === 'cod');
                ?>
                <div class="order-card">
                    <div class="card-top">
                        <p class="order-id"><?= $order_id ?></p>
                        <div class="badge-wrap">
                            <?php if ($is_cod): ?>
                                <span class="badge badge-yellow">COD</span>
                            <?php endif; ?>
                            <span class="badge badge-green">Ditugaskan</span>
                        </div>
                    </div>

                    <p class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></p>

                    <p class="meta-label">Produk:</p>
                    <p class="meta-value"><?= htmlspecialchars($order['product_name']) ?> (<?= $order['quantity'] ?> unit)</p>

                    <div class="address-wrap">
                        <i class="fa-solid fa-location-dot addr-icon"></i>
                        <div>
                            <p class="meta-label">Alamat:</p>
                            <p class="meta-value"><?= htmlspecialchars($order['address']) ?></p>
                        </div>
                    </div>

                    <a href="deliveryDetail.php?id=<?= $order['id'] ?>" class="btn-terima">Terima Tugas</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>

</html>