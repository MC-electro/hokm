<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }
$roomId = (int)($_GET['id'] ?? 0);
$inviteCode = h($_GET['code'] ?? '');
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>اتاق بازی | حکم آنلاین</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body data-room-id="<?= $roomId ?>" data-invite-code="<?= $inviteCode ?>">
<header class="topbar glass">
  <h1>اتاق بازی</h1>
  <nav><a href="/lobby.php">بازگشت به لابی</a></nav>
</header>
<main class="room-layout">
  <section class="glass panel">
    <h2>بازیکنان اتاق</h2>
    <ul id="players"></ul>
    <button id="startGameBtn">شروع بازی</button>
    <div class="invite-wrap">
      <label>لینک دعوت</label>
      <input id="inviteLink" readonly>
      <button id="copyInviteBtn">کپی لینک دعوت</button>
    </div>
    <p id="roomMsg"></p>
  </section>

  <section class="glass panel game-section">
    <h2>میز بازی حکم</h2>
    <div id="statusBar"></div>
    <div class="table-board" id="tableBoard">
      <div class="seat top" id="seat2"></div>
      <div class="seat left" id="seat1"></div>
      <div class="seat right" id="seat3"></div>
      <div class="seat bottom" id="seat0"></div>
      <div class="center-cards" id="centerCards"></div>
    </div>
    <div id="handCards" class="hand"></div>
    <div id="trumpChooser" class="hidden">
      <h3>انتخاب حکم</h3>
      <div class="trump-buttons">
        <button data-suit="hearts">دل ❤️</button>
        <button data-suit="diamonds">خشت ♦️</button>
        <button data-suit="clubs">گشنیز ♣️</button>
        <button data-suit="spades">پیک ♠️</button>
      </div>
    </div>
    <div id="teamNaming" class="hidden">
      <h3>نام‌گذاری تیم</h3>
      <label>نام تیم الف<input id="teamAName"></label>
      <button data-team="a" class="teamNameBtn">ثبت تیم الف</button>
      <label>نام تیم ب<input id="teamBName"></label>
      <button data-team="b" class="teamNameBtn">ثبت تیم ب</button>
    </div>
  </section>

  <section class="glass panel">
    <h2>چت اتاق</h2>
    <div id="chatBox" class="chat-box"></div>
    <form id="chatForm" class="chat-form">
      <input name="message" placeholder="پیام خود را بنویسید" maxlength="300">
      <button>ارسال</button>
    </form>
  </section>
</main>
<footer><a href="https://donofa.ir/persianart/" target="_blank" rel="noopener">حمایت از ما ❤️</a></footer>
<script>window.USER_ID = <?= (int)$_SESSION['user_id'] ?>;</script>
<script src="/assets/js/room.js"></script>
</body>
</html>
