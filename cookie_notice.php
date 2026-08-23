<?php
function cookie_notice()
{
    ?>
<style>
.text-link{color:#9c6b39!important;font-weight:700;text-decoration:underline!important;text-underline-offset:4px;cursor:pointer}.site-footer-links a,.footer-links a{cursor:pointer;text-decoration:underline;text-underline-offset:4px}.site-footer-links a:hover,.footer-links a:hover{color:var(--rose)}.cookie-banner{position:fixed;right:20px;bottom:20px;z-index:50;display:flex;align-items:center;gap:22px;width:min(560px,calc(100% - 32px));padding:18px 20px;background:var(--ink);color:var(--cream);box-shadow:0 20px 50px #0004}.cookie-banner p{margin:6px 0 0;color:#cfc1c1;font-size:12px;line-height:1.6}.cookie-banner a{color:var(--rose);font-weight:700;text-decoration:underline;text-underline-offset:3px}.cookie-banner button{flex:none;border:0;border-radius:999px;padding:11px 17px;background:var(--rose);color:#27171b;cursor:pointer;font-size:12px;font-weight:700}.cookie-banner button:hover{transform:translateY(-1px)}@media(max-width:520px){.cookie-banner{display:block;right:16px;bottom:16px}.cookie-banner button{margin-top:12px}}
</style>
<div class="cookie-banner" id="cookieBanner" role="region" aria-label="Cookie notice" hidden><div><strong>Cookie notice</strong><p>We use a small cookie to remember your consent. By clicking Accept, you agree to our <a href="/terms">Terms &amp; Conditions</a> and acknowledge our <a href="/privacy">Privacy Policy</a>.</p></div><button id="cookieAccept" type="button">Accept</button></div>
<script>
(()=>{const banner=document.getElementById('cookieBanner'),button=document.getElementById('cookieAccept');if(!banner||!button)return;const accepted=document.cookie.split('; ').some(item=>item==='bl_cookie_consent=accepted')||localStorage.getItem('bl_cookie_consent')==='accepted';if(!accepted)banner.hidden=false;button.addEventListener('click',()=>{localStorage.setItem('bl_cookie_consent','accepted');document.cookie='bl_cookie_consent=accepted; Max-Age=31536000; Path=/; SameSite=Lax; Secure';banner.remove()})})();
</script>
<?php
}
