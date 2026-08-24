document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#supabaseLogin');
  if (!form) return;
  const loadingStyle = document.createElement('style');
  loadingStyle.textContent = '@keyframes bl-login-spin{to{transform:rotate(360deg)}}button[aria-busy="true"]{cursor:wait;opacity:.72}button[aria-busy="true"]::after{content:"";display:inline-block;width:11px;height:11px;margin-left:8px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;vertical-align:-2px;animation:bl-login-spin .7s linear infinite}';
  document.head.appendChild(loadingStyle);
  const fields = form.querySelectorAll('input:not([type="hidden"])');
  form.querySelectorAll('label').forEach((label, index) => {
    const field = fields[index];
    if (!field) return;
    if (!field.id) field.id = 'login-field-' + index;
    label.htmlFor = field.id;
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    event.stopImmediatePropagation();
    const button = form.querySelector('button');
    let error = form.querySelector('.client-error');
    if (!error) {
      error = document.createElement('p');
      error.className = 'error client-error';
      error.setAttribute('role', 'alert');
      form.prepend(error);
    }
    error.textContent = '';
    const originalText = button.textContent;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.textContent = 'Signing in...';
    try {
      const auth = await fetch(form.dataset.supabaseUrl + '/auth/v1/token?grant_type=password', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', apikey: form.dataset.supabaseAnonKey},
        body: JSON.stringify({email: form.email.value, password: form.password.value})
      });
      const data = await auth.json();
      if (!auth.ok || !data.access_token) throw new Error();
      const response = await fetch(location.pathname, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({csrf: form.csrf.value, login: '1', supabase_token: data.access_token})
      });
      if (!response.ok) throw new Error();
      location.reload();
    } catch (_) {
      error.textContent = 'Sign-in failed. Check your email and password, then try again.';
      button.disabled = false;
      button.removeAttribute('aria-busy');
      button.textContent = originalText;
    }
  }, true);
});
