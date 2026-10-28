<?php
/** @var array $hero  Painel ilustrado da primeira dobra (muda junto com a mensagem). */
$panel = $hero['panel'];
if ($panel === 'flow') {
    partial('hero-flow');
    return;
}
$rows = fn(array $items) => implode('', array_map(fn($r) => '<li><span class="hp-icon">' . icon($r[0]) . '</span><div><strong>' . $r[1] . '</strong><span>' . $r[2] . '</span></div></li>', $items));
?>
<div class="flow-card hero-panel">
<?php if ($panel === 'ibs'): ?>
  <h2>Parte do IBS que vai para a cidade onde a compra acontece</h2>
  <div class="hp-bars">
    <?php foreach ([[2029, 20], [2040, 24], [2050, 44], [2060, 64], [2077, 98]] as [$y, $v]): ?>
      <div class="hp-bar-row"><span><?= $y ?></span><div class="hp-track"><i style="width:<?= $v ?>%"></i></div><b><?= $v ?>%</b></div>
    <?php endforeach; ?>
  </div>
  <p class="hp-note">O restante é dividido pelo histórico de arrecadação de 2019 a 2026. Fonte: EC 132/2023 e LC 227/2026.</p>

<?php elseif ($panel === 'marica' || $panel === 'niteroi'): $m = $panel === 'marica'; ?>
  <h2><?= $m ? 'Maricá em números' : 'Niterói em números' ?></h2>
  <div class="hp-stats">
    <?php if ($m): ?>
      <div><strong>2013</strong><span>ano de criação da moeda Mumbuca, por lei municipal</span></div>
      <div><strong>71 mil+</strong><span>pessoas na Renda Básica em 2025</span></div>
      <div><strong>2×+</strong><span>movimento no comércio para cada real pago (FGV)</span></div>
      <div><strong>+9%</strong><span>de renda nas famílias beneficiadas (NBER)</span></div>
    <?php else: ?>
      <div><strong>2021</strong><span>ano de criação da moeda Arariboia, por lei municipal</span></div>
      <div><strong>54 mil+</strong><span>famílias atendidas em 2026</span></div>
      <div><strong>8 mil+</strong><span>comércios aceitando a moeda</span></div>
      <div><strong>80%</strong><span>do valor gasto em alimentação</span></div>
    <?php endif; ?>
  </div>
  <p class="hp-note">Fontes: Ipea, FGV, NBER e prefeituras. Detalhes no estudo de casos brasileiros.</p>

<?php elseif ($panel === 'diagnostic'): ?>
  <h2>O que o diagnóstico entrega</h2>
  <ul class="hp-list"><?= $rows([
      ['coins', 'Valor movimentado no comércio', 'estimativa anual para o município'],
      ['users', 'Empregos e famílias alcançadas', 'com base na população e no benefício'],
      ['trending', 'Impacto nos impostos', 'ISS, ICMS e o futuro IBS'],
      ['check-circle', 'Nota de prontidão', 'o que já existe e o que falta'],
      ['file', 'Relatório em PDF', 'pronto para apresentar ao prefeito'],
  ]) ?></ul>

<?php elseif ($panel === 'legal'): ?>
  <h2>As 4 regras que tornam a moeda social legal</h2>
  <ul class="hp-list"><?= $rows([
      ['coins', '1 moeda social = 1 real', 'sempre o mesmo valor'],
      ['wallet', 'O valor fica guardado em reais', 'em conta da própria prefeitura'],
      ['store', 'Aceita só no município', 'nos comércios cadastrados'],
      ['landmark', 'Criada por lei municipal', 'com regras claras, inclusive de taxas'],
  ]) ?></ul>

<?php elseif ($panel === 'control'): ?>
  <h2>O que o Tribunal de Contas verifica</h2>
  <ul class="hp-list"><?= $rows([
      ['wallet', 'Onde está o dinheiro', 'conta específica da prefeitura'],
      ['coins', 'Quem fica com os rendimentos', 'devem voltar para o programa'],
      ['file', 'Quais taxas são cobradas', 'só as previstas em lei'],
      ['users', 'Quem recebe o benefício', 'cadastro conferido e atualizado'],
  ]) ?></ul>

<?php elseif ($panel === 'stats'): ?>
  <h2>O que já foi medido</h2>
  <div class="hp-stats">
    <div><strong>2×+</strong><span>movimento no comércio de Maricá para cada real pago (FGV)</span></div>
    <div><strong>95%</strong><span>dos comerciantes de Barcelona tiveram mais clientes</span></div>
    <div><strong>+3,4%</strong><span>de faturamento em lojas credenciadas na Coreia do Sul (KILA)</span></div>
    <div><strong>47,7%</strong><span>da receita do comércio independente fica na cidade, contra 13,6% das redes</span></div>
  </div>

<?php elseif ($panel === 'article'): $p = $hero['post'] ?? null; ?>
  <h2>Artigo mais recente</h2>
  <?php if ($p): ?>
    <a class="hp-article" href="/blog/<?= e($p['slug']) ?>">
      <span class="hp-article-cover" aria-hidden="true"><?= icon('book', 'icon', 40) ?></span>
      <strong><?= e($p['title']) ?></strong>
      <span><?= date_br((int) $p['published_at']) ?> · <?= (int) $p['reading_min'] ?> min de leitura</span>
    </a>
  <?php endif; ?>
  <p class="hp-note">Novos artigos a cada 15 dias. Receba por e-mail na newsletter.</p>

<?php elseif ($panel === 'steps'): ?>
  <h2>Roteiro de implantação</h2>
  <ol class="hp-timeline">
    <li><b>Mês 1</b> Diagnóstico e desenho do programa</li>
    <li><b>Mês 2</b> Projeto de lei e consulta jurídica</li>
    <li><b>Mês 3</b> Lei aprovada e conta do programa aberta</li>
    <li><b>Mês 4</b> Tecnologia contratada e configurada</li>
    <li><b>Mês 5</b> Cadastro do comércio e das famílias</li>
    <li><b>Mês 6</b> Lançamento e painel público de resultados</li>
  </ol>
<?php endif; ?>
</div>
