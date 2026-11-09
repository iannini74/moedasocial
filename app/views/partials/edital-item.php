<?php /** @var array $x */
$ts = (int) $x['published_at'];
$deadline = (int) $x['deadline_at'];
$open = $deadline && $deadline >= strtotime('today');
$place = trim($x['municipio'] . ($x['uf'] ? '/' . $x['uf'] : ''), '/');
$catIcon = ['moeda-social' => 'coins', 'distribuicao-beneficios' => 'wallet', 'beneficios-sociais' => 'users'][$x['categoria']] ?? 'file'; ?>
<li class="news-item edital">
  <div class="news-date"><b><?= date('d', $ts) ?></b><?= ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'][(int) date('n', $ts) - 1] ?></div>
  <div>
    <div class="news-meta">
      <a class="tag tag-green" href="/editais?categoria=<?= e($x['categoria']) ?>"><?= icon($catIcon, 'icon-xs') ?><?= e(Editais::CATEGORIES[$x['categoria']] ?? 'Edital') ?></a>
      <span class="tag"><?= e(Editais::TYPES[$x['tipo']] ?? 'Publicação oficial') ?></span>
      <?php if ($place): ?><span><?= e($place) ?></span><?php endif; ?>
      <?php if (!empty($x['pinned'])): ?><span class="tag tag-blue">Destaque</span><?php endif; ?>
    </div>
    <h3><a href="<?= e($x['url']) ?>" target="_blank" rel="noopener nofollow" data-ev="edital_click" data-ev-label="<?= (int) $x['id'] ?>"><?= e($x['title']) ?></a></h3>
    <?php if (!empty($x['summary'])): ?><p><?= e($x['summary']) ?></p><?php endif; ?>
    <div class="ed-foot">
      <?php if ($x['orgao']): ?><span><?= icon('landmark', 'icon-xs') ?> <?= e($x['orgao']) ?></span><?php endif; ?>
      <?php if ($deadline): ?><span class="ed-deadline <?= $open ? 'is-open' : 'is-closed' ?>"><?= icon('calendar', 'icon-xs') ?> Prazo: <?= date_br($deadline) ?> · <?= $open ? 'aberto' : 'encerrado' ?></span><?php endif; ?>
      <?php if ((float) $x['valor'] > 0): ?><span><?= icon('coins', 'icon-xs') ?> Valor estimado: <?= money_br((float) $x['valor']) ?></span><?php endif; ?>
      <a class="ed-source" href="<?= e($x['url']) ?>" target="_blank" rel="noopener nofollow">Ver na fonte (<?= e($x['source']) ?>) <?= icon('arrow-right', 'icon-xs') ?></a>
    </div>
  </div>
</li>
