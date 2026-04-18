const APP_BASE = window.APP_BASE || '';
const appPath = (path) => `${APP_BASE}${path}`;

async function loadLeaderboard() {
  const res = await fetch(appPath('/api/leaderboard.php'));
  const data = await res.json();
  const body = document.getElementById('leaderboardBody');
  body.innerHTML = data.items.map((item, i) => `
    <tr>
      <td>${i + 1}</td>
      <td>${item.username}</td>
      <td>${item.games_played}</td>
      <td>${item.wins}</td>
      <td>${item.losses}</td>
      <td>${item.points}</td>
    </tr>
  `).join('');
}
loadLeaderboard();
