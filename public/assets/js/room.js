const body = document.body;
const roomId = body.dataset.roomId;
const inviteCode = body.dataset.inviteCode;
let gameId = null;
let revision = 0;
let lastChatId = 0;
let meSeat = 0;
let lastCompletedTrick = { cards: [], at: 0 };
const APP_BASE = window.APP_BASE || '';
const appPath = (path) => `${APP_BASE}${path}`;
const myUserId = Number(window.USER_ID || 0);

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
  await apiRequest(appPath('/api/join_room.php'), { method: 'POST', body: form });
}

async function apiRequest(url, options = {}) {
  try {
    const res = await fetch(url, options);
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      console.error('پاسخ JSON نامعتبر:', url, text);
      return { ok: false, message: 'پاسخ سرور نامعتبر است.' };
    }
  } catch (e) {
    return { ok: false, message: 'ارتباط با سرور برقرار نشد.' };
  }
}

async function pollRoom() {
  const data = await apiRequest(appPath(`/api/room_state.php?room_id=${roomId}`));
  if (!data.ok) {
    document.getElementById('roomMsg').textContent = data.message;
    return;
  }

  document.getElementById('inviteLink').value = `${location.origin}${data.invite_link}`;
  document.getElementById('players').innerHTML = data.players.map(p => `<li>${p.username} - جایگاه ${seatMap[p.seat_position]}</li>`).join('');

  const myPlayer = data.players.find(p => Number(p.user_id) === myUserId);
  if (myPlayer) meSeat = Number(myPlayer.seat_position);
  const startBtn = document.getElementById('startGameBtn');
  const targetWrap = document.getElementById('roundTargetWrap');
  const canStart = Number(data.room.owner_id) === myUserId && data.players.length === 4 && data.room.status === 'waiting';
  startBtn.classList.toggle('hidden', !canStart);
  targetWrap.classList.toggle('hidden', !canStart);

  await pollGame();
}

function relativeSeat(targetSeat) {
  const delta = (targetSeat - meSeat + 4) % 4;
  return ['bottom', 'right', 'top', 'left'][delta];
}

function renderSeats(players, game) {
  const teamAName = game.team_a_name || 'تیم شما';
  const teamBName = game.team_b_name || 'حریف';
  const teamAScore = Number(game.team_a_points || 0);
  const teamBScore = Number(game.team_b_points || 0);
  players.forEach(p => {
    const realSeat = Number(p.seat_position);
    const rel = relativeSeat(realSeat);
    const isTeamA = [0, 2].includes(realSeat);
    const label = isTeamA ? `${teamAName} | امتیاز: ${teamAScore} | دور برده: ${teamAScore}` : `${teamBName} | امتیاز: ${teamBScore} | دور برده: ${teamBScore}`;
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
  const data = await apiRequest(appPath(`/api/game_state.php?room_id=${roomId}&revision=${revision}`));
  if (!data.ok || !data.has_update || !data.state) return;

  revision = data.revision;
  const { game, players, hands, current_trick } = data.state;
  gameId = game.id;
  const playersBySeat = {};
  players.forEach((p) => { playersBySeat[Number(p.seat_position)] = p.username; });

  renderSeats(players, game);
  const teamAName = game.team_a_name || 'تیم الف';
  const teamBName = game.team_b_name || 'تیم ب';
  document.getElementById('statusBar').innerHTML = `
    <p>نوبت: جایگاه ${seatMap[game.current_turn]} | حکم: ${game.trump_suit ? suitLabel(game.trump_suit) : 'انتخاب نشده'} | امتیاز هدف: ${game.target_points || 7}</p>
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
      const d = await apiRequest(appPath('/api/play_card.php'), { method: 'POST', body: form });
      if (!d.ok) alert(d.message);
    };
  });

  const latestTrickMove = [...(data.state.moves || [])].reverse().find(m => m.action === 'trick_finished');
  if (latestTrickMove) {
    try {
      const payload = JSON.parse(latestTrickMove.payload_json || '{}');
      if (Array.isArray(payload.trick)) {
        lastCompletedTrick = { cards: payload.trick, at: Date.now() };
      }
    } catch (e) {
      // intentionally ignored
    }
  }

  let cardsToShow = current_trick;
  if (!cardsToShow.length && lastCompletedTrick.cards.length && (Date.now() - lastCompletedTrick.at < 1000)) {
    cardsToShow = lastCompletedTrick.cards;
  }
  document.getElementById('centerCards').innerHTML = cardsToShow.map(t => `
    <div class="center-card-wrap">
      <small class="card-owner">${playersBySeat[Number(t.seat)] || 'بازیکن'}</small>
      <div class="center-card">${cardHtml(t.card)}</div>
    </div>
  `).join('');

  const trumpChooser = document.getElementById('trumpChooser');
  trumpChooser.classList.toggle('hidden', !(game.phase === 'trump_selection' && Number(game.dealer_position) === meSeat));

  const teamNaming = document.getElementById('teamNaming');
  const showTeamNaming = game.phase === 'team_naming' && (meSeat === 0 || meSeat === 1);
  teamNaming.classList.toggle('hidden', !showTeamNaming);
  document.querySelectorAll('.team-name-row').forEach(el => el.classList.add('hidden'));
  if (showTeamNaming) {
    if (meSeat === 0) {
      document.querySelector('.team-name-row.team-a').classList.remove('hidden');
    }
    if (meSeat === 1) {
      document.querySelector('.team-name-row.team-b').classList.remove('hidden');
    }
  }
}

document.querySelectorAll('#trumpChooser button[data-suit]').forEach(btn => {
  btn.addEventListener('click', async () => {
    const form = new FormData();
    form.append('game_id', gameId);
    form.append('suit', btn.dataset.suit);
    const data = await apiRequest(appPath('/api/choose_trump.php'), { method: 'POST', body: form });
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
    const data = await apiRequest(appPath('/api/team_name.php'), { method: 'POST', body: form });
    if (!data.ok) alert(data.message);
  });
});

document.getElementById('startGameBtn').addEventListener('click', async () => {
  const form = new FormData();
  form.append('room_id', roomId);
  form.append('target_points', document.getElementById('roundTarget').value || '7');
  const data = await apiRequest(appPath('/api/start_game.php'), { method: 'POST', body: form });
  if (!data.ok) alert(data.message);
});

document.getElementById('copyInviteBtn').addEventListener('click', async () => {
  const input = document.getElementById('inviteLink');
  await navigator.clipboard.writeText(input.value);
  alert('لینک دعوت کپی شد.');
});

document.getElementById('logoutRoomBtn')?.addEventListener('click', async (e) => {
  e.preventDefault();
  await apiRequest(appPath('/api/logout.php'), { method: 'POST' });
  location.href = appPath('/login.php');
});

async function pollChat() {
  const data = await apiRequest(appPath(`/api/chat.php?room_id=${roomId}&since_id=${lastChatId}`));
  if (!data.ok) return;
  const box = document.getElementById('chatBox');
  (data.messages || []).forEach(m => {
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
  const data = await apiRequest(appPath('/api/chat.php'), { method: 'POST', body: form });
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
