<?php require_once __DIR__ . '/../config/bootstrap.php'; ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ورود | بازی حکم آنلاین</title>
  <link rel="stylesheet" href="<?= h(appUrl('/assets/css/style.css')) ?>">
</head>
<body class="auth-page">
  <header class="topbar glass">
    <h1>بازی حکم آنلاین</h1>
    <nav>
      <a href="<?= h(appUrl('/register.php')) ?>">ثبت‌نام</a>
      <a href="<?= h(appUrl('/leaderboard.php')) ?>">اسکوردبرد</a>
    </nav>
  </header>
  <main class="glass panel auth-card">
    <h1>ورود به بازی حکم</h1>
    <form id="loginForm" class="form">
      <label>نام کاربری یا ایمیل
        <input type="text" name="identity" required>
      </label>
      <label>رمز عبور
        <input type="password" name="password" required>
      </label>
      <button type="submit">ورود</button>
      <p class="muted">حساب ندارید؟ <a href="<?= h(appUrl('/register.php')) ?>">ثبت‌نام</a></p>
      <p id="msg"></p>
    </form>
  </main>
  <footer><a href="https://donofa.ir/persianart/" target="_blank" rel="noopener">حمایت از ما ❤️</a></footer>
  <script>window.APP_BASE = <?= json_encode(rtrim(appUrl(''), '/'), JSON_UNESCAPED_UNICODE) ?>;</script>
  <script src="<?= h(appUrl('/assets/js/auth.js')) ?>"></script>
</body>
</html>
