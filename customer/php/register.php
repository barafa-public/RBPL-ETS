<?php
session_start();
include '../../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $address = $_POST['address'];
  $phone_number = $_POST['phone_number'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  if (strlen($_POST['password']) < 6) {
    $error = "Password must be at least 6 characters!";
  } elseif ($_POST['password'] !== $_POST['confirm_password']) {
    $error = "Password dan konfirmasi password tidak cocok!";
  } elseif ($phone_number[0] !== '0') {
    $error = "The first number must be 0!";
  } elseif (strlen($phone_number) < 10) {
    $error = "Phone number must be at least 10 digits!";
  } else {
    $query = "INSERT INTO customers (username, address, phone_number, password)
                  VALUES ('$username', '$address', '$phone_number', '$password')";

    if (mysqli_query($conn, $query)) {
      header("Location: login.php?status=success");
      exit;
    } else {
      $error = "Gagal mendaftar, coba lagi!";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daftar Akun Baru</title>
  <link rel="stylesheet" href="../css/register.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

  <div class="card">

    <div class="logo-wrap">
      <div class="logo">
        <i class="fa-regular fa-user"></i>
      </div>
    </div>

    <h1 class="title">Daftar Akun Baru</h1>
    <p class="subtitle">Lengkapi data diri Anda untuk mendaftar</p>

    <?php if (!empty($error)): ?>
      <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <div class="input-wrap">
          <i class="fa-regular fa-user icon-left"></i>
          <input type="text" name="username" placeholder="Buat Username" required />
        </div>
      </div>

      <div class="form-group">
        <label>Alamat</label>
        <div class="input-wrap">
          <i class="fa-solid fa-location-dot icon-left"></i>
          <input type="text" name="address" placeholder="Jln.xxx" required />
        </div>
      </div>

      <div class="form-group">
        <label>No. Telepon</label>
        <div class="input-wrap">
          <i class="fa-solid fa-phone icon-left"></i>
          <input type="tel" name="phone_number" id="phone_number" placeholder="08xxxxxxxxx" inputmode="numeric"
            pattern="0[0-9]{9,12}" minlength="10" maxlength="13" required />
        </div>
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <i class="fa-solid fa-lock icon-left"></i>
          <input type="password" name="password" id="passwordInput" placeholder="Minimal 6 karakter" minlength="6"
            required />
          <i class="fa-regular fa-eye icon-right" id="togglePassword"></i>
        </div>
      </div>

      <div class="form-group">
        <label>Konfirmasi Password</label>
        <div class="input-wrap">
          <i class="fa-solid fa-lock icon-left"></i>
          <input type="password" name="confirm_password" id="confirmPasswordInput" placeholder="Ulangi password Anda"
            required />
          <i class="fa-regular fa-eye icon-right" id="toggleConfirm"></i>
        </div>
      </div>

      <button type="submit" class="btn-daftar">Daftar Sekarang</button>
    </form>

    <p class="login-text">
      Sudah punya akun? <a href="login.php">Masuk</a>
    </p>

  </div>

  <script>
    function toggleVisibility(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }
    document.getElementById('togglePassword').addEventListener('click', () => toggleVisibility('passwordInput', 'togglePassword'));
    document.getElementById('toggleConfirm').addEventListener('click', () => toggleVisibility('confirmPasswordInput', 'toggleConfirm'));

    document.getElementById('phone_number').addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '');
      this.setCustomValidity('');
    });
    document.getElementById('phone_number').addEventListener('keydown', function (e) {
      const allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'];
      if (!/[0-9]/.test(e.key) && !allowed.includes(e.key)) e.preventDefault();
    });
    document.getElementById('phone_number').addEventListener('blur', function () {
      const val = this.value;
      if (val.length > 0 && val[0] !== '0') {
        this.setCustomValidity('The first number must be 0');
        this.reportValidity();
      } else if (val.length > 0 && val.length < 10) {
        this.setCustomValidity('Phone number must be at least 10 digits');
        this.reportValidity();
      } else {
        this.setCustomValidity('');
      }
    });

    // Validasi password minimal 6 karakter
    document.getElementById('passwordInput').addEventListener('blur', function () {
      if (this.value.length > 0 && this.value.length < 6) {
        this.setCustomValidity('Password must be at least 6 characters');
        this.reportValidity();
      } else {
        this.setCustomValidity('');
      }
    });
    document.getElementById('passwordInput').addEventListener('input', function () {
      this.setCustomValidity('');
    });
  </script>

</body>

</html>