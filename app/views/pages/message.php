<div class="wrap">
  <div class="message-box <?= !empty($ok) ? '' : 'bad' ?>">
    <div class="icon"><?= icon(!empty($ok) ? 'check-circle' : 'alert', 'icon', 34) ?></div>
    <h1 style="font-size:clamp(24px,4vw,32px)"><?= e($heading) ?></h1>
    <p><?= e($text) ?></p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:20px">
      <a class="btn btn-primary" href="/estudos">Explorar os estudos</a>
      <a class="btn btn-ghost" href="/">Página inicial</a>
    </div>
  </div>
</div>
