(() => {
  const focusStyle = document.createElement('style');
  focusStyle.textContent = ':focus-visible{outline:2px solid currentColor;outline-offset:3px}';
  document.head.appendChild(focusStyle);

  function setupMenu(button, root, shell) {
    if (!button || !root || !shell) return;
    if (!root.id) root.id = 'navigation-' + Math.random().toString(36).slice(2, 8);
    button.setAttribute('aria-controls', root.id);
    button.setAttribute('aria-expanded', 'false');
    const sync = () => button.setAttribute('aria-expanded', shell.classList.contains('open') || shell.classList.contains('mobile-open') ? 'true' : 'false');
    button.addEventListener('click', () => requestAnimationFrame(sync));
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && (shell.classList.contains('open') || shell.classList.contains('mobile-open'))) {
        shell.classList.remove('open', 'mobile-open');
        sync();
        button.focus();
      }
    });
  }

  setupMenu(document.querySelector('.menu-toggle'), document.querySelector('.nav-links'), document.querySelector('#siteNav'));
  setupMenu(document.querySelector('.site-menu'), document.querySelector('.site-links'), document.querySelector('#siteHeader'));
  setupMenu(document.querySelector('.mobile-menu'), document.querySelector('.sidebar'), document.querySelector('#adminShell'));

  const accountLink = document.querySelector('[data-account-link]');
  if (accountLink) {
    fetch('/api/customer-auth.php?action=status', {headers: {Accept: 'application/json'}})
      .then(response => response.ok ? response.json() : null)
      .then(data => { if (data?.authenticated) accountLink.textContent = 'My account'; })
      .catch(() => {});
  }

  document.querySelectorAll('label:not([for])').forEach(label => {
    if (label.querySelector('input, textarea, select')) return;
    const sibling = label.nextElementSibling;
    const control = sibling && sibling.matches('input:not([type="hidden"]), textarea, select') ? sibling : label.parentElement?.querySelector('input:not([type="hidden"]), textarea, select');
    if (!control) return;
    if (!control.id) control.id = 'field-' + Math.random().toString(36).slice(2, 8);
    label.htmlFor = control.id;
  });
})();
