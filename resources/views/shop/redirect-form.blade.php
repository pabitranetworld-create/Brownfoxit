<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>Redirecting to {{ $gateway }}…</title>
<style>body{font-family:system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0;background:#fbf9ff;color:#17112e}.b{text-align:center}.s{width:42px;height:42px;border-radius:50%;border:4px solid #f1d7cf;border-top-color:#ff5266;margin:0 auto 18px;animation:r .8s linear infinite}@keyframes r{to{transform:rotate(360deg)}}button{margin-top:14px;padding:12px 24px;border-radius:30px;border:0;background:#ff5266;color:#fff;font-weight:700;cursor:pointer}</style></head>
<body><div class="b"><div class="s"></div><p>Taking you to {{ $gateway }} to complete the payment…</p>
<form id="f" method="post" action="{{ $form['action'] }}">@foreach ($form['fields'] as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach<noscript><button type="submit">Continue to payment</button></noscript></form></div>
<script>document.getElementById('f').submit();</script></body></html>
