<?php
session_start();
if (!isset($_SESSION['manager'])) {
    header("Location: login.php");
    exit;
}
include '../../config/connection.php';

// Handle submit penugasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['employee_id'])) {
    $order_id = (int) $_POST['order_id'];
    $employee_id = (int) $_POST['employee_id'];

    if ($employee_id > 0) {
        mysqli_query($conn, "
            UPDATE orders 
            SET employee_id = '$employee_id', status = 'Dikirim' 
            WHERE id = '$order_id'
        ");

        $_SESSION['assignment_success'] = true;
        header("Location: assignSuccess.php");
        exit;
    }
}

// Ambil pesanan yang sudah disetujui dan belum ditugaskan
$result = mysqli_query($conn, "
    SELECT o.*, c.username as customer_name, c.address
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.status = 'Diproses' AND (o.employee_id IS NULL OR o.employee_id = 0)
    ORDER BY o.created_at ASC
");
$orders = [];
while ($row = mysqli_fetch_assoc($result))
    $orders[] = $row;

// Ambil daftar karyawan
$emp_result = mysqli_query($conn, "SELECT id, username FROM employees ORDER BY username ASC");
$employees = [];
while ($row = mysqli_fetch_assoc($emp_result))
    $employees[] = $row;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Penugasan Karyawan</title>
    <link rel="stylesheet" href="../css/employeeAssignment.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <div class="header">
        <button class="btn-back" onclick="window.location.href='dashboard.php'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Penugasan Karyawan</h2>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="content">

        <!-- Info card -->
        <div class="info-card">
            <p class="info-title">Pesanan yang Disetujui</p>
            <p class="info-sub">Tugaskan karyawan untuk menangani pengiriman</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <p>Semua pesanan sudah ditugaskan</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order):
                $order_id = 'ORD' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
                ?>
                <div class="order-card">
                    <p class="order-id"><?= $order_id ?></p>
                    <p class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></p>
                    <p class="order-meta">Produk: <?= htmlspecialchars($order['product_name']) ?></p>
                    <p class="order-meta">Alamat: <?= htmlspecialchars($order['address']) ?></p>

                    <form method="POST" class="assign-form">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>" />
                        <p class="select-label">Pilih Karyawan:</p>
                        <div class="select-wrap">
                            <select name="employee_id" required>
                                <option value="" disabled selected>Pilih karyawan...</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fa-solid fa-chevron-down select-icon"></i>
                        </div>
                        <button type="submit" class="btn-assign">Tugaskan</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>

</html>