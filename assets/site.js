(() => {
  const focusStyle = document.createElement('style');
  focusStyle.textContent = ':focus-visible{outline:2px solid currentColor;outline-offset:3px}';
  document.head.appendChild(focusStyle);
  const loadingStyle = document.createElement('style');
  loadingStyle.textContent = '@keyframes bl-spin{to{transform:rotate(360deg)}}button[aria-busy="true"]{cursor:wait!important;opacity:.72}button[aria-busy="true"]::after{content:"";display:inline-block;width:11px;height:11px;margin-left:8px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;vertical-align:-2px;animation:bl-spin .7s linear infinite}';
  document.head.appendChild(loadingStyle);

  function startLoading(form, button, label) {
    if (!form || !button || form.dataset.loading === '1') return false;
    form.dataset.loading = '1';
    button.dataset.originalText = button.dataset.originalText || button.textContent;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.textContent = label || button.dataset.loadingText || 'Working...';
    return true;
  }
  function stopLoading(form, button) {
    if (!form || !button) return;
    form.dataset.loading = '';
    button.disabled = false;
    button.removeAttribute('aria-busy');
    button.textContent = button.dataset.originalText || button.textContent;
  }
  window.blLoading = {start: startLoading, stop: stopLoading};

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

  document.querySelectorAll('form[data-loading-form]').forEach(form => form.addEventListener('submit', event => {
    if (event.defaultPrevented) return;
    const button = form.querySelector('button[type="submit"],button[name]');
    if (!startLoading(form, button)) event.preventDefault();
  }));

  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !['checkoutForm', 'trackForm'].includes(form.id)) return;
    if (form.id === 'checkoutForm' && !form.closest('body')?.querySelector('#cart .cart-row')) return;
    startLoading(form, form.querySelector('button[type="submit"]'), form.id === 'trackForm' ? 'Checking order...' : 'Saving your order...');
  }, true);
  [['trackResult', 'trackForm'], ['result', 'checkoutForm']].forEach(([resultId, formId]) => {
    const result = document.getElementById(resultId);
    const form = document.getElementById(formId);
    if (!result || !form) return;
    new MutationObserver(() => {
      if (formId === 'trackForm' && !/checking your order/i.test(result.textContent)) setTimeout(() => stopLoading(form, form.querySelector('button[type="submit"]')), 250);
      else if (/could not|still finishing|try again|not available/i.test(result.textContent)) stopLoading(form, form.querySelector('button[type="submit"]'));
    }).observe(result, {childList: true, subtree: true, characterData: true});
  });
})();
