<?php require_once __DIR__ . '/../config/bootstrap.php'; ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ورود | بازی حکم آنلاین</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
  <main class="glass card auth-card">
    <h1>ورود به بازی حکم</h1>
    <form id="loginForm" class="form">
      <label>نام کاربری یا ایمیل
        <input type="text" name="identity" required>
      </label>
      <label>رمز عبور
        <input type="password" name="password" required>
      </label>
      <button type="submit">ورود</button>
      <p class="muted">حساب ندارید؟ <a href="/public/register.php">ثبت‌نام</a></p>
      <p id="msg"></p>
    </form>
  </main>
  <footer><a href="https://donofa.ir/persianart/" target="_blank" rel="noopener">حمایت از ما ❤️</a></footer>
  <script src="/assets/js/auth.js"></script>
</body>
</html>
