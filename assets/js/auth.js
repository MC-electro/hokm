const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');

async function submitForm(form, url, successUrl) {
  const msg = document.getElementById('msg');
  const formData = new FormData(form);
  const res = await fetch(url, { method: 'POST', body: formData });
  const data = await res.json();
  msg.textContent = data.message;
  msg.className = data.ok ? 'ok' : 'error';
  if (data.ok) setTimeout(() => location.href = successUrl, 700);
}

if (loginForm) loginForm.addEventListener('submit', (e) => { e.preventDefault(); submitForm(loginForm, '/public/api/login.php', '/public/lobby.php'); });
if (registerForm) registerForm.addEventListener('submit', (e) => { e.preventDefault(); submitForm(registerForm, '/public/api/register.php', '/public/login.php'); });
