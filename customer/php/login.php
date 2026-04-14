<?php
session_start();
include '../../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $query = "SELECT * FROM customers WHERE username='$username'";
  $result = mysqli_query($conn, $query);
  $user = mysqli_fetch_assoc($result);

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['username'] = $user['username'];
    $_SESSION['id'] = $user['id'];
    header("Location: dashboard.php");
    exit;
  } else {
    $error = "Username atau password salah!";
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link rel="stylesheet" href="../css/login.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

  <div class="card">

    <div class="logo-wrap">
      <div class="logo">
        <i class="fa-solid fa-bag-shopping"></i>
      </div>
    </div>

    <h1 class="title">Selamat Datang</h1>
    <p class="subtitle">Masuk ke akun konsumen Anda</p>

    <?php if (!empty($error)): ?>
      <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <div class="input-wrap">
          <i class="fa-regular fa-user icon-left"></i>
          <input type="text" name="username" placeholder="Masukkan Username" required />
        </div>
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <i class="fa-solid fa-lock icon-left"></i>
          <input type="password" name="password" id="passwordInput" placeholder="Masukkan Password" required />
          <i class="fa-regular fa-eye icon-right" id="togglePassword"></i>
        </div>
        <div class="forgot">
          <a href="#">Lupa Password?</a>
        </div>
      </div>

      <button type="submit" class="btn-masuk">Masuk</button>
    </form>

    <p class="register-text">
      Belum punya akun? <a href="register.php">Daftar Sekarang</a>
    </p>

    <div class="divider"><span>atau masuk dengan</span></div>

    <div class="social-buttons">
      <button class="social-btn">
        <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" width="20" />
        Google
      </button>
      <button class="social-btn">
        <img src="https://www.svgrepo.com/show/475647/facebook-color.svg" alt="Facebook" width="20" />
        Facebook
      </button>
    </div>

  </div>

  <script>
    document.getElementById('togglePassword').addEventListener('click', function () {
      const input = document.getElementById('passwordInput');
      if (input.type === 'password') {
        input.type = 'text';
        this.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        this.classList.replace('fa-eye-slash', 'fa-eye');
      }
    });
  </script>

</body>

</html>