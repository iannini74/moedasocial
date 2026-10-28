<?php /** @var array $items @var int $total @var int $page @var int $per @var ?string $cat */
$pages = max(1, (int) ceil($total / $per)); ?>
<section class="page-hero">
  <div class="wrap">
    <?php partial('crumbs', ['items' => ['Notícias' => null]]) ?>
    <p class="kicker">Atualizado diariamente</p>
    <h1><?= $cat ? e(News::CATEGORIES[$cat]) : 'Notícias de moeda social, economia solidária e circular' ?></h1>
    <p class="lead">Curadoria automática das principais notícias sobre moedas sociais, bancos comunitários, economia solidária, economia circular e regulação de pagamentos que impacta municípios e comunidades.</p>
    <?php $last = (int) Db::setting('news_last_fetch'); if ($last): ?><div class="meta-line"><span><?= icon('clock', 'icon-sm') ?> Última atualização: <?= date_br($last, true) ?></span></div><?php endif; ?>
  </div>
</section>
<section class="section">
  <div class="wrap two-col two-col-wide">
    <div>
      <nav class="filters" aria-label="Filtrar por categoria">
        <a href="/noticias" class="<?= !$cat ? 'on' : '' ?>">Todas</a>
        <?php foreach (News::CATEGORIES as $k => $v): ?><a href="/noticias?categoria=<?= $k ?>" class="<?= $cat === $k ? 'on' : '' ?>"><?= e($v) ?></a><?php endforeach; ?>
      </nav>
      <?php if ($items): ?>
        <ul class="news-list">
          <?php foreach ($items as $i => $n): partial('news-item', ['n' => $n]); if ($i === 7): ?><li><?php ad('noticias-lista', 'banner') ?></li><?php endif; endforeach; ?>
        </ul>
        <?php if ($pages > 1): ?>
          <nav class="pagination" aria-label="Paginação">
            <?php for ($p = 1; $p <= min($pages, 12); $p++): $q = http_build_query(array_filter(['categoria' => $cat, 'pagina' => $p > 1 ? $p : null])); ?>
              <?php if ($p === $page): ?><span class="on"><?= $p ?></span><?php else: ?><a href="/noticias<?= $q ? '?' . $q : '' ?>"><?= $p ?></a><?php endif; ?>
            <?php endfor; ?>
          </nav>
        <?php endif; ?>
      <?php else: ?>
        <p class="empty">Ainda não há notícias nesta categoria. A curadoria roda várias vezes ao dia.</p>
      <?php endif; ?>
    </div>
    <aside class="aside">
      <?php ad('noticias-lateral', 'card') ?>
      <div class="aside-box">
        <h3>Receba os estudos por e-mail</h3>
        <p>Além das notícias, publicamos análises aprofundadas a cada 15 dias.</p>
        <a class="btn btn-ghost btn-sm" href="#nl-title">Assinar a newsletter</a>
      </div>
      <div class="aside-box">
        <h3>Sobre a curadoria</h3>
        <p>As notícias são coletadas automaticamente de fontes públicas (Google Notícias, Agência Brasil, Banco Central, Câmara, Senado e outras) e filtradas por relevância. O conteúdo é de responsabilidade dos veículos originais.</p>
      </div>
    </aside>
  </div>
</section>
