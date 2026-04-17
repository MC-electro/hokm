<?php require_once __DIR__ . '/../config/bootstrap.php'; ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لیدربرد | حکم آنلاین</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="topbar glass"><h1>رتبه‌بندی بازیکنان</h1><nav><a href="/public/lobby.php">لابی</a></nav></header>
<main class="glass card">
  <table class="leaderboard-table">
    <thead><tr><th>رتبه</th><th>بازیکن</th><th>بازی</th><th>برد</th><th>باخت</th><th>امتیاز</th></tr></thead>
    <tbody id="leaderboardBody"></tbody>
  </table>
</main>
<footer><a href="https://donofa.ir/persianart/" target="_blank" rel="noopener">حمایت از ما ❤️</a></footer>
<script src="/assets/js/leaderboard.js"></script>
</body>
</html>
