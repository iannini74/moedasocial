<?php
/** @var string $content */
$meta = ($meta ?? []) + ['title' => cfg('site.name'), 'description' => cfg('site.tagline'), 'path' => '/', 'type' => 'website', 'schema' => [], 'robots' => null];
$isHome = ($meta['path'] ?? '') === '/';
$fullTitle = $isHome ? $meta['title'] . ' | Portal Moeda Social' : $meta['title'] . ' | Moeda Social';
$canonical = url($meta['path'] ?? '/', true);
$current = '/' . explode('/', trim($_SERVER['REQUEST_URI'] ?? '/', '/'))[0];
$current = strtok($current, '?');
$nav = [
    '/estudos' => 'Estudos',
    '/por-que-adotar-moeda-social' => 'Por que adotar',
    '/noticias' => 'Notícias',
    '/blog' => 'Blog',
    '/perguntas-frequentes' => 'FAQ',
    '/contato' => 'Contato',
];
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($meta['description']) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="robots" content="<?= e($meta['robots'] ?? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1') ?>">
<meta name="theme-color" content="#0F2742">
<meta name="author" content="Portal Moeda Social">
<meta name="copyright" content="© <?= date('Y') ?> TribeX — todos os direitos reservados">
<meta property="og:type" content="<?= e($meta['type']) ?>">
<meta property="og:site_name" content="Portal Moeda Social">
<meta property="og:locale" content="pt_BR">
<meta property="og:title" content="<?= e($meta['title']) ?>">
<meta property="og:description" content="<?= e($meta['description']) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e(url('/assets/img/og-default.png', true)) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Portal Moeda Social — conhecimento para fazer o dinheiro circular na sua cidade">
<?php if (!empty($meta['published'])): ?><meta property="article:published_time" content="<?= date('c', (int) $meta['published']) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($meta['title']) ?>">
<meta name="twitter:description" content="<?= e($meta['description']) ?>">
<meta name="twitter:image" content="<?= e(url('/assets/img/og-default.png', true)) ?>">
<?php if (cfg('seo.google_verify')): ?><meta name="google-site-verification" content="<?= e(cfg('seo.google_verify')) ?>"><?php endif; ?>
<?php if (cfg('seo.bing_verify')): ?><meta name="msvalidate.01" content="<?= e(cfg('seo.bing_verify')) ?>"><?php endif; ?>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/assets/img/favicon-32.png" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<link rel="manifest" href="/assets/site.webmanifest">
<link rel="alternate" type="application/rss+xml" title="Blog Moeda Social" href="/feed.xml">
<link rel="alternate" type="text/markdown" title="Resumo para assistentes de IA" href="/llms.txt">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&family=Sora:wght@600;700;800&display=swap">
<link rel="stylesheet" href="<?= asset('css/main.css') ?>">
<link rel="stylesheet" href="<?= asset('css/print.css') ?>" media="print">
<?= Seo::jsonLd($meta['schema'] ?? []) ?>
</head>
<body class="page-<?= e(trim(str_replace('/', '-', $current), '-') ?: 'home') ?>">
<a class="skip" href="#conteudo">Pular para o conteúdo</a>

<header class="site-header" id="topo">
  <div class="wrap header-inner">
    <a class="logo" href="/" aria-label="Portal Moeda Social — página inicial">
      <?php partial('logo') ?>
      <span class="logo-text"><strong>moeda<span>social</span></strong><small>conhecimento que circula</small></span>
    </a>
    <button class="nav-toggle" aria-expanded="false" aria-controls="menu" aria-label="Abrir menu">
      <span></span><span></span><span></span>
    </button>
    <nav class="nav" id="menu" aria-label="Principal">
      <ul>
        <?php foreach ($nav as $href => $label): ?>
          <li><a href="<?= $href ?>"<?= $current === $href ? ' aria-current="page"' : '' ?>><?= $label ?></a></li>
        <?php endforeach; ?>
      </ul>
      <a class="btn btn-sm btn-primary" href="/diagnostico" data-ev="cta_click" data-ev-label="header-diagnostico">Diagnóstico grátis</a>
    </nav>
  </div>
</header>

<main id="conteudo">
<?= $content ?>
</main>

<?php if (!in_array($current, ['/contato'], true)): ?>
<section class="newsletter-band" aria-labelledby="nl-title">
  <div class="wrap nl-grid">
    <div>
      <p class="kicker">Newsletter</p>
      <h2 id="nl-title">Receba cada novo estudo antes de todo mundo</h2>
      <p>Um e-mail a cada novo artigo — análises, cases e mudanças na regulação que impactam municípios e comunidades. Sem spam, cancelamento em um clique.</p>
    </div>
    <?php partial('newsletter-form', ['source' => 'rodape' . ($meta['path'] ?? '')]) ?>
  </div>
</section>
<?php endif; ?>

<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <a class="logo logo-light" href="/"><?php partial('logo') ?><span class="logo-text"><strong>moeda<span>social</span></strong><small>conhecimento que circula</small></span></a>
      <p>Portal independente de conhecimento sobre moedas sociais, economia circular e desenvolvimento econômico local. Conteúdo com fontes, para gestores públicos, comunidades e empreendedores.</p>
      <a class="btn btn-sm btn-ghost-light" href="/contato">Quero implantar no meu município</a>
    </div>
    <div>
      <h3>Estudos</h3>
      <ul>
        <?php foreach (array_slice(Content::studies(), 0, 7) as $s): ?>
          <li><a href="/estudos/<?= e($s['slug']) ?>"><?= e($s['short'] ?? $s['title']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h3>Portal</h3>
      <ul>
        <li><a href="/por-que-adotar-moeda-social">Por que adotar</a></li>
        <li><a href="/diagnostico">Diagnóstico gratuito</a></li>
        <li><a href="/noticias">Notícias</a></li>
        <li><a href="/blog">Blog</a></li>
        <li><a href="/perguntas-frequentes">Perguntas frequentes</a></li>
        <li><a href="/glossario">Glossário</a></li>
        <li><a href="/sobre">Sobre o portal</a></li>
      </ul>
    </div>
    <div>
      <h3>Institucional</h3>
      <ul>
        <li><a href="/privacidade">Política de Privacidade</a></li>
        <li><a href="/cookies">Política de Cookies</a></li>
        <li><a href="/termos">Termos de Uso</a></li>
        <li><button type="button" class="linklike" data-cookie-prefs>Preferências de cookies</button></li>
        <li><a href="/feed.xml">RSS</a> · <a href="/llms.txt">llms.txt</a></li>
      </ul>
      <?php ad('rodape', 'mini') ?>
    </div>
  </div>
  <div class="footer-legal">
    <div class="wrap legal-inner">
      <p>© <?= date('Y') ?> Portal Moeda Social. Todos os direitos reservados. Conteúdo protegido pela Lei 9.610/1998 — reprodução permitida com citação da fonte e link para a página original.</p>
      <p class="dev">Direitos autorais e desenvolvimento: <a href="https://tribex.com.br" target="_blank" rel="noopener"><strong>TribeX</strong></a> · tribex.com.br</p>
    </div>
  </div>
</footer>

<?php partial('cookie-banner') ?>
<script>window.MS = {ga4: <?= json_encode((string) cfg('analytics.ga4_id')) ?>};</script>
<script src="<?= asset('js/main.js') ?>" defer></script>
<?php if (!empty($scripts)) foreach ($scripts as $s): ?><script src="<?= $s ?>" defer></script><?php endforeach; ?>
</body>
</html>
