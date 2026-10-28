<?php $letters = array_unique(array_map(fn($t) => mb_strtoupper(mb_substr(iconv('UTF-8', 'ASCII//TRANSLIT', $t), 0, 1)), array_keys($terms))); ?>
<section class="page-hero"><div class="wrap narrow">
  <?php partial('crumbs', ['items' => ['Glossário' => null]]) ?>
  <p class="kicker">Termos essenciais</p>
  <h1>Glossário de moeda social e economia circular</h1>
  <p class="lead">Definições claras dos termos usados em moedas sociais, pagamentos, economia solidária e Reforma Tributária.</p>
</div></section>
<section class="section"><div class="wrap narrow">
  <nav class="letters" aria-label="Letras"><?php foreach ($letters as $l): ?><a href="#letra-<?= $l ?>"><?= $l ?></a><?php endforeach; ?></nav>
  <dl class="glossary">
    <?php $prev = ''; foreach ($terms as $t => $d): $l = mb_strtoupper(mb_substr(iconv('UTF-8', 'ASCII//TRANSLIT', $t), 0, 1)); ?>
      <dt id="<?= e(slugify($t)) ?>"<?php if ($l !== $prev): $prev = $l; ?> data-letter="<?= $l ?>"<?php endif; ?>><?php if ($l === $prev && !isset($seen[$l])): $seen[$l] = 1; ?><span id="letra-<?= $l ?>"></span><?php endif; ?><?= e($t) ?></dt>
      <dd><?= e($d) ?></dd>
    <?php endforeach; ?>
  </dl>
</div></section>
