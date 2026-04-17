<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لابی | حکم آنلاین</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="topbar glass">
  <h1>لابی بازی حکم</h1>
  <nav>
    <a href="/leaderboard.php">لیدربرد</a>
    <a href="#" id="logoutBtn">خروج</a>
  </nav>
</header>
<main class="layout-two">
  <section class="glass panel">
    <h2>ساخت اتاق</h2>
    <form id="createRoomForm" class="form">
      <label>نام اتاق<input name="name" required></label>
      <label class="checkbox"><input type="checkbox" name="is_private">اتاق خصوصی</label>
      <button type="submit">ساخت اتاق</button>
    </form>
    <p id="createMsg"></p>
  </section>
  <section class="glass panel">
    <h2>اتاق‌های عمومی</h2>
    <div id="rooms"></div>
  </section>
  <section class="glass panel">
    <h2>کاربران آنلاین</h2>
    <ul id="onlineUsers"></ul>
  </section>
</main>
<footer><a href="https://donofa.ir/persianart/" target="_blank" rel="noopener">حمایت از ما ❤️</a></footer>
<script src="/assets/js/lobby.js"></script>
</body>
</html>
