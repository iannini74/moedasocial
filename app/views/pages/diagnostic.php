<?php $scripts = ['https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js', asset('js/diagnostico.js')]; ?>
<section class="page-hero no-print">
  <div class="wrap">
    <?php partial('crumbs', ['items' => ['Diagnóstico gratuito' => null]]) ?>
    <p class="kicker">Ferramenta gratuita para prefeituras</p>
    <h1>Diagnóstico de moeda social para o seu município</h1>
    <p class="lead">Preencha os dados abaixo — muitos já vêm estimados pela população e podem ser ajustados com números da sua Secretaria de Fazenda. Em segundos você recebe o impacto estimado, a nota de prontidão, os riscos e o roteiro de implantação, pronto para imprimir ou salvar em PDF.</p>
    <div class="meta-line"><span>⏱️ 3 minutos</span><span>🔒 Não pedimos dados pessoais</span><span>🖨️ Relatório em PDF</span></div>
  </div>
</section>

<section class="section no-print" style="padding-top:40px">
  <div class="wrap two-col two-col-wide">
    <form id="diag-form" class="card" style="padding:clamp(20px,4vw,32px)" novalidate>
      <fieldset>
        <legend>1. O município</legend>
        <div class="form-grid">
          <label class="field"><span>Município *</span><input type="text" name="municipio" required placeholder="Ex.: Itabirito"></label>
          <label class="field"><span>UF *</span><select name="uf" required><option value="">UF</option><?php foreach (ufs() as $u): ?><option><?= $u ?></option><?php endforeach; ?></select></label>
          <label class="field"><span>População (habitantes) *</span><input type="number" name="populacao" min="1000" step="1" required placeholder="Ex.: 52000" inputmode="numeric"><small>Dado do IBGE (Censo 2022 ou estimativa mais recente).</small></label>
          <label class="field"><span>Receita corrente anual (R$)</span><input type="number" name="receita" min="0" step="1000" data-auto placeholder="estimada automaticamente"><small>Consulte o RREO ou o Portal da Transparência.</small></label>
          <label class="field"><span>ISS arrecadado por ano (R$)</span><input type="number" name="iss" min="0" step="1000" data-auto placeholder="estimado"></label>
          <label class="field"><span>Cota-parte de ICMS por ano (R$)</span><input type="number" name="icms" min="0" step="1000" data-auto placeholder="estimada"></label>
          <label class="field"><span>Famílias no CadÚnico</span><input type="number" name="cadunico" min="0" step="1" data-auto placeholder="estimado"><small>Fonte: CECAD / MDS.</small></label>
          <label class="field"><span>Comércios e serviços locais</span><input type="number" name="comercios" min="0" step="1" data-auto placeholder="estimado"><small>Varejo, alimentação, farmácias, serviços e MEIs.</small></label>
        </div>
      </fieldset>

      <fieldset class="mt-3">
        <legend>2. O programa que vocês imaginam</legend>
        <div class="form-grid">
          <label class="field"><span>Famílias beneficiadas</span><input type="number" name="familias" min="0" step="1" data-auto placeholder="sugerido"></label>
          <label class="field"><span>Valor mensal por família (R$)</span><input type="number" name="valor" min="0" step="10" value="200"></label>
          <label class="field"><span>Outros pagamentos em moeda social (R$/mês)</span><input type="number" name="outros" min="0" step="100" value="0"><small>Ex.: auxílio-alimentação de servidores, bolsas, premiações, compras públicas.</small></label>
          <label class="field"><span>Principal fonte de recursos</span>
            <select name="fonte">
              <option value="proprios">Recursos próprios do Tesouro</option>
              <option value="royalties">Royalties / compensações financeiras</option>
              <option value="transferencias">Programas já existentes (redirecionamento)</option>
              <option value="privado">Parceria com empresas / fundações</option>
              <option value="misto">Misto</option>
            </select>
          </label>
          <label class="field"><span>Gasto feito hoje fora do município (%)</span><input type="number" name="vazamento" min="5" max="90" step="1" data-auto placeholder="estimado"><small>Quanto da renda das famílias é gasta em outras cidades ou on-line.</small></label>
          <label class="field"><span>Adesão esperada do comércio (%)</span><input type="number" name="adesao" min="5" max="100" step="5" value="30"></label>
        </div>
      </fieldset>

      <fieldset class="mt-3">
        <legend>3. Prontidão</legend>
        <p class="muted" style="font-size:14px;margin-top:-6px">Marque o que o município já tem.</p>
        <div class="stack">
          <?php foreach ([
              'lei' => 'Lei municipal do programa aprovada ou projeto em tramitação',
              'orcamento' => 'Recursos previstos na LOA / PPA para o programa',
              'programa' => 'Já existe programa de transferência de renda ou auxílio municipal',
              'cadastro' => 'CadÚnico atualizado e equipe de assistência social estruturada',
              'comercio' => 'Associação comercial, CDL ou Sala do Empreendedor engajadas',
              'conectividade' => 'Boa cobertura de internet móvel (4G/5G) na maioria dos bairros',
              'equipe' => 'Equipe técnica para gestão e monitoramento (Fazenda/Desenvolvimento)',
              'controle' => 'Controle interno e transparência ativa estruturados',
          ] as $k => $l): ?>
            <label class="check"><input type="checkbox" name="<?= $k ?>" value="1"> <span><?= e($l) ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <div class="mt-3" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
        <button class="btn btn-primary btn-lg" type="submit">Gerar diagnóstico</button>
        <span class="muted" style="font-size:13.5px">Estimativas ilustrativas, com premissas explicadas no relatório.</span>
      </div>
      <p class="form-msg" id="diag-msg" role="status" aria-live="polite"></p>
    </form>

    <aside class="aside">
      <div class="aside-box">
        <h3>Como calculamos</h3>
        <p>Usamos o conceito de <strong>multiplicador local em três rodadas (LM3)</strong>, da New Economics Foundation: quanto de cada real é gasto dentro do município na primeira compra, na segunda e na terceira. Na moeda social, a primeira rodada é 100% local e a recirculação cresce com a adesão do comércio.</p>
        <p style="margin:0">Arrecadação e empregos são ordens de grandeza com premissas conservadoras, exibidas no relatório. <a href="/estudos/finalidades-da-moeda-social">Entenda a metodologia</a>.</p>
      </div>
      <?php ad('diagnostico-lateral', 'card') ?>
    </aside>
  </div>
</section>

<section id="relatorio" class="section report-section" hidden>
  <div class="wrap">
    <div class="report-actions no-print">
      <button class="btn btn-primary" type="button" id="btn-pdf">⬇️ Baixar PDF</button>
      <button class="btn btn-ghost" type="button" id="btn-print">🖨️ Imprimir</button>
      <a class="btn btn-ghost" id="btn-contact" href="/contato">Falar com especialistas</a>
      <button class="btn btn-ghost" type="button" id="btn-edit">✏️ Ajustar dados</button>
    </div>
    <div id="report" class="report"></div>
  </div>
</section>
