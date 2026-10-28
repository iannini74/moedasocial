<?php /** @var array $post @var string $html @var array $toc @var array $related */
$parts = preg_split('/(?=<h2 id=)/', $html);
$tags = array_filter(array_map('trim', explode(',', (string) $post['tags']))); ?>
<section class="page-hero">
  <div class="wrap">
    <?php partial('crumbs', ['items' => ['Blog' => '/blog', excerpt($post['title'], 50) => null]]) ?>
    <?php if ($tags): ?><p class="kicker"><?= e(implode(' · ', array_slice($tags, 0, 3))) ?></p><?php endif; ?>
    <h1><?= e($post['title']) ?></h1>
    <p class="lead"><?= e($post['description']) ?></p>
    <div class="meta-line">
      <span><?= icon('pen', 'icon-sm') ?> <?= e($post['author']) ?></span>
      <span><?= icon('calendar', 'icon-sm') ?> <?= date_long_br((int) $post['published_at']) ?></span>
      <span><?= icon('book', 'icon-sm') ?> <?= (int) $post['reading_min'] ?> min de leitura</span>
    </div>
  </div>
</section>
<div class="wrap article-layout">
  <nav class="toc" aria-label="Neste artigo">
    <?php if ($toc): ?><h2>Neste artigo</h2><ol><?php foreach ($toc as $t): ?><li><a href="#<?= e($t['id']) ?>"><?= e($t['title']) ?></a></li><?php endforeach; ?></ol><?php endif; ?>
  </nav>
  <article class="prose">
    <?php foreach ($parts as $i => $part): echo $part; if ($i === 2) ad('blog-' . $post['slug'], 'inline'); endforeach; ?>
    <?php if ($post['generated_by'] === 'agente-ia'): ?>
      <p class="muted" style="font-size:13.5px;margin-top:2em">Este artigo foi produzido pela redação do portal com apoio de inteligência artificial, a partir de pesquisa em fontes públicas listadas acima. Encontrou algo a corrigir? <a href="/contato">Avise a gente</a>.</p>
    <?php endif; ?>
    <?php partial('share', ['path' => '/blog/' . $post['slug'], 'title' => $post['title']]) ?>
  </article>
  <aside class="aside">
    <?php ad('blog-lateral', 'card') ?>
    <div class="aside-box">
      <h3>Diagnóstico gratuito</h3>
      <p>Simule o impacto de uma moeda social no seu município e gere o PDF.</p>
      <a class="btn btn-primary btn-sm" href="/diagnostico" data-ev="cta_click" data-ev-label="blog-aside-diagnostico">Fazer diagnóstico</a>
    </div>
    <?php if ($related): ?>
      <div class="aside-box">
        <h3>Leia também</h3>
        <ul class="check-list" style="font-size:14px"><?php foreach ($related as $r): ?><li><a href="/blog/<?= e($r['slug']) ?>"><?= e($r['title']) ?></a></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
  </aside>
</div>
