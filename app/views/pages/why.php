<?php /** @var array $faq */ ?>
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <?php partial('crumbs', ['items' => ['Por que adotar' => null]]) ?>
      <p class="kicker">Para prefeitos, secretários e vereadores</p>
      <h1>Por que seu município deve adotar uma <span class="grad-text">moeda social</span>?</h1>
      <p class="lead">Quando a renda dos moradores é gasta em outras cidades, o município perde vendas, empregos e, com a Reforma Tributária, receita. A moeda social direciona benefícios e pagamentos públicos para o comércio local e registra cada transação.</p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="/diagnostico" data-ev="cta_click" data-ev-label="why-hero-diagnostico">Ver o impacto no meu município</a>
        <a class="btn btn-ghost btn-lg" href="#argumentos">Conhecer os argumentos</a>
      </div>
    </div>
    <div class="flow-card">
      <h2>Em números, o que já se sabe</h2>
      <div class="flow-legend" style="grid-template-columns:1fr">
        <div><strong>2×+</strong><span>atividade econômica local para cada real distribuído em Maricá, segundo a FGV (3,4 milhões de transações)<sup>1</sup></span></div>
        <div><strong>+9%</strong><span>de renda domiciliar e +5% de consumo entre beneficiários da renda básica de Maricá (NBER)<sup>2</sup></span></div>
        <div><strong>8 mil+</strong><span>comércios credenciados à moeda Arariboia em Niterói, com 54 mil famílias (2026)<sup>3</sup></span></div>
        <div><strong>2019–2026</strong><span>janela de ISS e ICMS que define parte da partilha do IBS até 2077 (LC 227/2026)<sup>4</sup></span></div>
      </div>
    </div>
  </div>
</section>

<section class="section" id="argumentos">
  <div class="wrap">
    <div class="section-head">
      <p class="kicker">10 razões</p>
      <h2>O que a moeda social entrega para a cidade</h2>
    </div>
    <div class="grid grid-2">
      <?php
      $args = [
          ['repeat', 'A renda é gasta no município', 'Benefícios, auxílios e prêmios pagos em moeda social só podem ser usados no comércio credenciado do município. A primeira compra é 100% local, e as seguintes também tendem a ser quando o comerciante paga fornecedores locais com o saldo.'],
          ['store', 'Mais vendas para o pequeno comércio', 'Padarias, mercadinhos, farmácias e MEIs passam a receber um fluxo previsível de clientes. Em Barcelona, 95,3% dos comerciantes que aceitaram a moeda local viram aumento de clientes; na Coreia, estabelecimentos credenciados faturaram de 2,9% a 3,4% a mais.'],
          ['users', 'Emprego e renda', 'Mais vendas locais sustentam postos de trabalho no comércio e nos serviços. Em Maricá, um estudo estimou emprego formal cerca de 20% acima do esperado até 2019 — e o NBER mediu +9% de renda domiciliar entre beneficiários.'],
          ['trending', 'Proteção da base tributária', 'Com o IBS cobrado no destino, o local onde o morador compra define cada vez mais a receita do município até 2077. Manter o consumo no município é estratégia fiscal de longo prazo e, enquanto ISS e ICMS existirem, aumenta também o valor adicionado local.'],
          ['search', 'Transparência completa', 'Em uma moeda digital, cada transação fica registrada: quem recebeu, onde gastou, em que bairro e setor. Isso facilita a prestação de contas ao Tribunal de Contas, reduz fraudes e permite publicar resultados com dados reais.'],
          ['target', 'Políticas sociais com mais resultado', 'O mesmo orçamento social cumpre dois objetivos: proteger famílias e aumentar as vendas no comércio local. É possível definir categorias de compra (alimentos, medicamentos, material escolar) e validade do saldo.'],
          ['factory', 'Atração e fortalecimento de empresas', 'Empresas instaladas no município passam a ter mais demanda local; fornecedores locais passam a vender para o comércio que recebe a moeda. Empresas também podem pagar prêmios e benefícios em moeda social, com impacto ESG mensurável.'],
          ['phone', 'Inclusão financeira e digital', 'Famílias sem conta bancária passam a ter uma carteira digital no celular; comerciantes recebem por QR Code, sem máquina de cartão.'],
          ['leaf', 'Economia circular', 'A moeda social pode pagar pela entrega de recicláveis, óleo e serviços ambientais. Quem recicla recebe renda, e essa renda é gasta no comércio do bairro.'],
          ['landmark', 'Legado institucional', 'Uma política pública mensurável e inovadora, que coloca o município entre as referências nacionais em desenvolvimento local.'],
      ];
      foreach ($args as $i => [$ic, $t, $d]): ?>
        <div class="card reveal"><div class="card-icon"><?= icon($ic) ?></div><span class="card-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="wrap two-col">
    <div>
      <p class="kicker">Arrecadação e Reforma Tributária</p>
      <h2>O local onde o morador compra passou a definir parte da receita do município</h2>
      <p>Pela Emenda Constitucional 132/2023 e pela Lei Complementar 227/2026, de 2029 a 2077 parte do IBS de cada município é retida e repartida pela <strong>média de ISS e cota-parte de ICMS de 2019 a 2026</strong>. A retenção é de 80% até 2032 e 90% em 2033, caindo 1/45 por ano até 2077. A parte não retida — que só cresce — vai para o <strong>local do consumo</strong>.</p>
      <p>Na prática: <strong>cada compra feita em outra cidade é receita futura que vai para outro município</strong>. A moeda social é a ferramenta mais direta para manter o consumo e a base tributária no próprio município.</p>
      <a class="btn btn-ghost" href="/estudos/reforma-tributaria-e-municipios">Entender a regra em detalhe</a>
    </div>
    <div class="card">
      <h3>Como a moeda social atua sobre a receita</h3>
      <ul class="check-list">
        <li><strong>ISS</strong> — serviços prestados e consumidos no município (até 2032)</li>
        <li><strong>Cota-parte de ICMS</strong> — mais valor adicionado no comércio local (até 2032)</li>
        <li><strong>IBS no destino</strong> — consumo local vira receita local (2029 em diante, crescente)</li>
        <li><strong>Formalização</strong> — credenciamento exige CNPJ ou MEI</li>
        <li><strong>Dados</strong> — consumo por bairro e setor para planejamento fiscal</li>
      </ul>
      <p class="muted" style="font-size:13px;margin-top:14px">Ainda não há estudo causal que comprove aumento de arrecadação por moeda social; por isso recomendamos medir desde o primeiro dia.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head center">
      <p class="kicker">O modelo que funciona</p>
      <h2>O que separa os programas que prosperam dos que param</h2>
      <p>A experiência brasileira mostra que os problemas vieram da forma de execução, e não do conceito. Compare:</p>
    </div>
    <div class="pros-cons">
      <div class="card card-ok">
        <h3><span class="h-icon ok"><?= icon('check-circle') ?></span> Modelo recomendado</h3>
        <ul>
          <li>Lei municipal específica, com tarifas (se houver) previstas em lei</li>
          <li>Lastro 1:1 em <strong>conta do próprio município</strong>, com conciliação diária</li>
          <li>Fornecedor de tecnologia <strong>sem custódia</strong> de recursos</li>
          <li>Arranjo fechado, restrito ao comércio credenciado</li>
          <li>100% digital, com QR Code, sem cartão nem máquina de cartão</li>
          <li>Trilha de auditoria e relatórios prontos para o TCE</li>
          <li>Portabilidade de dados e saldos garantida em contrato</li>
          <li>Sem custo fixo de tecnologia para a prefeitura</li>
        </ul>
      </div>
      <div class="card card-warn">
        <h3><span class="h-icon warn"><?= icon('alert') ?></span> O que levou programas a serem questionados ou suspensos</h3>
        <ul>
          <li>Lastro misturado na conta do operador (TCE-RJ, Maricá)</li>
          <li>Rendimentos do dinheiro público apropriados</li>
          <li>Tarifas cobradas sem previsão legal</li>
          <li>Contratações emergenciais sucessivas</li>
          <li>Dependência de um fornecedor dono do código e dos dados</li>
          <li>Cartões e máquinas de cartão com custo alto e pouco uso entre empresas</li>
          <li>Cadastro sem cruzamentos (falecidos recebendo)</li>
          <li>Programa criado ou ampliado perto de eleições</li>
        </ul>
      </div>
    </div>
    <p class="center mt-2"><a href="/estudos/cases-brasil">Ver os cases brasileiros em detalhe →</a></p>
  </div>
</section>

<section class="section section-cream">
  <div class="wrap">
    <div class="section-head center">
      <p class="kicker">Benefícios por público</p>
      <h2>O que cada público ganha</h2>
    </div>
    <div class="grid grid-4">
      <div class="card"><div class="card-icon"><?= icon('landmark') ?></div><h3>Prefeito(a)</h3><p>Política pública mensurável, transparência de ponta a ponta e legado de inovação.</p></div>
      <div class="card"><div class="card-icon"><?= icon('briefcase') ?></div><h3>Secretaria de Fazenda</h3><p>Proteção da base tributária na transição para o IBS e dados de consumo local.</p></div>
      <div class="card"><div class="card-icon"><?= icon('store') ?></div><h3>Comerciante</h3><p>Mais clientes, recebimento pelo celular, credenciamento simples e resgate na própria conta.</p></div>
      <div class="card"><div class="card-icon"><?= icon('users') ?></div><h3>Família</h3><p>Benefício digital seguro, compras no comércio do bairro e mais oferta perto de onde mora.</p></div>
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
        <h2>Descubra, em 3 minutos, o potencial do seu município</h2>
        <p>O diagnóstico gratuito estima o movimento econômico, empregos, arrecadação, prontidão e riscos — e gera um relatório em PDF para apresentar ao prefeito e à Câmara.</p>
      </div>
      <div class="actions">
        <a class="btn btn-light btn-lg" href="/diagnostico" data-ev="cta_click" data-ev-label="why-band-diagnostico">Fazer diagnóstico</a>
        <a class="btn btn-ghost-light btn-lg" href="/contato">Falar com especialistas</a>
      </div>
    </div>
    <ol class="muted" style="font-size:12.5px;margin-top:24px;padding-left:1.2em">
      <li><a href="https://eaesp.fgv.br/blog/administracao-de-empresas/como-transferencia-de-renda-fortalece-economia-local-mas-amplia" target="_blank" rel="noopener">FGV EAESP (2026)</a></li>
      <li><a href="https://www.nber.org/papers/w33089" target="_blank" rel="noopener">NBER w33089 (2024)</a></li>
      <li><a href="https://cidadedeniteroi.com/economia/pagamento-da-moeda-arariboia-de-niteroi-e-antecipado/" target="_blank" rel="noopener">Cidade de Niterói (30/03/2026)</a></li>
      <li><a href="https://vademecumprevidenciario.com.br/legislacao/nivel/lec_00002272026/-titulo-iii-da-distribuicao-do-produto-da-arrecadacao-do-ibs" target="_blank" rel="noopener">LC 227/2026, art. 115, §2º, I</a> e <a href="https://www.planalto.gov.br/ccivil_03/constituicao/emendas/emc/emc132.htm" target="_blank" rel="noopener">EC 132/2023, ADCT art. 131</a></li>
    </ol>
  </div>
</section>
