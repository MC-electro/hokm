<?php require_once __DIR__ . '/../config/bootstrap.php'; ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ثبت‌نام | بازی حکم آنلاین</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
<main class="glass card auth-card">
  <h1>ثبت‌نام</h1>
  <form id="registerForm" class="form">
    <label>نام کاربری
      <input type="text" name="username" required>
    </label>
    <label>ایمیل
      <input type="email" name="email" required>
    </label>
    <label>رمز عبور
      <input type="password" name="password" required>
    </label>
    <button type="submit">ثبت‌نام</button>
    <p class="muted">قبلاً عضو شده‌اید؟ <a href="/public/login.php">ورود</a></p>
    <p id="msg"></p>
  </form>
</main>
<footer><a href="https://donofa.ir/persianart/" target="_blank" rel="noopener">حمایت از ما ❤️</a></footer>
<script src="/assets/js/auth.js"></script>
</body>
</html>
