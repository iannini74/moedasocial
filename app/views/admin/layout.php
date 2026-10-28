<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Painel · Portal Moeda Social</title>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&family=Sora:wght@700;800&display=swap">
<link rel="stylesheet" href="<?= asset('css/main.css') ?>">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin">
<?php if (!empty($_SESSION['admin'])): ?>
<header class="adm-top">
  <div class="adm-wrap">
    <a class="logo" href="/admin"><?php partial('logo') ?><span class="logo-text"><strong>moeda<span>social</span></strong><small>painel administrativo</small></span></a>
    <nav class="adm-nav">
      <?php $cur = $__view; foreach (['admin/dashboard' => ['/admin', 'Analytics'], 'admin/contacts' => ['/admin/contatos', 'Contatos'], 'admin/subscribers' => ['/admin/inscritos', 'Inscritos'], 'admin/diagnostics' => ['/admin/diagnosticos', 'Diagnósticos'], 'admin/blog' => ['/admin/blog', 'Blog'], 'admin/news' => ['/admin/noticias', 'Notícias']] as $v => [$h, $l]): ?>
        <a href="<?= $h ?>"<?= $cur === $v ? ' aria-current="page"' : '' ?>><?= $l ?></a>
      <?php endforeach; ?>
      <a href="/" target="_blank">Ver site ↗</a>
      <a href="/admin/sair">Sair</a>
    </nav>
  </div>
</header>
<?php endif; ?>
<main class="adm-wrap adm-main"><?= $content ?></main>
<script src="<?= asset('js/admin.js') ?>" defer></script>
</body>
</html>
