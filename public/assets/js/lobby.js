const createRoomForm = document.getElementById('createRoomForm');
const roomsEl = document.getElementById('rooms');
const onlineEl = document.getElementById('onlineUsers');
const createMsg = document.getElementById('createMsg');
const logoutBtn = document.getElementById('logoutBtn');
const APP_BASE = window.APP_BASE || '';
const appPath = (path) => `${APP_BASE}${path}`;

async function fetchLobby() {
  const res = await fetch(appPath('/api/room_state.php?room_id=0'));
  if (!res.ok) return;
  const data = await res.json();
  if (!data.ok) return;

  roomsEl.innerHTML = data.public_rooms.map(r => `
    <article class="room-item">
      <h3>${r.name}</h3>
      <p>وضعیت: ${r.status === 'waiting' ? 'در انتظار' : 'در حال بازی'} | نفرات: ${r.players_count}/۴</p>
      <button data-room="${r.id}">ورود به اتاق</button>
    </article>
  `).join('') || '<p>اتاق عمومی فعالی وجود ندارد.</p>';

  onlineEl.innerHTML = data.online_users.map(u => `<li>${u.username}</li>`).join('');

  roomsEl.querySelectorAll('button[data-room]').forEach(btn => {
    btn.onclick = async () => {
      const form = new FormData();
      form.append('room_id', btn.dataset.room);
      const joinRes = await fetch(appPath('/api/join_room.php'), { method: 'POST', body: form });
      const joinData = await joinRes.json();
      if (joinData.ok) location.href = appPath(`/room.php?id=${btn.dataset.room}`);
      else alert(joinData.message);
    };
  });
}

createRoomForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = new FormData(createRoomForm);
  const res = await fetch(appPath('/api/create_room.php'), { method: 'POST', body: form });
  const data = await res.json();
  createMsg.textContent = data.ok ? 'اتاق ساخته شد.' : data.message;
  if (data.ok) location.href = appPath(`/room.php?id=${data.room_id}&code=${data.invite_code}`);
});

logoutBtn?.addEventListener('click', async (e) => {
  e.preventDefault();
  await fetch(appPath('/api/logout.php'), { method: 'POST' });
  location.href = appPath('/login.php');
});

fetchLobby();
setInterval(fetchLobby, 2000);
