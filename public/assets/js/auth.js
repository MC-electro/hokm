const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const APP_BASE = window.APP_BASE || '';
const appPath = (path) => `${APP_BASE}${path}`;

async function submitForm(form, url, successUrl) {
  const msg = document.getElementById('msg');
  const formData = new FormData(form);
  try {
    const res = await fetch(url, { method: 'POST', body: formData });
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      msg.textContent = 'پاسخ سرور نامعتبر است. لطفاً دوباره تلاش کنید.';
      msg.className = 'error';
      console.error('پاسخ JSON نامعتبر:', text);
      return;
    }

    msg.textContent = data.message || 'خطای نامشخص';
    msg.className = data.ok ? 'ok' : 'error';
    if (data.ok) setTimeout(() => location.href = successUrl, 700);
  } catch (e) {
    msg.textContent = 'ارتباط با سرور برقرار نشد.';
    msg.className = 'error';
  }
}

if (loginForm) loginForm.addEventListener('submit', (e) => { e.preventDefault(); submitForm(loginForm, appPath('/api/login.php'), appPath('/lobby.php')); });
if (registerForm) registerForm.addEventListener('submit', (e) => { e.preventDefault(); submitForm(registerForm, appPath('/api/register.php'), appPath('/login.php')); });
