<?php /** @var array $studies */ ?>
<section class="page-hero">
  <div class="wrap">
    <?php partial('crumbs', ['items' => ['Estudos' => null]]) ?>
    <p class="kicker">Biblioteca</p>
    <h1>Estudos sobre moeda social e economia circular</h1>
    <p class="lead">Uma trilha de leitura completa, do conceito à implantação. Cada estudo cita suas fontes — leis, decisões, pesquisas acadêmicas e documentos oficiais — para que gestores e técnicos possam conferir e usar em pareceres e projetos.</p>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <div class="grid grid-3">
      <?php $i = 0; foreach ($studies as $s): $i++; ?>
        <a class="card reveal" href="/estudos/<?= e($s['slug']) ?>">
          <span class="card-num"><?= str_pad((string) $i, 2, '0', STR_PAD_LEFT) ?> · <?= e($s['kicker'] ?? 'Estudo') ?></span>
          <h3><?= e($s['title']) ?></h3>
          <p><?= e($s['description']) ?></p>
          <span class="more">Ler estudo · <?= reading_minutes($s['md']) ?> min →</span>
        </a>
        <?php if ($i === 6): ?><div class="reveal"><?php ad('estudos-lista', 'card') ?></div><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
