<?php
session_start();
if (!isset($_SESSION['employee'])) {
    header("Location: index.php");
    exit;
}
include '../../config/connection.php';

$id = (int) ($_GET['id'] ?? 0);
$employee_id = $_SESSION['employee_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal = (int) str_replace(['.', ','], '', $_POST['nominal'] ?? 0);

    if ($nominal <= 0) {
        $error = 'Nominal setoran harus lebih dari 0.';
    } elseif (empty($_FILES['bukti']['name']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Bukti setoran wajib diupload sebelum menyelesaikan pengiriman.';
    } else {
        $ext_allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $ext_allowed)) {
            $error = 'Format file harus PNG atau JPG.';
        } elseif ($_FILES['bukti']['size'] > 5 * 1024 * 1024) {
            $error = 'Ukuran file maksimal 5MB.';
        } else {
            $upload_dir = '../../uploads/cod/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);
            $filename = 'cod_' . $id . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['bukti']['tmp_name'], $upload_path)) {
                // Simpan ke tabel cod_deposits
                $stmt = $conn->prepare("INSERT INTO cod_deposits (order_id, employee_id, nominal, bukti_file) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $id, $employee_id, $nominal, $filename);
                $stmt->execute();

                // Baru update status Selesai setelah bukti berhasil diupload
                mysqli_query($conn, "UPDATE orders SET status='Selesai' WHERE id='$id' AND employee_id='$employee_id'");

                header("Location: codSuccess.php");
                exit;
            } else {
                $error = 'Gagal mengupload file. Coba lagi.';
            }
        }
    }
}

// Ambil data pesanan — validasi milik karyawan ini dan metode COD
$order = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM orders 
    WHERE id='$id' AND employee_id='$employee_id' AND payment_method LIKE '%COD%'
"));
if (!$order) {
    header("Location: taskList.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Setoran COD</title>
    <link rel="stylesheet" href="../css/codDeposit.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <div class="header">
        <button class="btn-back" onclick="window.location.href='deliveryDetail.php?id=<?= $id ?>'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Setoran COD</h2>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="content">
        <div class="form-card">
            <p class="form-title">Form Setoran COD</p>
            <p class="form-sub">Laporkan hasil pembayaran COD yang telah diterima</p>

            <?php if (!empty($error)): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Nominal Setoran</label>
                    <div class="nominal-wrap">
                        <span class="nominal-prefix">Rp</span>
                        <input type="text" name="nominal" id="nominalInput" placeholder="0"
                            value="<?= isset($_POST['nominal']) ? htmlspecialchars($_POST['nominal']) : number_format($order['total'], 0, ',', '.') ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label>Bukti Setoran</label>
                    <label for="buktiInput" class="upload-area" id="uploadArea">
                        <i class="fa-solid fa-arrow-up-from-bracket upload-icon"></i>
                        <p class="upload-text" id="uploadText">Upload foto bukti transfer</p>
                        <p class="upload-hint">PNG, JPG hingga 5MB</p>
                    </label>
                    <input type="file" name="bukti" id="buktiInput" accept=".png,.jpg,.jpeg" style="display:none" />
                </div>

                <button type="submit" class="btn-kirim">Kirim Setoran</button>

            </form>
        </div>

        <!-- Tips -->
        <div class="tips-card">
            <span class="tips-icon">💡</span>
            <span class="tips-text"><b>Tips:</b> Pastikan bukti setoran jelas dan terbaca dengan baik</span>
        </div>

    </div>

    <script>
        // Preview nama file saat dipilih
        document.getElementById('buktiInput').addEventListener('change', function () {
            const name = this.files[0]?.name || 'Upload foto bukti transfer';
            document.getElementById('uploadText').textContent = name;
            document.getElementById('uploadArea').classList.add('has-file');
        });

        // Format nominal input dengan titik
        document.getElementById('nominalInput').addEventListener('input', function () {
            let val = this.value.replace(/\D/g, '');
            this.value = val ? parseInt(val).toLocaleString('id-ID') : '';
        });
    </script>

</body>

</html>