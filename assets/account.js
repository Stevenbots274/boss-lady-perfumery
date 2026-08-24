(() => {
  document.querySelectorAll('[data-account-logout]').forEach(button => button.addEventListener('click', async () => {
    const form = button.form || button.closest('section') || document.body;
    if (window.blLoading && !window.blLoading.start(form, button, 'Signing out...')) return;
    try {
      const response = await fetch('/api/customer-auth.php', {method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json'}, body: JSON.stringify({action: 'logout'})});
      if (!response.ok) throw new Error();
      location.replace('/account');
    } catch (_) {
      if (window.blLoading) window.blLoading.stop(form, button);
      button.textContent = 'Try again';
    }
  }));

  const root = document.querySelector('#customerAuth');
  if (!root) return;
  const supabaseUrl = root.dataset.supabaseUrl;
  const anonKey = root.dataset.supabaseAnonKey;
  const signin = document.querySelector('#signinForm');
  const signup = document.querySelector('#signupForm');
  const message = document.querySelector('#accountMessage');
  const tabs = [...document.querySelectorAll('[data-auth-tab]')];
  const showMessage = (text, success = false) => {
    message.textContent = text;
    message.style.color = success ? 'var(--gold)' : 'var(--rose)';
  };
  const setTab = tab => {
    const isSignup = tab === 'signup';
    signin.hidden = isSignup;
    signup.hidden = !isSignup;
    tabs.forEach(button => button.classList.toggle('active', button.dataset.authTab === tab));
    showMessage('');
  };
  tabs.forEach(button => button.addEventListener('click', () => setTab(button.dataset.authTab)));
  if (!supabaseUrl || !anonKey) {
    showMessage('Accounts are not configured yet. Guest checkout remains available.');
    return;
  }
  async function supabase(path, body) {
    const response = await fetch(`${supabaseUrl}${path}`, {method: 'POST', headers: {'Content-Type': 'application/json', apikey: anonKey}, body: JSON.stringify(body)});
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.msg || data.message || data.error_description || 'Please check your details and try again.');
    return data;
  }
  async function storeSession(data) {
    const response = await fetch('/api/customer-auth.php', {method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json'}, body: JSON.stringify({action: 'session', access_token: data.access_token, refresh_token: data.refresh_token})});
    const result = await response.json().catch(() => ({}));
    if (!response.ok || !result.ok) throw new Error(result.error || 'Your sign-in session could not be started.');
  }
  signin.addEventListener('submit', async event => {
    event.preventDefault();
    const button = signin.querySelector('button');
    if (window.blLoading && !window.blLoading.start(signin, button, 'Signing in...')) return;
    button.disabled = true;
    try {
      const data = await supabase('/auth/v1/token?grant_type=password', {email: signin.email.value.trim(), password: signin.password.value});
      await storeSession(data);
      location.replace('/account');
    } catch (error) { showMessage(error.message); if (window.blLoading) window.blLoading.stop(signin, button); else button.disabled = false; }
  });
  signup.addEventListener('submit', async event => {
    event.preventDefault();
    const button = signup.querySelector('button');
    if (window.blLoading && !window.blLoading.start(signup, button, 'Creating account...')) return;
    button.disabled = true;
    try {
      const data = await supabase('/auth/v1/signup', {email: signup.email.value.trim(), password: signup.password.value});
      if (data.access_token) {
        await storeSession(data);
        location.replace('/account');
      } else showMessage('Account created. Check your email to confirm it, then sign in.', true);
    } catch (error) { showMessage(error.message); }
    if (window.blLoading) window.blLoading.stop(signup, button); else button.disabled = false;
  });
})();
