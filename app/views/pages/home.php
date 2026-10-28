<?php
/** @var array $news @var array $posts @var array $studies */
$ticker = News::latest(10);
?>
<?php if ($ticker): ?>
<div class="ticker" aria-label="Últimas notícias">
  <div class="wrap ticker-inner">
    <span class="ticker-label">Hoje</span>
    <div class="ticker-viewport"><div class="ticker-track">
      <?php for ($i = 0; $i < 2; $i++): foreach ($ticker as $n): ?>
        <a href="<?= e($n['url']) ?>" target="_blank" rel="noopener nofollow"<?= $i ? ' aria-hidden="true" tabindex="-1"' : '' ?>><?= e($n['title']) ?> <span class="muted">· <?= e($n['source']) ?></span></a>
      <?php endforeach; endfor; ?>
    </div></div>
  </div>
</div>
<?php endif; ?>

<?php $hero = Hero::current(); ?>
<section class="hero hero-rot" data-hero="<?= (int) $hero['index'] ?>">
  <div class="wrap hero-grid">
    <div>
      <div class="hero-badges">
        <span class="badge-first"><?= icon('shield', 'icon-sm') ?> O primeiro portal brasileiro especializado em moeda social</span>
        <span class="badge-date"><?= icon('refresh', 'icon-sm') ?> Destaque de <?= date_br($hero['since']) ?></span>
      </div>
      <p class="kicker"><?= e($hero['kicker']) ?></p>
      <h1 class="hero-title"><?= $hero['title'] ?></h1>
      <p class="lead"><?= $hero['lead'] ?></p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="<?= e($hero['cta'][1]) ?>" data-ev="cta_click" data-ev-label="home-hero-<?= (int) $hero['index'] ?>"><?= e($hero['cta'][0]) ?></a>
        <a class="btn btn-ghost btn-lg" href="<?= e($hero['cta2'][1]) ?>"><?= e($hero['cta2'][0]) ?></a>
      </div>
      <ul class="hero-trust">
        <li>Conteúdo com fontes citadas</li>
        <li>Notícias atualizadas diariamente</li>
        <li>Para prefeituras e comunidades</li>
      </ul>
    </div>
    <?php partial('hero-panel', ['hero' => $hero]) ?>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <p class="kicker">Por que isso importa</p>
      <h2>Parte da renda do município é gasta em outras cidades</h2>
      <p>Quando salários, benefícios e pagamentos a fornecedores são gastos em outras cidades, em grandes redes ou na internet, o comércio local vende menos, gera menos empregos e o município arrecada menos. A moeda social direciona esse gasto para os comércios do próprio município.</p>
    </div>
    <div class="grid grid-4">
      <div class="card reveal"><div class="card-icon"><?= icon('repeat') ?></div><h3>Mantém a renda no município</h3><p>O recurso só pode ser usado em comércios credenciados do município. Cada compra gera vendas, salários e novas compras locais.</p></div>
      <div class="card reveal"><div class="card-icon"><?= icon('store') ?></div><h3>Aumenta as vendas do comércio</h3><p>Padarias, mercados, farmácias e MEIs passam a ter mais clientes e vendas mais previsíveis.</p></div>
      <div class="card reveal"><div class="card-icon"><?= icon('trending') ?></div><h3>Protege a arrecadação</h3><p>Com a Reforma Tributária, o IBS vai para o município onde o consumo acontece. Mais consumo local tende a significar mais receita local.</p></div>
      <div class="card reveal"><div class="card-icon"><?= icon('search') ?></div><h3>Registra cada transação</h3><p>Moedas digitais registram quem recebeu, onde gastou e quanto, o que facilita auditoria, prestação de contas e avaliação do programa.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="wrap">
    <div class="split-head">
      <div><p class="kicker">Notícias do dia</p><h2>O que está acontecendo no setor</h2></div>
      <a class="btn btn-ghost btn-sm" href="/noticias">Ver todas as notícias</a>
    </div>
    <div class="two-col" style="align-items:start">
      <div>
        <?php if ($news): ?>
          <ul class="news-list">
            <?php foreach (array_slice($news, 0, 6) as $n) partial('news-item', ['n' => $n]); ?>
          </ul>
        <?php else: ?>
          <p class="empty">As notícias estão sendo atualizadas. Volte em alguns minutos.</p>
        <?php endif; ?>
      </div>
      <div class="stack">
        <?php ad('home-noticias', 'card') ?>
        <div class="aside-box">
          <h3>Categorias</h3>
          <div class="chips" style="gap:6px">
            <?php foreach (News::CATEGORIES as $k => $v): ?><a class="tag tag-green" href="/noticias?categoria=<?= $k ?>"><?= e($v) ?></a><?php endforeach; ?>
          </div>
          <p class="muted mt-2" style="font-size:13px;margin-bottom:0">Curadoria automática a partir de fontes públicas, atualizada várias vezes ao dia. Os links levam aos veículos originais.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="split-head">
      <div><p class="kicker">Biblioteca de estudos</p><h2>Do conceito à implantação, com fontes</h2></div>
      <a class="btn btn-ghost btn-sm" href="/estudos">Todos os estudos</a>
    </div>
    <div class="grid grid-3">
      <?php $i = 0; foreach ($studies as $s): $i++; ?>
        <a class="card reveal" href="/estudos/<?= e($s['slug']) ?>">
          <span class="card-num"><?= str_pad((string) $i, 2, '0', STR_PAD_LEFT) ?> · <?= e($s['kicker'] ?? 'Estudo') ?></span>
          <h3><?= e($s['title']) ?></h3>
          <p><?= e(excerpt($s['description'], 150)) ?></p>
          <span class="more">Ler estudo →</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-cream">
  <div class="wrap">
    <div class="section-head center">
      <p class="kicker">Como funciona na prática</p>
      <h2>Como funciona, em cinco etapas</h2>
    </div>
    <ol class="steps">
      <li><strong>Lei e lastro</strong><p>O município aprova a lei do programa e reserva os recursos em reais, 1 para 1, em conta própria.</p></li>
      <li><strong>Comércio credenciado</strong><p>Lojas do município se cadastram de graça e recebem pelo celular, via QR Code.</p></li>
      <li><strong>Benefício no aplicativo</strong><p>As famílias recebem o crédito no celular e compram no comércio do bairro.</p></li>
      <li><strong>Uso entre empresas</strong><p>O lojista paga fornecedores locais com o saldo ou resgata em reais na própria conta.</p></li>
      <li><strong>Impacto medido</strong><p>Painéis mostram volume retido, bairros, setores e relatórios para o Tribunal de Contas.</p></li>
    </ol>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="cta-band">
      <div>
        <p class="kicker" style="color:var(--mint)">Diagnóstico gratuito</p>
        <h2>Quanto a moeda social pode movimentar no seu município?</h2>
        <p>Informe alguns dados públicos e receba, em segundos, uma estimativa de impacto em renda, empregos e arrecadação, a nota de prontidão e um roteiro de implantação — pronto para imprimir em PDF.</p>
      </div>
      <div class="actions">
        <a class="btn btn-light btn-lg" href="/diagnostico" data-ev="cta_click" data-ev-label="home-band-diagnostico">Começar diagnóstico</a>
        <a class="btn btn-ghost-light btn-lg" href="/por-que-adotar-moeda-social">Por que adotar?</a>
      </div>
    </div>
  </div>
</section>

<?php if ($posts): ?>
<section class="section section-alt">
  <div class="wrap">
    <div class="split-head">
      <div><p class="kicker">Blog</p><h2>Análises recentes</h2></div>
      <a class="btn btn-ghost btn-sm" href="/blog">Ver o blog</a>
    </div>
    <div class="grid grid-3">
      <?php foreach ($posts as $k => $p) partial('post-card', ['p' => $p, 'k' => $k]); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="wrap">
    <?php ad('home-rodape', 'banner') ?>
  </div>
</section>
