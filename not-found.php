<?php
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=60, stale-while-revalidate=600');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<link rel="icon" type="image/jpeg" href="/assets/boss-lady-favicon.jpg">
<title>Page not found | Boss Lady Perfumery</title>
<style>
:root{--ink:#171214;--cream:#f8f3ed;--rose:#dda8b1;--gold:#c59a53;--muted:#c8b8b8;--line:#ffffff1c}*{box-sizing:border-box}html{min-height:100%;background:var(--ink)}body{min-height:100vh;margin:0;background:radial-gradient(circle at 78% 20%,#5b333d 0,transparent 30%),var(--ink);color:var(--cream);font-family:Arial,Helvetica,sans-serif}main{width:min(760px,calc(100% - 40px));min-height:100vh;margin:auto;padding:clamp(36px,9vw,100px) 0;display:flex;flex-direction:column;justify-content:center;position:relative}main:before{content:'404';position:absolute;right:-6vw;bottom:4vh;color:#ffffff08;font:italic 260px Georgia,serif;line-height:1;pointer-events:none}.logo{display:block;width:min(440px,88%);height:auto;margin-bottom:clamp(45px,8vw,85px)}.eyebrow{margin:0 0 18px;color:var(--rose);font-size:11px;letter-spacing:.24em;text-transform:uppercase}h1{max-width:650px;margin:0;color:var(--cream);font:400 clamp(48px,8vw,88px)/.94 Georgia,serif;letter-spacing:-.05em}h1 em{color:var(--rose);font-style:italic}p{max-width:480px;margin:25px 0 0;color:var(--muted);font-size:15px;line-height:1.8}.actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:34px}.button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 20px;border:1px solid var(--gold);border-radius:999px;color:var(--ink);background:var(--gold);font-size:12px;font-weight:700;text-decoration:none}.button.alt{color:var(--cream);background:transparent;border-color:var(--line)}.code{margin-top:70px;color:#ffffff66;font-size:10px;letter-spacing:.2em}@media(max-width:520px){main{width:calc(100% - 32px)}main:before{font-size:160px;right:-20px}.logo{width:100%;margin-bottom:55px}h1{font-size:54px}.actions{display:grid}.button{width:100%}}
</style>
</head>
<body>
<main>
  <img class="logo" src="/assets/boss-lady-logo.svg" width="640" height="180" alt="Boss Lady Perfumery">
  <p class="eyebrow">404 / Wrong turn</p>
  <h1>This page took<br><em>a different exit.</em></h1>
  <p>The link may have moved, but your next signature scent is still close. Return home or explore the collection.</p>
  <div class="actions"><a class="button" href="/">Back to home</a><a class="button alt" href="/shop">Explore the collection</a></div>
  <p class="code">BOSS LADY PERFUMERY</p>
</main>
</body>
</html>
