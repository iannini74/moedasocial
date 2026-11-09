<?php /** @var array $items @var int $total @var ?int $all @var array $states @var array $f @var int $page @var int $per */
$pages = max(1, (int) ceil($total / $per));
$qs = fn(array $over) => ($q = http_build_query(array_filter(array_merge(['categoria' => $f['categoria'], 'uf' => $f['uf'], 'abertos' => $f['abertos'] ? 1 : null], $over)))) ? '?' . $q : '';
$last = (int) Db::setting('editais_last_run'); ?>
<section class="page-hero">
  <div class="wrap">
    <?php partial('crumbs', ['items' => ['Editais' => null]]) ?>
    <p class="kicker">Pesquisado todos os dias</p>
    <h1><?= $f['categoria'] ? 'Editais: ' . e(Editais::CATEGORIES[$f['categoria']]) : 'Editais de moeda social e benefícios sociais' ?></h1>
    <p class="lead">Editais, chamamentos, licitações, credenciamentos, leis, decretos e anúncios oficiais de municípios, estados e do governo federal sobre moeda social, distribuição de benefícios e programas sociais.</p>
    <div class="meta-line">
      <?php if ($last): ?><span><?= icon('clock', 'icon-sm') ?> Última pesquisa: <?= date_br($last, true) ?></span><?php endif; ?>
      <span><?= icon('file', 'icon-sm') ?> <?= Editais::count() ?> publicações reunidas</span>
      <span><?= icon('refresh', 'icon-sm') ?> <a href="/editais.xml">Acompanhe por RSS</a></span>
    </div>
  </div>
</section>
<section class="section">
  <div class="wrap two-col two-col-wide">
    <div>
      <nav class="filters" aria-label="Filtrar por tema">
        <a href="/editais<?= $qs(['categoria' => null]) ?>" class="<?= !$f['categoria'] ? 'on' : '' ?>">Todos</a>
        <?php foreach (Editais::CATEGORIES as $k => $v): ?><a href="/editais<?= $qs(['categoria' => $k]) ?>" class="<?= $f['categoria'] === $k ? 'on' : '' ?>"><?= e($v) ?></a><?php endforeach; ?>
      </nav>
      <form class="ed-filter" method="get" action="/editais">
        <?php if ($f['categoria']): ?><input type="hidden" name="categoria" value="<?= e($f['categoria']) ?>"><?php endif; ?>
        <label>Estado
          <select name="uf" onchange="this.form.submit()">
            <option value="">Todos os estados</option>
            <?php foreach (ufs() as $uf): ?><option value="<?= $uf ?>"<?= $f['uf'] === $uf ? ' selected' : '' ?><?= isset($states[$uf]) ? '' : ' disabled' ?>><?= $uf ?><?= isset($states[$uf]) ? ' (' . $states[$uf] . ')' : '' ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="ed-check"><input type="checkbox" name="abertos" value="1"<?= $f['abertos'] ? ' checked' : '' ?> onchange="this.form.submit()"> Só com prazo aberto</label>
        <noscript><button class="btn btn-sm btn-ghost">Filtrar</button></noscript>
        <?php if ($all !== null): ?><span class="ed-count"><?= $total ?> de <?= $all ?> publicações</span><?php endif; ?>
      </form>
      <?php if ($items): ?>
        <ul class="news-list">
          <?php foreach ($items as $i => $x): partial('edital-item', ['x' => $x]); if ($i === 5): ?><li><?php ad('editais-lista', 'banner') ?></li><?php endif; endforeach; ?>
        </ul>
        <?php if ($pages > 1): ?>
          <nav class="pagination" aria-label="Paginação">
            <?php for ($p = 1; $p <= min($pages, 12); $p++): ?>
              <?php if ($p === $page): ?><span class="on"><?= $p ?></span><?php else: ?><a href="/editais<?= $qs(['pagina' => $p > 1 ? $p : null]) ?>"><?= $p ?></a><?php endif; ?>
            <?php endfor; ?>
          </nav>
        <?php endif; ?>
      <?php else: ?>
        <p class="empty">Nenhuma publicação encontrada com estes filtros. A pesquisa roda todos os dias e novos itens aparecem aqui assim que são conferidos.</p>
      <?php endif; ?>
    </div>
    <aside class="aside">
      <div class="aside-box">
        <h3>Sua prefeitura vai publicar um edital?</h3>
        <p>Ajudamos a preparar a lei municipal, o termo de referência e o credenciamento do comércio, com os cuidados que os Tribunais de Contas exigem.</p>
        <a class="btn btn-primary btn-sm" href="/contato" data-ev="cta_click" data-ev-label="editais-contato">Falar com especialistas</a>
      </div>
      <?php ad('editais-lateral', 'card') ?>
      <div class="aside-box">
        <h3>Como a pesquisa é feita</h3>
        <ol class="ed-agents">
          <li><span><?= icon('search', 'icon-sm') ?></span><div><strong>Coleta</strong> no Portal Nacional de Contratações Públicas (PNCP), em diários oficiais municipais e na imprensa.</div></li>
          <li><span><?= icon('globe', 'icon-sm') ?></span><div><strong>Pesquisa na web</strong> em sites de prefeituras, governos e portais de transparência.</div></li>
          <li><span><?= icon('check-circle', 'icon-sm') ?></span><div><strong>Análise</strong> de cada item: o que não é do tema é descartado, e o restante recebe título e resumo em linguagem simples.</div></li>
        </ol>
        <p class="ed-note">As informações vêm de fontes públicas. Confira sempre o documento original antes de tomar decisões.</p>
      </div>
    </aside>
  </div>
</section>
