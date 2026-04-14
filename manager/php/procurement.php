<?php
session_start();
if (!isset($_SESSION['manager'])) {
    header("Location: login.php");
    exit;
}
include '../../config/connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_barang = trim($_POST['kode_barang']);
    $nama_barang = trim($_POST['nama_barang']);
    $jumlah = (int) $_POST['jumlah'];
    $tanggal = $_POST['tanggal'];
    $supplier = trim($_POST['supplier']);
    $status = 'Tersedia';

    if (empty($kode_barang) || empty($nama_barang) || $jumlah <= 0 || empty($tanggal) || empty($supplier)) {
        $error = 'Semua field harus diisi dengan benar!';
    } else {
        // Simpan ke tabel procurement
        $stmt = $conn->prepare("INSERT INTO procurement (kode_barang, nama_barang, jumlah, tanggal, supplier, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisss", $kode_barang, $nama_barang, $jumlah, $tanggal, $supplier, $status);

        if ($stmt->execute()) {
            // Update stok di tabel products jika nama barang cocok
            $conn->query("UPDATE products SET stock = stock + $jumlah WHERE product_name = '$nama_barang'");

            // Simpan data ke session untuk halaman sukses
            $_SESSION['procurement_success'] = [
                'kode_barang' => $kode_barang,
                'nama_barang' => $nama_barang,
                'jumlah' => $jumlah,
                'tanggal' => $tanggal,
                'supplier' => $supplier,
                'status' => $status,
            ];

            header("Location: procureSuccess.php");
            exit;
        } else {
            $error = 'Gagal menyimpan data. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pengadaan Barang</title>
    <link rel="stylesheet" href="../css/procurement.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <!-- Header -->
    <div class="header">
        <button class="btn-back" onclick="window.location.href='dashboard.php'">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h2 class="header-title">Pengadaan Barang</h2>
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="content">
        <div class="section-card">
            <h3 class="form-title">Form Pengadaan Barang</h3>

            <?php if (!empty($error)): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group">
                    <label>Kode Barang</label>
                    <input type="text" name="kode_barang" placeholder="Masukkan kode barang"
                        value="<?= htmlspecialchars($_POST['kode_barang'] ?? '') ?>" required />
                </div>

                <div class="form-group">
                    <label>Nama Barang</label>
                    <select name="nama_barang" required>
                        <option value="" disabled <?= empty($_POST['nama_barang']) ? 'selected' : '' ?>>Masukkan nama
                            barang</option>
                        <?php
                        $products = mysqli_query($conn, "SELECT product_name FROM products");
                        while ($p = mysqli_fetch_assoc($products)):
                            $sel = (isset($_POST['nama_barang']) && $_POST['nama_barang'] === $p['product_name']) ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($p['product_name']) ?>" <?= $sel ?>>
                                <?= htmlspecialchars($p['product_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah" placeholder="Jumlah stok" min="1"
                        value="<?= htmlspecialchars($_POST['jumlah'] ?? '') ?>" required />
                </div>

                <div class="form-group">
                    <label>Tanggal</label>
                    <div class="input-icon-wrap">
                        <input type="date" name="tanggal" value="<?= htmlspecialchars($_POST['tanggal'] ?? '') ?>"
                            required />
                        <i class="fa-solid fa-calendar-days icon-right"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Supplier</label>
                    <input type="text" name="supplier" placeholder="Nama supplier"
                        value="<?= htmlspecialchars($_POST['supplier'] ?? '') ?>" required />
                </div>

                <div class="form-group">
                    <label>Status Default:</label>
                    <div class="status-badge">Tersedia</div>
                </div>

                <button type="submit" class="btn-submit">Buat Pesanan</button>

            </form>
        </div>
    </div>

</body>

</html>