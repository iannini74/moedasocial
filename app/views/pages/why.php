<?php
/** @var array $faq */
$ibs = [[2029, 80], [2033, 90], [2040, 76], [2050, 56], [2060, 36], [2070, 16], [2077, 2]];
$reasons = [
    ['repeat', 'O benefício é gasto na cidade', 'Quem recebe só pode usar o valor nos comércios cadastrados do município.'],
    ['store', 'Mais vendas para o comércio do bairro', 'Padarias, mercados e farmácias ganham clientes novos todos os meses.'],
    ['users', 'Mais empregos', 'Com mais vendas, o comércio contrata mais gente da própria cidade.'],
    ['trending', 'Mais imposto para a cidade', 'Com a Reforma Tributária, o imposto vai cada vez mais para a cidade onde a compra é feita.'],
    ['search', 'Controle de cada real', 'Cada pagamento fica registrado: quem recebeu, onde gastou e quanto.'],
    ['target', 'Programas sociais com mais resultado', 'O mesmo dinheiro ajuda as famílias e o comércio ao mesmo tempo.'],
    ['factory', 'Empresas locais mais fortes', 'Fornecedores da cidade vendem mais, e empresas podem pagar prêmios na moeda.'],
    ['phone', 'Conta digital para quem não tem banco', 'As famílias usam o celular. O comerciante recebe por QR Code.'],
    ['leaf', 'Incentivo à reciclagem', 'A prefeitura pode pagar em moeda social por material reciclável entregue.'],
    ['landmark', 'Referência para outras cidades', 'Um programa inovador, com resultados que podem ser medidos e mostrados.'],
];
?>
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <?php partial('crumbs', ['items' => ['Por que adotar' => null]]) ?>
      <p class="kicker">Para prefeitos, secretários e vereadores</p>
      <h1>Por que seu município deve adotar uma <span class="grad-text">moeda social</span>?</h1>
      <p class="lead">Hoje, parte do dinheiro dos moradores é gasta em outras cidades e na internet. Com a moeda social, benefícios e pagamentos da prefeitura passam a ser gastos no comércio do próprio município. Resultado: mais vendas, mais empregos e mais controle sobre o dinheiro público.</p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="/diagnostico" data-ev="cta_click" data-ev-label="why-hero-diagnostico">Ver o resultado no meu município</a>
        <a class="btn btn-ghost btn-lg" href="#como-funciona">Entender em 2 minutos</a>
      </div>
    </div>
    <div class="flow-card">
      <h2>Como funciona, em 4 passos</h2>
      <ol class="vsteps">
        <li><span class="vs-icon"><?= icon('landmark') ?></span><div><strong>A prefeitura paga o benefício</strong><span>em moeda social, pelo aplicativo</span></div></li>
        <li><span class="vs-icon"><?= icon('users') ?></span><div><strong>A família compra na cidade</strong><span>só nos comércios cadastrados</span></div></li>
        <li><span class="vs-icon"><?= icon('store') ?></span><div><strong>O comércio vende mais</strong><span>e recebe pelo celular</span></div></li>
        <li><span class="vs-icon"><?= icon('briefcase') ?></span><div><strong>A cidade inteira ganha</strong><span>fornecedores, empregos e impostos</span></div></li>
      </ol>
    </div>
  </div>
</section>

<section class="section" id="como-funciona">
  <div class="wrap">
    <div class="section-head center">
      <p class="kicker">A diferença, em uma imagem</p>
      <h2>O que acontece com R$ 100 de benefício</h2>
      <p>Compare o mesmo valor pago de duas formas diferentes.</p>
    </div>
    <div class="compare">
      <div class="compare-card">
        <div class="compare-top"><span class="h-icon warn"><?= icon('coins') ?></span><h3>Pago em dinheiro ou cartão comum</h3></div>
        <div class="money-bar" role="img" aria-label="R$ 58 gastos na cidade e R$ 42 gastos fora">
          <span class="mb-in" style="width:58%">R$ 58</span><span class="mb-out" style="width:42%">R$ 42</span>
        </div>
        <ul class="money-legend">
          <li><i class="sw-in"></i><?= icon('home', 'icon-sm') ?> R$ 58 gastos no comércio da cidade</li>
          <li><i class="sw-out"></i><?= icon('globe', 'icon-sm') ?> R$ 42 gastos em outras cidades e na internet</li>
        </ul>
        <p class="compare-result bad"><?= icon('x-circle', 'icon-sm') ?> Quase metade do valor sai do município.</p>
      </div>
      <div class="compare-card best">
        <div class="compare-top"><span class="h-icon ok"><?= icon('wallet') ?></span><h3>Pago em moeda social</h3></div>
        <div class="money-bar" role="img" aria-label="R$ 100 gastos na cidade">
          <span class="mb-in" style="width:100%">R$ 100</span>
        </div>
        <ul class="money-legend">
          <li><i class="sw-in"></i><?= icon('home', 'icon-sm') ?> R$ 100 gastos no comércio da cidade</li>
          <li><i class="sw-again"></i><?= icon('repeat', 'icon-sm') ?> Depois, o comerciante usa parte desse valor para pagar fornecedores e funcionários da cidade</li>
        </ul>
        <p class="compare-result good"><?= icon('check-circle', 'icon-sm') ?> Todo o valor é gasto no município, e parte dele é gasta de novo.</p>
      </div>
    </div>
    <p class="note center">Exemplo ilustrativo, considerando que 42% do gasto das famílias é feito fora do município. O percentual real varia de cidade para cidade — calcule o seu no <a href="/diagnostico">diagnóstico gratuito</a>.</p>
  </div>
</section>

<section class="section section-alt" id="argumentos">
  <div class="wrap">
    <div class="section-head">
      <p class="kicker">10 motivos</p>
      <h2>O que a moeda social traz para a cidade</h2>
    </div>
    <div class="reasons">
      <?php foreach ($reasons as $i => [$ic, $t, $d]): ?>
        <div class="reason reveal">
          <span class="reason-icon"><?= icon($ic) ?></span>
          <div><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <p class="kicker">O que os estudos mostram</p>
      <h2>Resultados já medidos</h2>
      <p>Números de pesquisas independentes e de prefeituras que já usam moeda social.</p>
    </div>
    <div class="grid grid-4">
      <div class="evidence reveal">
        <span class="ev-icon"><?= icon('store') ?></span>
        <strong>Mais de R$ 2</strong>
        <p>movimentados no comércio de Maricá para cada R$ 1 pago em moeda social.</p>
        <small>Fonte: <a href="https://eaesp.fgv.br/blog/administracao-de-empresas/como-transferencia-de-renda-fortalece-economia-local-mas-amplia" target="_blank" rel="noopener">FGV, 2026</a></small>
      </div>
      <div class="evidence reveal">
        <span class="ev-icon"><?= icon('users') ?></span>
        <strong>+9%</strong>
        <p>de renda nas famílias que recebem o benefício em moeda social em Maricá.</p>
        <small>Fonte: <a href="https://www.nber.org/papers/w33089" target="_blank" rel="noopener">NBER, 2024</a></small>
      </div>
      <div class="evidence reveal">
        <span class="ev-icon"><?= icon('home') ?></span>
        <strong>54 mil famílias</strong>
        <p>atendidas em Niterói, com mais de 8 mil comércios aceitando a moeda.</p>
        <small>Fonte: <a href="https://cidadedeniteroi.com/economia/pagamento-da-moeda-arariboia-de-niteroi-e-antecipado/" target="_blank" rel="noopener">Niterói, 2026</a></small>
      </div>
      <div class="evidence reveal">
        <span class="ev-icon"><?= icon('trending') ?></span>
        <strong>95%</strong>
        <p>dos comerciantes de Barcelona que aceitaram a moeda local tiveram mais clientes.</p>
        <small>Fonte: <a href="https://bcnroc.ajuntament.barcelona.cat/jspui/bitstream/11703/114011/3/bmincome_butlleti_2_ca.pdf.txt" target="_blank" rel="noopener">Prefeitura de Barcelona, 2019</a></small>
      </div>
    </div>
    <p class="note center">Ainda não há estudo que comprove aumento de arrecadação causado por moeda social. Por isso, recomendamos que o município meça esse resultado desde o primeiro mês.</p>
  </div>
</section>

<section class="section section-alt">
  <div class="wrap two-col">
    <div>
      <p class="kicker">Reforma Tributária</p>
      <h2>O imposto vai mudar de endereço</h2>
      <p>Até 2032, ISS e ICMS serão substituídos por um novo imposto, o <strong>IBS</strong>. A grande mudança: ele vai, cada vez mais, para a <strong>cidade onde a compra acontece</strong>.</p>
      <ol class="plain-steps">
        <li><strong>Até 2028</strong> — vale a regra atual.</li>
        <li><strong>De 2029 a 2077</strong> — uma parte do IBS é dividida pelo que cada cidade arrecadou no passado (média de 2019 a 2026). A outra parte vai para a cidade onde a compra foi feita.</li>
        <li><strong>A cada ano</strong> — a parte "pela compra" aumenta, até chegar a quase 100%.</li>
      </ol>
      <p class="highlight"><?= icon('info', 'icon-sm') ?> Em outras palavras: <strong>quando o morador compra em outra cidade, o imposto dessa compra vai para a outra cidade.</strong> A moeda social ajuda a manter essas compras no município.</p>
      <a class="btn btn-ghost" href="/estudos/reforma-tributaria-e-municipios">Ver a explicação completa</a>
    </div>
    <figure class="card ibs-chart">
      <figcaption>
        <h3>Como o IBS será dividido entre as cidades</h3>
        <p>Parte de cada 100 reais de IBS, por ano</p>
        <div class="chart-legend">
          <span><i style="background:#0060E8"></i>Pelo histórico da cidade (2019–2026)</span>
          <span><i style="background:#00A06A"></i>Para a cidade onde a compra acontece</span>
        </div>
      </figcaption>
      <div class="ibs-rows">
        <?php foreach ($ibs as [$y, $hist]): $loc = 100 - $hist; ?>
          <div class="ibs-row" title="<?= $y ?>: <?= $hist ?>% pelo histórico, <?= $loc ?>% pelo local da compra">
            <span class="ibs-year"><?= $y ?></span>
            <div class="ibs-bar">
              <span class="seg hist" style="width:<?= $hist ?>%"><?= $hist >= 14 ? $hist . '%' : '' ?></span>
              <span class="seg loc" style="width:<?= $loc ?>%"><?= $loc >= 14 ? $loc . '%' : '' ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="chart-source">Regra de transição da Emenda Constitucional 132/2023 (ADCT, art. 131) e da Lei Complementar 227/2026. Valores arredondados.</p>
    </figure>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <p class="kicker">Aprendendo com quem já fez</p>
      <h2>O que dá certo e o que deu errado</h2>
      <p>Os problemas em outras cidades vieram da forma de execução, não da ideia. Veja a diferença:</p>
    </div>
    <div class="pros-cons">
      <div class="card card-ok">
        <h3><span class="h-icon ok"><?= icon('check-circle') ?></span> O que dá segurança ao programa</h3>
        <ul class="icon-list ok">
          <li>Uma lei municipal que define todas as regras, inclusive as taxas</li>
          <li>O dinheiro do programa guardado em conta da própria prefeitura</li>
          <li>A empresa de tecnologia não guarda o dinheiro</li>
          <li>Moeda aceita só nos comércios cadastrados da cidade</li>
          <li>Pagamento pelo celular, sem cartão e sem máquina</li>
          <li>Relatórios prontos para o Tribunal de Contas</li>
          <li>Contrato que permite trocar de fornecedor sem perder dados</li>
          <li>Nenhum custo de tecnologia para a prefeitura</li>
        </ul>
      </div>
      <div class="card card-warn">
        <h3><span class="h-icon warn"><?= icon('alert') ?></span> O que causou problemas em outras cidades</h3>
        <ul class="icon-list warn">
          <li>Dinheiro do programa misturado com o da empresa operadora</li>
          <li>Rendimentos do dinheiro público ficando com a operadora</li>
          <li>Taxas cobradas sem estar previstas na lei</li>
          <li>Contratações de emergência, uma atrás da outra</li>
          <li>Fornecedor dono do sistema e dos dados</li>
          <li>Cartões e máquinas caros, com pouco uso entre comerciantes</li>
          <li>Cadastro sem conferência, com pessoas falecidas recebendo</li>
          <li>Programa criado ou ampliado perto da eleição</li>
        </ul>
      </div>
    </div>
    <p class="center mt-2"><a href="/estudos/cases-brasil">Ver os casos brasileiros em detalhe →</a></p>
  </div>
</section>

<section class="section section-cream">
  <div class="wrap">
    <div class="section-head center">
      <p class="kicker">Benefícios por público</p>
      <h2>O que cada um ganha</h2>
    </div>
    <div class="grid grid-4">
      <div class="card"><div class="card-icon"><?= icon('landmark') ?></div><h3>Prefeito(a)</h3><p>Uma política pública com resultados visíveis e controle total do dinheiro.</p></div>
      <div class="card"><div class="card-icon"><?= icon('briefcase') ?></div><h3>Secretaria de Fazenda</h3><p>Proteção da arrecadação na Reforma Tributária e dados de consumo da cidade.</p></div>
      <div class="card"><div class="card-icon"><?= icon('store') ?></div><h3>Comerciante</h3><p>Mais clientes, recebimento pelo celular e dinheiro na própria conta.</p></div>
      <div class="card"><div class="card-icon"><?= icon('users') ?></div><h3>Família</h3><p>Benefício seguro no celular e compras no comércio do bairro.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php ad('porque-adotar', 'banner') ?>
  </div>
</section>

<section class="section section-alt">
  <div class="wrap narrow">
    <div class="section-head center"><p class="kicker">Dúvidas frequentes</p><h2>Perguntas de quem decide</h2></div>
    <div class="faq">
      <?php foreach ($faq as $f): ?>
        <details><summary><?= e($f['q']) ?></summary><div class="answer"><p><?= e($f['a']) ?></p></div></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="cta-band">
      <div>
        <p class="kicker" style="color:var(--mint)">Próximo passo</p>
        <h2>Veja, em 3 minutos, o resultado no seu município</h2>
        <p>O diagnóstico gratuito calcula o impacto em vendas, empregos e impostos, mostra o que falta para começar e gera um relatório em PDF para apresentar ao prefeito e à Câmara.</p>
      </div>
      <div class="actions">
        <a class="btn btn-light btn-lg" href="/diagnostico" data-ev="cta_click" data-ev-label="why-band-diagnostico">Fazer diagnóstico</a>
        <a class="btn btn-ghost-light btn-lg" href="/contato">Falar com especialistas</a>
      </div>
    </div>
  </div>
</section>
