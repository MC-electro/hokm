const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const APP_BASE = window.APP_BASE || '';
const appPath = (path) => `${APP_BASE}${path}`;

async function submitForm(form, url, successUrl) {
  const msg = document.getElementById('msg');
  const formData = new FormData(form);
  const res = await fetch(url, { method: 'POST', body: formData });
  const data = await res.json();
  msg.textContent = data.message;
  msg.className = data.ok ? 'ok' : 'error';
  if (data.ok) setTimeout(() => location.href = successUrl, 700);
}

if (loginForm) loginForm.addEventListener('submit', (e) => { e.preventDefault(); submitForm(loginForm, appPath('/api/login.php'), appPath('/lobby.php')); });
if (registerForm) registerForm.addEventListener('submit', (e) => { e.preventDefault(); submitForm(registerForm, appPath('/api/register.php'), appPath('/login.php')); });
