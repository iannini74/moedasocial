/* Diagnóstico de Moeda Social — Portal Moeda Social © TribeX
 * Modelo transparente e conservador. Todas as premissas são exibidas no relatório. */
(function () {
  'use strict';
  var form = document.getElementById('diag-form');
  if (!form) return;
  var reportWrap = document.getElementById('relatorio');
  var report = document.getElementById('report');
  var msg = document.getElementById('diag-msg');

  var P = {
    receitaPerCapita: 5000,   // receita corrente média municipal por habitante (ordem de grandeza)
    icmsPerCapita: 550,
    familiaPorHab: 0.17,      // famílias no CadÚnico por habitante (média nacional aproximada)
    pessoasPorFamilia: 2.8,   // Censo 2022: média de moradores por domicílio
    habPorComercio: 40,
    publicoAlvo: 0.35,        // parcela do CadÚnico sugerida como público inicial
    tetoRecirculacao: 0.9,
    partServicos: 0.25, aliqIss: 0.03,
    partBens: 0.75, cargaIcms: 0.12, cotaMunicipal: 0.25, criterioVaf: 0.65,
    aliqIbsMunicipal: 0.025,
    receitaPorEmprego: 300000 // receita anual do varejo por pessoa ocupada (ordem de grandeza, IBGE/PAC)
  };

  function issPerCapita(pop) { return pop < 20000 ? 120 : pop < 100000 ? 250 : pop < 500000 ? 450 : 800; }
  function vazamentoPadrao(pop) { return pop < 20000 ? 55 : pop < 50000 ? 48 : pop < 100000 ? 42 : pop < 500000 ? 35 : 25; }

  function num(name) { var v = parseFloat(String(form[name].value).replace(',', '.')); return isNaN(v) ? null : v; }
  function brl(v, dec) { return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: dec || 0, maximumFractionDigits: dec || 0 }); }
  function brlShort(v) {
    var a = Math.abs(v);
    if (a >= 1e9) return 'R$ ' + (v / 1e9).toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + ' bi';
    if (a >= 1e6) return 'R$ ' + (v / 1e6).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mi';
    if (a >= 1e3) return 'R$ ' + (v / 1e3).toLocaleString('pt-BR', { maximumFractionDigits: 0 }) + ' mil';
    return brl(v);
  }
  function int(v) { return Math.round(v).toLocaleString('pt-BR'); }
  function pct(v, d) { return (v * 100).toLocaleString('pt-BR', { maximumFractionDigits: d === undefined ? 1 : d }) + '%'; }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  /* Preenche estimativas automáticas conforme a população */
  var touched = {};
  form.querySelectorAll('[data-auto]').forEach(function (el) { el.addEventListener('input', function () { touched[el.name] = true; }); });
  form.populacao.addEventListener('input', function () {
    var pop = num('populacao');
    if (!pop || pop < 1000) return;
    var est = {
      receita: Math.round(pop * P.receitaPerCapita / 1000) * 1000,
      iss: Math.round(pop * issPerCapita(pop) / 1000) * 1000,
      icms: Math.round(pop * P.icmsPerCapita / 1000) * 1000,
      cadunico: Math.round(pop * P.familiaPorHab),
      comercios: Math.round(pop / P.habPorComercio),
      vazamento: vazamentoPadrao(pop)
    };
    est.familias = Math.round(est.cadunico * P.publicoAlvo / 10) * 10;
    Object.keys(est).forEach(function (k) { if (!touched[k]) form[k].value = est[k]; });
  });
  var started = false;
  form.addEventListener('focusin', function () { if (!started) { started = true; window.msTrack && window.msTrack('simulator_start', 'diagnostico'); } });

  function calc() {
    var pop = num('populacao');
    var d = {
      municipio: form.municipio.value.trim(), uf: form.uf.value, populacao: pop,
      receita: num('receita') || pop * P.receitaPerCapita,
      iss: num('iss') || pop * issPerCapita(pop),
      icms: num('icms') || pop * P.icmsPerCapita,
      cadunico: num('cadunico') || Math.round(pop * P.familiaPorHab),
      comercios: num('comercios') || Math.round(pop / P.habPorComercio),
      familias: num('familias'), valor: num('valor') || 0, outros: num('outros') || 0,
      fonte: form.fonte.value,
      vazamento: Math.min(90, Math.max(5, num('vazamento') || vazamentoPadrao(pop))),
      adesao: Math.min(100, Math.max(5, num('adesao') || 30))
    };
    if (d.familias === null) d.familias = Math.round(d.cadunico * P.publicoAlvo);
    var checks = ['lei', 'orcamento', 'programa', 'cadastro', 'comercio', 'conectividade', 'equipe', 'controle'];
    var weights = { lei: 20, orcamento: 20, programa: 10, cadastro: 10, comercio: 15, conectividade: 10, equipe: 10, controle: 5 };
    d.prontidao = {};
    var score = 0;
    checks.forEach(function (k) { d.prontidao[k] = form[k].checked; if (form[k].checked) score += weights[k]; });

    var I = (d.familias * d.valor + d.outros) * 12;
    var r0 = 1 - d.vazamento / 100;
    var rc = r0 >= P.tetoRecirculacao ? r0 : r0 + (P.tetoRecirculacao - r0) * (d.adesao / 100);
    var conv = [I * r0, I * r0 * r0, I * r0 * r0 * r0];
    var ms = [I, I * rc, I * rc * rc];
    var Mc = conv[0] + conv[1] + conv[2], Mm = ms[0] + ms[1] + ms[2];
    var delta = Mm - Mc;
    var issAdd = delta * P.partServicos * P.aliqIss;
    var icmsAdd = delta * P.partBens * P.cargaIcms * P.cotaMunicipal * P.criterioVaf;
    var ibsAdd = delta * P.aliqIbsMunicipal;
    var cred = Math.max(1, Math.round(d.comercios * d.adesao / 100));
    return {
      d: d, score: score, I: I, r0: r0, rc: rc, conv: conv, ms: ms, Mc: Mc, Mm: Mm, delta: delta,
      lm3c: I ? Mc / I : 0, lm3m: I ? Mm / I : 0,
      issAdd: issAdd, icmsAdd: icmsAdd, ibsAdd: ibsAdd,
      empregos: Mm / P.receitaPorEmprego, empregosAdd: delta / P.receitaPorEmprego,
      credenciados: cred, porComercio: I / cred,
      pessoas: d.familias * P.pessoasPorFamilia, alcance: (d.familias * P.pessoasPorFamilia) / pop,
      pesoOrcamento: d.receita ? I / d.receita : 0,
      pesoIss: d.iss ? (issAdd / d.iss) : 0
    };
  }

  var nivel = function (s) {
    if (s >= 75) return { t: 'Pronto para implantar', c: 'ok', e: '🟢' };
    if (s >= 45) return { t: 'Quase lá: ajustes pontuais', c: 'mid', e: '🟡' };
    return { t: 'Em preparação: comece pela base', c: 'low', e: '🟠' };
  };

  var recs = {
    lei: ['Aprovar a lei municipal do programa', 'Defina por lei: objetivos, público, valor, paridade 1:1 com o real, lastro integral, circulação restrita a comércios credenciados, regras de resgate, governança e avaliação. É a base da segurança jurídica perante o Tribunal de Contas e o Ministério Público.'],
    orcamento: ['Garantir a dotação orçamentária', 'Inclua o programa no PPA, na LDO e na LOA, com ação orçamentária própria, e indique a fonte. Despesa obrigatória de caráter continuado exige estimativa de impacto e compensação (LRF, arts. 16 e 17).'],
    programa: ['Aproveitar ou criar um programa de transferência', 'Converter auxílios já existentes (alimentação, aluguel social, bolsas) para moeda social é o caminho mais rápido: o recurso já existe e passa a girar no comércio local.'],
    cadastro: ['Qualificar o cadastro de beneficiários', 'Use o CadÚnico como base, com critérios objetivos de elegibilidade e rotinas de revisão. Isso evita questionamentos sobre impessoalidade.'],
    comercio: ['Mobilizar o comércio local', 'Envolva associação comercial, CDL e Sala do Empreendedor desde o início. Credenciamento gratuito e recebimento pelo celular, sem maquininha, aceleram a adesão.'],
    conectividade: ['Planejar a inclusão digital', 'Onde a internet móvel é fraca, prefira soluções com QR Code leve, pontos de atendimento e suporte presencial nos primeiros meses.'],
    equipe: ['Formar a equipe gestora', 'Designe um comitê gestor (Fazenda, Assistência Social, Desenvolvimento Econômico) com metas e painel de indicadores.'],
    controle: ['Estruturar controle e transparência', 'Publique dados agregados do programa, relatórios periódicos e trilha de auditoria. Plataformas digitais com rastreabilidade de ponta a ponta simplificam a prestação de contas.']
  };
  var fortes = {
    lei: 'base legal encaminhada', orcamento: 'recursos previstos no orçamento', programa: 'programa de transferência existente',
    cadastro: 'cadastro de beneficiários qualificado', comercio: 'comércio local mobilizado', conectividade: 'boa conectividade móvel',
    equipe: 'equipe técnica disponível', controle: 'controle e transparência estruturados'
  };
  var fontes = {
    proprios: 'Recursos próprios dão autonomia, mas exigem disciplina fiscal: dimensione o programa para caber no orçamento mesmo em anos de receita menor.',
    royalties: 'Royalties são uma fonte poderosa (Maricá e Niterói usam esse modelo), porém voláteis e finitos. Recomenda-se um fundo de estabilização e regras de transição na lei.',
    transferencias: 'Redirecionar programas existentes para a moeda social tem baixo impacto fiscal e alto efeito local: o mesmo real passa a circular dentro da cidade.',
    privado: 'Empresas e fundações podem financiar benefícios, prêmios e incentivos em moeda social (ESG), sempre com instrumento jurídico adequado (termo de fomento, doação ou convênio).',
    misto: 'Combinar fontes reduz a dependência de uma só receita e dá estabilidade ao programa ao longo das gestões.'
  };

  function render(r) {
    var d = r.d, n = nivel(r.score);
    var today = new Date();
    var dataStr = today.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
    var maxBar = Math.max.apply(null, r.ms.concat(r.conv)) || 1;
    var bar = function (v, cls) { return '<span class="bar ' + cls + '" style="width:' + Math.max(2, v / maxBar * 100) + '%"></span>'; };
    var mes = function (add) { var x = new Date(today.getFullYear(), today.getMonth() + add, 1); return x.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' }); };

    var riscos = [];
    if (r.pesoOrcamento > 0.03) riscos.push(['Peso orçamentário elevado (' + pct(r.pesoOrcamento) + ' da receita corrente)', 'Considere implantação escalonada (piloto por bairros ou públicos prioritários) e fonte de financiamento estável antes de ampliar.']);
    if (d.adesao < 25) riscos.push(['Baixa adesão do comércio', 'Com poucos comércios credenciados, a moeda perde utilidade e a recirculação cai. Meta mínima sugerida: 25–30% dos estabelecimentos, cobrindo todos os bairros.']);
    if (d.fonte === 'royalties') riscos.push(['Dependência de royalties', 'Receitas de petróleo oscilam com preço e produção. Crie fundo de reserva e regra de ajuste automático do benefício.']);
    riscos.push(['Calendário eleitoral (2028)', 'A Lei 9.504/1997 (art. 73, §10) veda distribuição gratuita de bens e valores em ano eleitoral, salvo programas autorizados em lei e já em execução orçamentária no ano anterior. Para operar com tranquilidade em 2028, o programa deve estar em lei e em execução ao longo de 2027.']);
    riscos.push(['Descontinuidade entre gestões', 'Programas criados só por decreto ficam vulneráveis. Lei própria, conselho gestor com participação social e avaliação de impacto publicada protegem a política.']);
    riscos.push(['Enquadramento regulatório', 'A moeda deve ser instrumento de pagamento de circulação restrita, com paridade 1:1 e lastro integral em reais. Evite custódia de recursos por terceiros e circulação fora da rede credenciada, o que poderia atrair exigências do Banco Central.']);
    riscos.push(['Contratação da tecnologia', 'Escolha o instrumento jurídico adequado (licitação, credenciamento, chamamento ou parceria com OSC) e documente a vantajosidade. Prefira modelos sem custo fixo de tecnologia para o município.']);

    var faltantes = Object.keys(recs).filter(function (k) { return !d.prontidao[k]; });
    var feitos = Object.keys(recs).filter(function (k) { return d.prontidao[k]; });

    var html = '' +
    '<header class="rep-head">' +
      '<div class="rep-brand"><img src="/assets/img/logo-192.png" alt="" width="44" height="44"><div><strong>moeda<span>social</span></strong><small>Diagnóstico gratuito · moedasocial.com</small></div></div>' +
      '<div class="rep-id"><span>Protocolo <b id="rep-protocol">—</b></span><span>' + esc(dataStr) + '</span></div>' +
    '</header>' +
    '<div class="rep-title"><p class="kicker">Diagnóstico de moeda social</p><h2>' + esc(d.municipio) + ' / ' + esc(d.uf) + '</h2>' +
      '<p>' + int(d.populacao) + ' habitantes · ' + int(d.familias) + ' famílias beneficiadas · ' + brl(d.valor) + '/mês por família' + (d.outros ? ' · ' + brlShort(d.outros) + '/mês em outros pagamentos' : '') + '</p></div>' +

    '<section class="rep-sec"><h3>1. Resumo executivo</h3><div class="rep-kpis">' +
      kpi(brlShort(r.I), 'injetados por ano no comércio local', 'Valor do programa em moeda social') +
      kpi(brlShort(r.Mm), 'de movimento econômico local', 'Multiplicador LM3 de ' + r.lm3m.toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + '×') +
      kpi('+' + brlShort(r.delta), 'a mais na economia da cidade', 'em relação ao mesmo valor pago em dinheiro ou cartão comum') +
      kpi(int(r.empregos), 'postos de trabalho sustentados', '+' + int(r.empregosAdd) + ' em relação ao pagamento convencional') +
      kpi(int(r.credenciados), 'comércios credenciados', 'média de ' + brlShort(r.porComercio) + '/ano em vendas novas por loja') +
      kpi(pct(r.alcance, 0), 'da população alcançada', int(r.pessoas) + ' pessoas em ' + int(d.familias) + ' famílias') +
      kpi(brlShort(r.issAdd + r.icmsAdd), 'de arrecadação adicional/ano', 'ISS + cota-parte de ICMS (estimativa conservadora)') +
      kpi(pct(r.pesoOrcamento), 'da receita corrente', 'peso do programa no orçamento municipal') +
    '</div>' +
    '<p class="rep-note">Leitura: cada <b>R$ 1</b> distribuído em moeda social gera cerca de <b>' + brl(r.lm3m, 2) + '</b> em atividade econômica local nas três primeiras rodadas de gasto, contra <b>' + brl(r.lm3c, 2) + '</b> quando o mesmo valor é pago em dinheiro ou cartão de uso livre — porque hoje cerca de <b>' + d.vazamento + '%</b> do gasto escapa do município.</p></section>' +

    '<section class="rep-sec"><h3>2. Para onde vai o dinheiro: rodadas de gasto</h3>' +
      '<div class="rep-bars">' +
        ['1ª compra', '2ª rodada', '3ª rodada'].map(function (lbl, i) {
          return '<div class="rep-bar-row"><span class="lbl">' + lbl + '</span><div class="bars">' + bar(r.ms[i], 'ms') + '<em>' + brlShort(r.ms[i]) + '</em></div><div class="bars">' + bar(r.conv[i], 'cv') + '<em>' + brlShort(r.conv[i]) + '</em></div></div>';
        }).join('') +
      '</div><p class="rep-legend"><span class="sw ms"></span> Moeda social (1ª compra 100% local; recirculação de ' + pct(r.rc, 0) + ')  <span class="sw cv"></span> Pagamento convencional (recirculação local de ' + pct(r.r0, 0) + ')</p></section>' +

    '<section class="rep-sec"><h3>3. Impacto fiscal</h3><div class="rep-table"><table>' +
      '<tr><th>Tributo</th><th>Hoje (informado/estimado)</th><th>Adicional estimado/ano</th><th>Observação</th></tr>' +
      '<tr><td>ISS</td><td>' + brlShort(d.iss) + '</td><td>+' + brlShort(r.issAdd) + '</td><td>' + pct(r.pesoIss, 2) + ' a mais sobre o ISS atual</td></tr>' +
      '<tr><td>Cota-parte de ICMS</td><td>' + brlShort(d.icms) + '</td><td>+' + brlShort(r.icmsAdd) + '</td><td>Via Valor Adicionado Fiscal, com defasagem de 2 anos</td></tr>' +
      '<tr><td>IBS municipal (pós-transição)</td><td>—</td><td>+' + brlShort(r.ibsAdd) + '</td><td>Com o IBS cobrado no destino, consumo local vira receita local</td></tr>' +
    '</table></div>' +
    '<p class="rep-note"><b>Reforma Tributária:</b> na transição para o IBS (EC 132/2023 e LC 214/2025), a arrecadação passa a pertencer ao local de consumo. Municípios que retêm o consumo dos seus moradores protegem a própria base tributária nas próximas décadas. Além disso, o movimento adicional fortalece a formalização de pequenos negócios que passam a vender para o programa.</p></section>' +

    '<section class="rep-sec rep-break"><h3>4. Prontidão do município: <span class="score score-' + n.c + '">' + r.score + '/100 · ' + n.e + ' ' + n.t + '</span></h3>' +
      '<div class="rep-meter"><span style="width:' + r.score + '%"></span></div>' +
      (feitos.length ? '<p><b>Pontos fortes:</b> ' + feitos.map(function (k) { return fortes[k]; }).join(' · ').replace(/^./, function (c) { return c.toUpperCase(); }) + '.</p>' : '') +
      (faltantes.length ? '<div class="rep-recs">' + faltantes.map(function (k) { return '<div><b>' + recs[k][0] + '</b><p>' + recs[k][1] + '</p></div>'; }).join('') + '</div>' : '<p>Todos os pré-requisitos foram marcados. O município pode avançar diretamente para a contratação da tecnologia e o credenciamento do comércio.</p>') +
      '<p class="rep-note"><b>Fonte de recursos:</b> ' + fontes[d.fonte] + '</p></section>' +

    '<section class="rep-sec"><h3>5. Modelo recomendado</h3><ul class="rep-check">' +
      '<li><b>Moeda 100% digital</b>, com aplicativo e pagamento por QR Code — sem cartão físico e sem maquininha para o comércio.</li>' +
      '<li><b>Paridade 1:1 com o real e lastro integral</b>, mantido em conta do próprio município em banco parceiro, sem custódia por terceiros.</li>' +
      '<li><b>Arranjo fechado de circulação restrita</b>: aceita apenas por comércios credenciados do município, com resgate em reais direto na conta do lojista.</li>' +
      '<li><b>Rastreabilidade de ponta a ponta</b>, trilha de auditoria e relatórios prontos para o Tribunal de Contas, a LRF e a transparência ativa.</li>' +
      '<li><b>Regras parametrizáveis</b>: públicos, limites, categorias de compra e validade do saldo para estimular a circulação.</li>' +
      '<li><b>Credenciamento gratuito do comércio</b> e <b>nenhum custo fixo de tecnologia</b> para a prefeitura, com remuneração transparente por transação.</li>' +
      '<li><b>Painel de impacto</b> por bairro e setor: volume retido, comércios ativos, frequência de uso e vazamento evitado.</li>' +
    '</ul></section>' +

    '<section class="rep-sec rep-break"><h3>6. Riscos e como mitigá-los</h3><div class="rep-recs">' +
      riscos.map(function (x) { return '<div><b>⚠️ ' + x[0] + '</b><p>' + x[1] + '</p></div>'; }).join('') + '</div></section>' +

    '<section class="rep-sec"><h3>7. Roteiro de implantação sugerido</h3><ol class="rep-timeline">' +
      '<li><span>' + mes(0) + '</span><b>Diagnóstico e desenho</b><p>Validar dados, definir público, valor, fonte e metas; apresentar ao prefeito e à Câmara.</p></li>' +
      '<li><span>' + mes(1) + '</span><b>Base legal</b><p>Projeto de lei, decreto regulamentador, dotação orçamentária e conta específica para o lastro.</p></li>' +
      '<li><span>' + mes(2) + '</span><b>Tecnologia e contratação</b><p>Seleção da plataforma, parametrização das regras, integração com o CadÚnico e com sistemas de PDV.</p></li>' +
      '<li><span>' + mes(3) + '</span><b>Credenciamento e cadastro</b><p>Mutirões com o comércio por bairro; cadastro e orientação das famílias.</p></li>' +
      '<li><span>' + mes(4) + '</span><b>Lançamento piloto</b><p>Início com público prioritário, suporte presencial e comunicação local.</p></li>' +
      '<li><span>' + mes(6) + '</span><b>Escala e avaliação</b><p>Ampliação gradual, painel público de resultados e primeira avaliação de impacto.</p></li>' +
    '</ol></section>' +

    '<section class="rep-sec rep-method"><h3>8. Premissas e metodologia</h3><ul>' +
      '<li>Multiplicador local em 3 rodadas (LM3), conforme metodologia da New Economics Foundation. Recirculação convencional = 100% − gasto fora do município (' + d.vazamento + '%). Na moeda social, a 1ª compra é 100% local e a recirculação sobe com a adesão do comércio (' + d.adesao + '%), até o teto de 90%.</li>' +
      '<li>Arrecadação: serviços = 25% do movimento adicional com ISS médio de 3%; bens = 75% com carga efetiva de ICMS de 12%, cota municipal de 25% e critério de VAF de 65%; IBS municipal ilustrativo de 2,5% (a alíquota de referência será fixada em resolução do Senado).</li>' +
      '<li>Empregos: movimento econômico ÷ R$ 300 mil de receita anual por pessoa ocupada no varejo (ordem de grandeza da Pesquisa Anual de Comércio/IBGE).</li>' +
      '<li>Valores não informados foram estimados por média per capita. Os resultados são <b>ilustrativos</b>, não constituem promessa de resultado e devem ser refinados com dados oficiais do município.</li>' +
    '</ul></section>' +

    '<aside class="rep-ad" aria-label="Anúncio"><span class="ad-label">Anúncio</span>' +
      '<div><img src="/assets/img/moeda-nobre-192.png" alt="Moeda Nobre" width="56" height="56"></div>' +
      '<div><strong>Transforme este diagnóstico em projeto com a Moeda Nobre.</strong>' +
      '<p>Moeda social digital municipal: app com QR Code, sem maquininha, lastro 1:1 em conta da prefeitura, rastreabilidade total para o Tribunal de Contas e sem custo de tecnologia para o município.</p>' +
      '<p class="rep-ad-url">moedanobre.com · contato@moedanobre.com</p></div>' +
      '<a class="btn btn-light" href="https://moedanobre.com/?utm_source=moedasocial.com&utm_medium=diagnostico&utm_campaign=portal&utm_content=relatorio" target="_blank" rel="sponsored noopener" data-ad-click="diagnostico-relatorio">Agendar demonstração</a>' +
    '</aside>' +
    '<footer class="rep-foot">Relatório gerado gratuitamente em <b>moedasocial.com/diagnostico</b> · Portal Moeda Social · © ' + today.getFullYear() + ' TribeX (tribex.com.br). Reprodução permitida com citação da fonte.</footer>';

    report.innerHTML = html;
    function kpi(v, l, s) { return '<div class="rep-kpi"><strong>' + v + '</strong><span>' + l + '</span><small>' + s + '</small></div>'; }
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var miss = ['municipio', 'uf', 'populacao'].filter(function (k) { return !form[k].value.trim(); });
    if (miss.length || num('populacao') < 1000) {
      msg.className = 'form-msg bad';
      msg.textContent = 'Informe município, UF e população (mínimo de 1.000 habitantes).';
      form[miss[0] || 'populacao'].focus();
      return;
    }
    msg.textContent = '';
    var r = calc();
    if (!r.I) { msg.className = 'form-msg bad'; msg.textContent = 'Informe o número de famílias e o valor mensal do benefício.'; return; }
    render(r);
    reportWrap.hidden = false;
    reportWrap.scrollIntoView({ behavior: 'smooth' });
    var contact = document.getElementById('btn-contact');
    contact.href = '/contato?municipio=' + encodeURIComponent(r.d.municipio);
    window.msTrack && window.msTrack('simulator_run', r.d.municipio + '/' + r.d.uf, Math.round(r.I));
    var inputs = {};
    Object.keys(r.d).forEach(function (k) { if (typeof r.d[k] !== 'object') inputs[k] = r.d[k]; });
    Object.keys(r.d.prontidao).forEach(function (k) { inputs['p_' + k] = r.d.prontidao[k] ? 1 : 0; });
    fetch('/api/diagnostico', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ inputs: inputs, score: r.score, results: { injecao: Math.round(r.I), movimento: Math.round(r.Mm), delta: Math.round(r.delta), empregos: Math.round(r.empregos), tributos: Math.round(r.issAdd + r.icmsAdd) } })
    }).then(function (x) { return x.json(); }).then(function (j) {
      if (j && j.id) document.getElementById('rep-protocol').textContent = j.id;
    }).catch(function () {});
  });

  document.getElementById('btn-print').addEventListener('click', function () {
    window.msTrack && window.msTrack('simulator_print', form.municipio.value);
    window.print();
  });
  document.getElementById('btn-edit').addEventListener('click', function () { form.scrollIntoView({ behavior: 'smooth' }); });
  document.getElementById('btn-pdf').addEventListener('click', function () {
    var name = 'diagnostico-moeda-social-' + (form.municipio.value || 'municipio').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-') + '.pdf';
    window.msTrack && window.msTrack('simulator_pdf', form.municipio.value);
    if (!window.html2pdf) { window.print(); return; }
    var btn = this; btn.disabled = true; btn.textContent = 'Gerando PDF…';
    report.classList.add('pdf-mode');
    window.html2pdf().set({
      margin: [10, 10, 12, 10], filename: name,
      image: { type: 'jpeg', quality: 0.95 },
      html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
      pagebreak: { mode: ['css', 'legacy'], before: '.rep-break', avoid: ['.rep-kpi', '.rep-recs > div', '.rep-ad', 'tr', '.rep-timeline li'] }
    }).from(report).save().then(function () {
      report.classList.remove('pdf-mode'); btn.disabled = false; btn.textContent = '⬇️ Baixar PDF';
    });
  });
})();
