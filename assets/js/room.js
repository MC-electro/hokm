const body = document.body;
const roomId = body.dataset.roomId;
const inviteCode = body.dataset.inviteCode;
let gameId = null;
let revision = 0;
let lastChatId = 0;
let meSeat = 0;
let roomPollDelay = 1500;
let chatPollDelay = 1500;
let roomPollTimer = null;
let chatPollTimer = null;
let roomRequestInFlight = false;
let chatRequestInFlight = false;
let gameResultShown = false;
let recentTrickCards = [];
let recentTrickExpiresAt = 0;
let lastTrickMoveId = 0;

const LAST_TRICK_VISIBLE_MS = 2000;

const seatMap = { 0: 'پایین', 1: 'چپ', 2: 'بالا', 3: 'راست' };

function suitLabel(s) {
  return ({ hearts: 'دل ❤️', diamonds: 'خشت ♦️', clubs: 'گشنیز ♣️', spades: 'پیک ♠️' })[s] || '-';
}

function rankLabel(r) {
  return ({ J: 'سرباز', Q: 'بی‌بی', K: 'شاه', A: 'آس' })[r] || r;
}

function cardHtml(card, playable = false) {
  if (card === 'hidden') return `<div class="card back"></div>`;
  const [suit, rank] = card.split('-');
  return `<button class="card ${suit} ${playable ? 'playable' : ''}" data-card="${card}"><span>${rankLabel(rank)}</span><small>${suitLabel(suit)}</small></button>`;
}

async function ensureMembership() {
  const form = new FormData();
  form.append('room_id', roomId);
  if (inviteCode) form.append('invite_code', inviteCode);
  await fetch('/public/api/join_room.php', { method: 'POST', body: form, credentials: 'same-origin' });
}

async function apiRequest(url, options = {}) {
  try {
    const res = await fetch(url, { credentials: 'same-origin', ...options });
    const text = await res.text();
    let data = null;
    try {
      data = JSON.parse(text);
    } catch {
      if (!res.ok) {
        console.warn('پاسخ JSON نامعتبر:', url, text.slice(0, 500));
      }
      return { ok: false, message: 'پاسخ نامعتبر از سرور دریافت شد.', status: res.status };
    }

    if (!res.ok && data?.ok !== false) {
      return { ok: false, message: data?.message || 'خطای سرور.', status: res.status };
    }
    return data;
  } catch (err) {
    return { ok: false, message: 'ارتباط با سرور برقرار نشد.' };
  }
}

async function pollRoom() {
  if (roomRequestInFlight) return;
  roomRequestInFlight = true;
  const data = await apiRequest(`/public/api/room_state.php?room_id=${roomId}`);
  if (!data.ok) {
    document.getElementById('roomMsg').textContent = data.message;
    roomPollDelay = Math.min(roomPollDelay + 1500, 10000);
    roomRequestInFlight = false;
    return;
  }
  roomPollDelay = 1500;

  document.getElementById('inviteLink').value = `${location.origin}${data.invite_link}`;
  document.getElementById('players').innerHTML = data.players.map(p => `<li>${p.username} - جایگاه ${seatMap[p.seat_position]}</li>`).join('');

  const myPlayer = data.players.find(p => Number(p.user_id) === Number(window.USER_ID || 0));
  if (myPlayer) meSeat = Number(myPlayer.seat_position);

  await pollGame();
  roomRequestInFlight = false;
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

function renderCenterCards(cards, playersBySeat) {
  document.getElementById('centerCards').innerHTML = cards.map(t => {
    const seat = Number(t.seat);
    const playerName = playersBySeat[seat] || `جایگاه ${seatMap[seat]}`;
    return `
      <div class="center-card-wrap">
        <small class="center-card-player">${playerName}</small>
        <div class="center-card">${cardHtml(t.card)}</div>
      </div>
    `;
  }).join('');
}

async function pollGame() {
  const data = await apiRequest(`/public/api/game_state.php?room_id=${roomId}&revision=${revision}`);
  if (!data.ok || !data.has_update || !data.state) return;

  revision = data.revision;
  const { game, players, hands, current_trick, moves } = data.state;
  gameId = game.id;
  const playersBySeat = {};
  players.forEach((p) => {
    playersBySeat[Number(p.seat_position)] = p.username;
  });

  renderSeats(players, game);
  document.getElementById('scoreHeader').innerHTML = `
    <span class="team team-a">${game.team_a_name}: ${game.team_a_points}</span>
    <span class="team team-b">${game.team_b_name}: ${game.team_b_points}</span>
  `;
  document.getElementById('statusBar').innerHTML = `
    <p>نوبت: جایگاه ${seatMap[game.current_turn]} | حکم: ${game.trump_suit ? suitLabel(game.trump_suit) : 'انتخاب نشده'}</p>
    <p>دست‌ها: ${game.team_a_tricks} - ${game.team_b_tricks}</p>
  `;

  const myCards = hands[String(meSeat)] || [];
  const playable = Number(game.current_turn) === meSeat && game.phase === 'playing';
  document.getElementById('handCards').innerHTML = myCards.map(c => cardHtml(c, playable)).join('');
  document.querySelectorAll('.card.playable').forEach(btn => {
    btn.onclick = async () => {
      const form = new FormData();
      form.append('game_id', gameId);
      form.append('card', btn.dataset.card);
      const d = await apiRequest('/public/api/play_card.php', { method: 'POST', body: form });
      if (!d.ok) alert(d.message);
    };
  });

  const latestFinishedTrick = [...(moves || [])].reverse().find((m) => m.action === 'trick_finished');
  if (latestFinishedTrick && Number(latestFinishedTrick.id) > lastTrickMoveId) {
    lastTrickMoveId = Number(latestFinishedTrick.id);
    const payload = JSON.parse(latestFinishedTrick.payload_json || '{}');
    recentTrickCards = payload?.trick || [];
    recentTrickExpiresAt = Date.now() + LAST_TRICK_VISIBLE_MS;
  }

  if ((current_trick || []).length > 0) {
    renderCenterCards(current_trick, playersBySeat);
  } else if (recentTrickCards.length > 0 && Date.now() < recentTrickExpiresAt) {
    renderCenterCards(recentTrickCards, playersBySeat);
  } else {
    renderCenterCards([], playersBySeat);
    recentTrickCards = [];
  }

  const trumpChooser = document.getElementById('trumpChooser');
  trumpChooser.classList.toggle('hidden', !(game.phase === 'trump_selection' && Number(game.dealer_position) === meSeat));

  const teamNaming = document.getElementById('teamNaming');
  teamNaming.classList.toggle('hidden', game.phase !== 'team_naming');

  if (game.phase === 'finished' && !gameResultShown) {
    const myTeamA = [0, 2].includes(meSeat);
    const iWon = (myTeamA && Number(game.team_a_points) > Number(game.team_b_points)) || (!myTeamA && Number(game.team_b_points) > Number(game.team_a_points));
    const box = document.getElementById('resultOverlay');
    document.getElementById('resultTitle').textContent = iWon ? '🎉 شما بردید!' : '😔 شما باختید';
    document.getElementById('resultText').textContent = `${game.team_a_name} ${game.team_a_points} - ${game.team_b_name} ${game.team_b_points}`;
    box.classList.remove('hidden');
    gameResultShown = true;
  }
}

document.querySelectorAll('#trumpChooser button[data-suit]').forEach(btn => {
  btn.addEventListener('click', async () => {
    const form = new FormData();
    form.append('game_id', gameId);
    form.append('suit', btn.dataset.suit);
    const data = await apiRequest('/public/api/choose_trump.php', { method: 'POST', body: form });
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
    const data = await apiRequest('/public/api/team_name.php', { method: 'POST', body: form });
    if (!data.ok) alert(data.message);
  });
});

document.getElementById('startGameBtn').addEventListener('click', async () => {
  const targetInput = document.getElementById('targetPoints');
  const targetPoints = Math.max(1, Math.min(20, Number(targetInput?.value || 7)));
  const form = new FormData();
  form.append('room_id', roomId);
  form.append('target_points', String(targetPoints));
  const data = await apiRequest('/public/api/start_game.php', { method: 'POST', body: form });
  if (!data.ok) alert(data.message);
});

document.getElementById('copyInviteBtn').addEventListener('click', async () => {
  const input = document.getElementById('inviteLink');
  await navigator.clipboard.writeText(input.value);
  alert('لینک دعوت کپی شد.');
});

async function pollChat() {
  if (chatRequestInFlight) return;
  chatRequestInFlight = true;
  const data = await apiRequest(`/public/api/chat.php?room_id=${roomId}&since_id=${lastChatId}`);
  if (!data.ok) {
    chatPollDelay = Math.min(chatPollDelay + 1500, 10000);
    chatRequestInFlight = false;
    return;
  }
  chatPollDelay = 1500;
  const box = document.getElementById('chatBox');
  data.messages.forEach(m => {
    lastChatId = m.id;
    const item = document.createElement('div');
    item.className = 'chat-item';
    item.textContent = `${m.username} (${new Date(m.created_at).toLocaleTimeString('fa-IR')}): ${m.message}`;
    box.appendChild(item);
  });
  box.scrollTop = box.scrollHeight;
  chatRequestInFlight = false;
}

document.getElementById('chatForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = new FormData(e.target);
  form.append('room_id', roomId);
  const data = await apiRequest('/public/api/chat.php', { method: 'POST', body: form });
  if (!data.ok) alert(data.message);
  e.target.reset();
});

document.getElementById('closeResultBtn')?.addEventListener('click', () => {
  document.getElementById('resultOverlay')?.classList.add('hidden');
});

document.getElementById('logoutRoomBtn')?.addEventListener('click', async (e) => {
  e.preventDefault();
  await apiRequest('/public/api/logout.php', { method: 'POST' });
  location.href = '/public/login.php';
});

function roomTick() {
  pollRoom().finally(() => {
    roomPollTimer = setTimeout(roomTick, roomPollDelay);
  });
}

function chatTick() {
  pollChat().finally(() => {
    chatPollTimer = setTimeout(chatTick, chatPollDelay);
  });
}

async function boot() {
  await ensureMembership();
  await pollRoom();
  await pollChat();
  roomTick();
  chatTick();
}

boot();
