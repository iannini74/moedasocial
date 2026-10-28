<section class="page-hero"><div class="wrap narrow">
  <?php partial('crumbs', ['items' => ['Perguntas frequentes' => null]]) ?>
  <p class="kicker">Respostas diretas</p>
  <h1>Perguntas frequentes sobre moeda social</h1>
  <p class="lead">As dúvidas mais comuns de gestores, vereadores, técnicos de controle, comerciantes e cidadãos — com links para os estudos completos.</p>
</div></section>
<section class="section"><div class="wrap narrow">
  <?php foreach ($faq as $group => $items): ?>
    <h2 style="font-size:24px;margin:28px 0 14px"><?= e($group) ?></h2>
    <div class="faq">
      <?php foreach ($items as $f): ?><details><summary><?= e($f['q']) ?></summary><div class="answer"><p><?= e($f['a']) ?></p></div></details><?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  <div class="mt-3"><?php ad('faq', 'banner') ?></div>
  <p class="center mt-3">Não encontrou sua dúvida? <a href="/contato">Fale com a gente</a> ou explore os <a href="/estudos">estudos</a>.</p>
</div></section>
