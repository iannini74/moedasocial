<?php /** @var array $posts @var int $total @var int $page @var int $per */ $pages = max(1, (int) ceil($total / $per)); ?>
<section class="page-hero">
  <div class="wrap">
    <?php partial('crumbs', ['items' => ['Blog' => null]]) ?>
    <p class="kicker">Novo artigo a cada 15 dias</p>
    <h1>Blog Moeda Social</h1>
    <p class="lead">Análises, guias práticos e casos sobre moedas sociais, economia circular, regulação e desenvolvimento econômico dos municípios. <a href="/feed.xml">Assine o RSS</a> ou receba por e-mail.</p>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <?php if ($posts): ?>
      <div class="grid grid-3">
        <?php foreach ($posts as $k => $p): partial('post-card', ['p' => $p, 'k' => $k]); if ($k === 2): ?><div class="reveal"><?php ad('blog-lista', 'card') ?></div><?php endif; endforeach; ?>
      </div>
      <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Paginação">
          <?php for ($p = 1; $p <= $pages; $p++): ?>
            <?php if ($p === $page): ?><span class="on"><?= $p ?></span><?php else: ?><a href="/blog<?= $p > 1 ? '?pagina=' . $p : '' ?>"><?= $p ?></a><?php endif; ?>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <p class="empty">O primeiro artigo está a caminho.</p>
    <?php endif; ?>
  </div>
</section>
