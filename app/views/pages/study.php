<?php /** @var array $study @var string $html @var array $toc @var ?array $prev @var ?array $next @var int $reading */
// Anúncio inline após a 3ª seção
$parts = preg_split('/(?=<h2 id=)/', $html);
?>
<section class="page-hero">
  <div class="wrap">
    <?php partial('crumbs', ['items' => ['Estudos' => '/estudos', $study['short'] ?? $study['title'] => null]]) ?>
    <p class="kicker"><?= e($study['kicker'] ?? 'Estudo') ?></p>
    <h1><?= e($study['title']) ?></h1>
    <p class="lead"><?= e($study['description']) ?></p>
    <div class="meta-line">
      <span>📖 <?= $reading ?> min de leitura</span>
      <span>🔄 Atualizado em <?= date_br($study['updated']) ?></span>
      <span>📚 Com fontes citadas</span>
    </div>
  </div>
</section>
<div class="wrap article-layout">
  <nav class="toc" aria-label="Neste estudo">
    <?php if ($toc): ?>
      <h2>Neste estudo</h2>
      <ol><?php foreach ($toc as $t): ?><li><a href="#<?= e($t['id']) ?>"><?= e($t['title']) ?></a></li><?php endforeach; ?></ol>
    <?php endif; ?>
  </nav>
  <article class="prose">
    <?php foreach ($parts as $i => $part): echo $part; if ($i === 3) ad('estudo-' . $study['slug'], 'inline'); endforeach; ?>
    <?php partial('share', ['path' => '/estudos/' . $study['slug'], 'title' => $study['title']]) ?>
    <div class="pager">
      <?php if ($prev): ?><a href="/estudos/<?= e($prev['slug']) ?>"><small>← Estudo anterior</small><?= e($prev['title']) ?></a><?php else: ?><span></span><?php endif; ?>
      <?php if ($next): ?><a class="next" href="/estudos/<?= e($next['slug']) ?>"><small>Próximo estudo →</small><?= e($next['title']) ?></a><?php endif; ?>
    </div>
  </article>
  <aside class="aside">
    <div class="aside-box">
      <h3>Diagnóstico gratuito</h3>
      <p>Veja quanto uma moeda social pode movimentar no seu município e receba um roteiro de implantação em PDF.</p>
      <a class="btn btn-primary btn-sm" href="/diagnostico" data-ev="cta_click" data-ev-label="estudo-aside-diagnostico">Fazer diagnóstico</a>
    </div>
    <?php ad('estudo-lateral', 'card') ?>
    <div class="aside-box">
      <h3>Precisa de apoio?</h3>
      <p>Especialistas em moeda social e meios de pagamento ajudam da minuta de lei ao credenciamento do comércio.</p>
      <a class="btn btn-ghost btn-sm" href="/contato">Falar com especialistas</a>
    </div>
  </aside>
</div>
