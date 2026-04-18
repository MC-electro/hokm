const body = document.body;
const roomId = body.dataset.roomId;
const inviteCode = body.dataset.inviteCode;
let gameId = null;
let revision = 0;
let lastChatId = 0;
let meSeat = 0;
const APP_BASE = window.APP_BASE || '';
const appPath = (path) => `${APP_BASE}${path}`;

const seatMap = { 0: 'پایین', 1: 'چپ', 2: 'بالا', 3: 'راست' };

function suitLabel(s) {
  return ({ hearts: 'دل ❤️', diamonds: 'خشت ♦️', clubs: 'گشنیز ♣️', spades: 'پیک ♠️' })[s] || '-';
}

function rankLabel(r) {
  return ({ J: 'سرباز', Q: 'بی‌بی', K: 'شاه', A: 'آس' })[r] || r;
}

function cardHtml(card, playable = false) {
  if (card === 'hidden') return `<div class="playing-card back"></div>`;
  const [suit, rank] = card.split('-');
  return `<button class="playing-card ${suit} ${playable ? 'playable' : ''}" data-card="${card}"><span>${rankLabel(rank)}</span><small>${suitLabel(suit)}</small></button>`;
}

async function ensureMembership() {
  const form = new FormData();
  form.append('room_id', roomId);
  if (inviteCode) form.append('invite_code', inviteCode);
  await fetch(appPath('/api/join_room.php'), { method: 'POST', body: form });
}

async function pollRoom() {
  const res = await fetch(appPath(`/api/room_state.php?room_id=${roomId}`));
  const data = await res.json();
  if (!data.ok) {
    document.getElementById('roomMsg').textContent = data.message;
    return;
  }

  document.getElementById('inviteLink').value = `${location.origin}${data.invite_link}`;
  document.getElementById('players').innerHTML = data.players.map(p => `<li>${p.username} - جایگاه ${seatMap[p.seat_position]}</li>`).join('');

  const myPlayer = data.players.find(p => Number(p.user_id) === Number(window.USER_ID || 0));
  if (myPlayer) meSeat = Number(myPlayer.seat_position);

  await pollGame();
}

function relativeSeat(targetSeat) {
  const delta = (targetSeat - meSeat + 4) % 4;
  return ['bottom', 'right', 'top', 'left'][delta];
}

function renderSeats(players, game) {
  const teamAName = game.team_a_name || 'تیم شما';
  const teamBName = game.team_b_name || 'حریف';
  players.forEach(p => {
    const realSeat = Number(p.seat_position);
    const rel = relativeSeat(realSeat);
    const isTeamA = [0, 2].includes(realSeat);
    const label = isTeamA ? teamAName : teamBName;
    const tag = ([0, 2].includes(realSeat) === [0, 2].includes(meSeat)) ? 'هم‌تیمی شما' : 'حریف';
    const dealer = Number(game.dealer_position) === realSeat ? '👑 دیلر' : '';
    document.getElementById(`seat${{bottom:0,left:1,top:2,right:3}[rel]}`).innerHTML = `
      <div class="seat-player ${isTeamA ? 'team-a' : 'team-b'}">
        <strong>${p.username}</strong>
        <span>${tag}</span>
        <small>${label}</small>
        <small>${dealer}</small>
      </div>
    `;
  });
}

async function pollGame() {
  const res = await fetch(appPath(`/api/game_state.php?room_id=${roomId}&revision=${revision}`));
  const data = await res.json();
  if (!data.ok || !data.has_update || !data.state) return;

  revision = data.revision;
  const { game, players, hands, current_trick } = data.state;
  gameId = game.id;

  renderSeats(players, game);
  document.getElementById('statusBar').innerHTML = `
    <p>نوبت: جایگاه ${seatMap[game.current_turn]} | حکم: ${game.trump_suit ? suitLabel(game.trump_suit) : 'انتخاب نشده'} | امتیاز ${game.team_a_name}: ${game.team_a_points} - ${game.team_b_name}: ${game.team_b_points}</p>
    <p>دست‌ها: ${game.team_a_tricks} - ${game.team_b_tricks}</p>
  `;

  const myCards = hands[String(meSeat)] || [];
  const playable = Number(game.current_turn) === meSeat && game.phase === 'playing';
  document.getElementById('handCards').innerHTML = myCards.map(c => cardHtml(c, playable)).join('');
  document.querySelectorAll('.playing-card.playable').forEach(btn => {
    btn.onclick = async () => {
      const form = new FormData();
      form.append('game_id', gameId);
      form.append('card', btn.dataset.card);
      const resPlay = await fetch(appPath('/api/play_card.php'), { method: 'POST', body: form });
      const d = await resPlay.json();
      if (!d.ok) alert(d.message);
    };
  });

  document.getElementById('centerCards').innerHTML = current_trick.map(t => `<div class="center-card">${cardHtml(t.card)}</div>`).join('');

  const trumpChooser = document.getElementById('trumpChooser');
  trumpChooser.classList.toggle('hidden', !(game.phase === 'trump_selection' && Number(game.dealer_position) === meSeat));

  const teamNaming = document.getElementById('teamNaming');
  teamNaming.classList.toggle('hidden', game.phase !== 'team_naming');
}

document.querySelectorAll('#trumpChooser button[data-suit]').forEach(btn => {
  btn.addEventListener('click', async () => {
    const form = new FormData();
    form.append('game_id', gameId);
    form.append('suit', btn.dataset.suit);
    const res = await fetch(appPath('/api/choose_trump.php'), { method: 'POST', body: form });
    const data = await res.json();
    if (!data.ok) alert(data.message);
  });
});

document.querySelectorAll('.teamNameBtn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const inputId = btn.dataset.team === 'a' ? 'teamAName' : 'teamBName';
    const name = document.getElementById(inputId).value.trim();
    const form = new FormData();
    form.append('game_id', gameId);
    form.append('team', btn.dataset.team);
    form.append('name', name);
    const res = await fetch(appPath('/api/team_name.php'), { method: 'POST', body: form });
    const data = await res.json();
    if (!data.ok) alert(data.message);
  });
});

document.getElementById('startGameBtn').addEventListener('click', async () => {
  const form = new FormData();
  form.append('room_id', roomId);
  const res = await fetch(appPath('/api/start_game.php'), { method: 'POST', body: form });
  const data = await res.json();
  if (!data.ok) alert(data.message);
});

document.getElementById('copyInviteBtn').addEventListener('click', async () => {
  const input = document.getElementById('inviteLink');
  await navigator.clipboard.writeText(input.value);
  alert('لینک دعوت کپی شد.');
});

async function pollChat() {
  const res = await fetch(appPath(`/api/chat.php?room_id=${roomId}&since_id=${lastChatId}`));
  const data = await res.json();
  const box = document.getElementById('chatBox');
  data.messages.forEach(m => {
    lastChatId = m.id;
    const item = document.createElement('div');
    item.className = 'chat-item';
    item.textContent = `${m.username} (${new Date(m.created_at).toLocaleTimeString('fa-IR')}): ${m.message}`;
    box.appendChild(item);
  });
  box.scrollTop = box.scrollHeight;
}

document.getElementById('chatForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = new FormData(e.target);
  form.append('room_id', roomId);
  const res = await fetch(appPath('/api/chat.php'), { method: 'POST', body: form });
  const data = await res.json();
  if (!data.ok) alert(data.message);
  e.target.reset();
});

async function boot() {
  await ensureMembership();
  await pollRoom();
  await pollChat();
  setInterval(pollRoom, 1500);
  setInterval(pollChat, 1500);
}

boot();
