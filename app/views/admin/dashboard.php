<?php
/** Painel de Analytics — gráficos em SVG/CSS, sem bibliotecas externas. */
$n = fn($v) => number_format((float) $v, 0, ',', '.');
$dur = function ($ms) { $s = (int) round(($ms ?? 0) / 1000); return $s >= 60 ? intdiv($s, 60) . 'min ' . str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT) . 's' : $s . 's'; };
$var = function ($cur, $prev) { if (!$prev) return ''; $d = ($cur - $prev) / $prev * 100; return '<span class="var ' . ($d >= 0 ? 'up' : 'down') . '">' . ($d >= 0 ? '▲' : '▼') . ' ' . number_format(abs($d), 0, ',', '.') . '%</span>'; };
$bounceRate = $bounce['total'] ? $bounce['single'] / $bounce['total'] * 100 : 0;
$pps = $kpi['sessions'] ? $kpi['pv'] / $kpi['sessions'] : 0;
$refLabels = ['direto' => 'Acesso direto', 'busca' => 'Buscadores', 'ia' => 'Assistentes de IA', 'social' => 'Redes sociais', 'referencia' => 'Outros sites', 'email' => 'E-mail / newsletter', 'campanha' => 'Campanhas (UTM)'];
$evLabels = ['ad_view' => 'Anúncios visualizados', 'ad_click' => 'Cliques em anúncios', 'cta_click' => 'Cliques em CTAs', 'outbound_click' => 'Cliques em links externos', 'newsletter_signup' => 'Inscrições na newsletter', 'contact_submit' => 'Contatos enviados', 'simulator_start' => 'Diagnóstico iniciado', 'simulator_run' => 'Diagnóstico gerado', 'simulator_pdf' => 'PDF baixado', 'simulator_print' => 'Relatório impresso', 'share' => 'Compartilhamentos', 'scroll_75' => 'Leituras de 75%+ da página'];
$dows = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

/** Lista com barras horizontais (magnitude, uma só cor). */
$bars = function (array $rows, string $labelKey, string $valKey, ?callable $fmtLabel = null, int $limit = 12) use ($n) {
    if (!$rows) return '<p class="adm-empty">Sem dados no período.</p>';
    $rows = array_slice($rows, 0, $limit);
    $max = max(array_map(fn($r) => (float) $r[$valKey], $rows)) ?: 1;
    $total = array_sum(array_map(fn($r) => (float) $r[$valKey], $rows)) ?: 1;
    $h = '<ul class="hbars">';
    foreach ($rows as $r) {
        $label = $fmtLabel ? $fmtLabel($r) : e((string) ($r[$labelKey] ?? '—'));
        $v = (float) $r[$valKey];
        $h .= '<li title="' . e(strip_tags($label)) . ': ' . $n($v) . '"><span class="hb-fill" style="width:' . round($v / $max * 100, 1) . '%"></span><span class="hb-label">' . $label . '</span><span class="hb-val">' . $n($v) . ' <small>' . number_format($v / $total * 100, 0) . '%</small></span></li>';
    }
    return $h . '</ul>';
};

// Série diária completa (preenche dias sem acesso com zero)
$byDay = [];
foreach ($daily as $d) $byDay[$d['day']] = $d;
$series = [];
for ($t = strtotime($from); $t <= strtotime($to); $t += 86400) {
    $k = date('Y-m-d', $t);
    $series[] = ['day' => $k, 'pv' => (int) ($byDay[$k]['pv'] ?? 0), 's' => (int) ($byDay[$k]['s'] ?? 0)];
}
$W = 900; $H = 240; $pl = 44; $pr = 70; $pt = 14; $pb = 28;
$maxY = max(1, max(array_column($series, 'pv')));
$step = $maxY <= 5 ? 1 : (int) pow(10, floor(log10($maxY))) / ($maxY / pow(10, floor(log10($maxY))) < 3 ? 2 : 1);
$maxY = (int) (ceil($maxY / $step) * $step);
$cnt = count($series);
$x = fn($i) => $pl + ($cnt > 1 ? $i / ($cnt - 1) : .5) * ($W - $pl - $pr);
$y = fn($v) => $pt + (1 - $v / $maxY) * ($H - $pt - $pb);
$path = function ($key) use ($series, $x, $y) { $d = ''; foreach ($series as $i => $p) $d .= ($i ? 'L' : 'M') . round($x($i), 1) . ' ' . round($y($p[$key]), 1); return $d; };

// Mapa de calor dia da semana × hora
$heatMap = []; $heatMax = 1;
foreach ($heat as $h) { $heatMap[(int) $h['dow']][(int) $h['hour']] = (int) $h['n']; $heatMax = max($heatMax, (int) $h['n']); }
$periods = ['hoje' => 'Hoje', 'ontem' => 'Ontem', '7d' => '7 dias', '30d' => '30 dias', '90d' => '90 dias', '12m' => '12 meses', 'tudo' => 'Tudo'];
?>
<div class="adm-head">
  <div>
    <h1>Analytics</h1>
    <p class="muted"><?= date_br($from) ?> a <?= date_br($to) ?> · <span class="live"><span class="dot"></span><?= $realtimeCount ?> pessoa(s) no site agora</span></p>
  </div>
  <form class="adm-filter" method="get">
    <div class="seg">
      <?php foreach ($periods as $k => $l): ?><a href="?p=<?= $k ?>" class="<?= $p === $k ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?>
    </div>
    <label>De <input type="date" name="de" value="<?= e($from) ?>"></label>
    <label>Até <input type="date" name="ate" value="<?= e($to) ?>"></label>
    <button class="btn btn-sm btn-ghost">Aplicar</button>
    <a class="btn btn-sm btn-ghost" href="/admin/exportar/acessos.csv">Exportar CSV</a>
  </form>
</div>

<section class="kpis">
  <div class="kpi"><span>Visualizações</span><strong><?= $n($kpi['pv']) ?></strong><?= $var((int) $kpi['pv'], (int) $prev['pv']) ?></div>
  <div class="kpi"><span>Visitantes únicos</span><strong><?= $n($kpi['visitors']) ?></strong><small>soma de únicos por dia</small></div>
  <div class="kpi"><span>Sessões</span><strong><?= $n($kpi['sessions']) ?></strong><?= $var((int) $kpi['sessions'], (int) $prev['sessions']) ?></div>
  <div class="kpi"><span>Páginas por sessão</span><strong><?= number_format($pps, 2, ',', '.') ?></strong></div>
  <div class="kpi"><span>Tempo médio na página</span><strong><?= $dur($kpi['avg_ms']) ?></strong></div>
  <div class="kpi"><span>Rolagem média</span><strong><?= number_format((float) $kpi['avg_scroll'], 0) ?>%</strong></div>
  <div class="kpi"><span>Taxa de rejeição</span><strong><?= number_format($bounceRate, 0) ?>%</strong><small>sessões de 1 página</small></div>
  <div class="kpi kpi-lead"><span>Leads no período</span><strong><?= $n($leads['contatos'] + $leads['inscritos']) ?></strong><small><?= $leads['contatos'] ?> contatos · <?= $leads['inscritos'] ?> inscritos · <?= $leads['diagnosticos'] ?> diagnósticos</small></div>
</section>

<section class="panel">
  <div class="panel-head"><h2>Visitação diária</h2>
    <div class="legend"><span><i class="sw" style="background:#0060E8"></i>Visualizações</span><span><i class="sw" style="background:#00A06A"></i>Sessões</span></div>
  </div>
  <div class="chart-wrap">
    <svg class="linechart" viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Visualizações e sessões por dia" data-points='<?= e(json_encode(array_map(fn($p) => [date_br($p['day']), $p['pv'], $p['s']], $series))) ?>' data-geom='<?= json_encode(['pl' => $pl, 'pr' => $pr, 'w' => $W, 'n' => $cnt]) ?>'>
      <?php for ($v = 0; $v <= $maxY; $v += $step): ?>
        <line x1="<?= $pl ?>" x2="<?= $W - $pr ?>" y1="<?= round($y($v), 1) ?>" y2="<?= round($y($v), 1) ?>" class="grid"/>
        <text x="<?= $pl - 8 ?>" y="<?= round($y($v), 1) + 4 ?>" class="ax" text-anchor="end"><?= $n($v) ?></text>
      <?php endfor; ?>
      <?php $every = max(1, (int) ceil($cnt / 8)); foreach ($series as $i => $pnt): if ($i % $every === 0 || ($i === $cnt - 1 && ($cnt - 1) % $every >= $every / 2)): ?>
        <text x="<?= round($x($i), 1) ?>" y="<?= $H - 8 ?>" class="ax" text-anchor="middle"><?= date('d/m', strtotime($pnt['day'])) ?></text>
      <?php endif; endforeach; ?>
      <path d="<?= $path('pv') ?>" class="ln" stroke="#0060E8"/>
      <path d="<?= $path('s') ?>" class="ln" stroke="#00A06A"/>
      <line class="xhair" x1="0" x2="0" y1="<?= $pt ?>" y2="<?= $H - $pb ?>" visibility="hidden"/>
    </svg>
    <div class="tip" hidden></div>
  </div>
</section>

<div class="adm-grid">
  <section class="panel">
    <div class="panel-head"><h2>Dias e horários de acesso</h2><span class="muted">horário de Brasília</span></div>
    <div class="heat-wrap">
      <table class="heat">
        <thead><tr><th></th><?php for ($h = 0; $h < 24; $h++): ?><th><?= $h % 3 === 0 ? $h . 'h' : '' ?></th><?php endfor; ?></tr></thead>
        <tbody>
        <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?>
          <tr><th><?= $dows[$d] ?></th>
          <?php for ($h = 0; $h < 24; $h++): $v = $heatMap[$d][$h] ?? 0; $t = $v / $heatMax; ?>
            <td style="--t:<?= round($t, 3) ?>" title="<?= $dows[$d] ?>, <?= $h ?>h: <?= $n($v) ?> visualizações"></td>
          <?php endfor; ?></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div class="heat-scale"><span>menos</span><i style="--t:0"></i><i style="--t:.25"></i><i style="--t:.5"></i><i style="--t:.75"></i><i style="--t:1"></i><span>mais</span></div>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head"><h2>Funil do diagnóstico</h2></div>
    <?php $f0 = max(1, reset($funnel)); ?>
    <ul class="funnel">
      <?php foreach ($funnel as $label => $v): ?>
        <li><span class="fl"><?= e($label) ?></span><span class="fb"><span style="width:<?= max(2, round($v / $f0 * 100, 1)) ?>%"></span></span><span class="fv"><?= $n($v) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <h3 class="sub">Seções do portal</h3>
    <?= $bars($sections, 'section', 'pv') ?>
  </section>
</div>

<section class="panel">
  <div class="panel-head"><h2>Páginas mais vistas</h2></div>
  <div class="table-scroll"><table class="adm-table">
    <thead><tr><th>Página</th><th>Visualizações</th><th>Sessões</th><th>Tempo médio</th><th>Rolagem</th></tr></thead>
    <tbody>
    <?php foreach ($pages as $r): ?>
      <tr><td><a href="<?= e($r['path']) ?>" target="_blank"><?= e($r['title'] ?: $r['path']) ?></a><br><small class="muted"><?= e($r['path']) ?></small></td><td><?= $n($r['pv']) ?></td><td><?= $n($r['s']) ?></td><td><?= $dur($r['ms']) ?></td><td><?= number_format((float) $r['sc'], 0) ?>%</td></tr>
    <?php endforeach; if (!$pages): ?><tr><td colspan="5" class="adm-empty">Sem dados no período.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</section>

<div class="adm-grid">
  <section class="panel"><div class="panel-head"><h2>Origem das visitas</h2><span class="muted">entradas</span></div>
    <?= $bars($refTypes, 'ref_type', 'n', fn($r) => e($refLabels[$r['ref_type']] ?? (string) $r['ref_type'])) ?>
  </section>
  <section class="panel"><div class="panel-head"><h2>Sites, buscadores, redes e IAs</h2></div>
    <?= $bars($refs, 'ref_domain', 'n', fn($r) => e((string) $r['ref_domain']) . ' <small class="muted">' . e($refLabels[$r['ref_type']] ?? '') . '</small>') ?>
  </section>
</div>

<div class="adm-grid">
  <section class="panel"><div class="panel-head"><h2>Cidades</h2></div>
    <?= $bars($cities, 'city', 'n', fn($r) => e($r['city']) . ' <small class="muted">' . e((string) $r['region']) . '</small>', 15) ?>
  </section>
  <section class="panel"><div class="panel-head"><h2>Estados / regiões</h2></div>
    <?= $bars($regions, 'region', 'n', fn($r) => e($r['region']) . ' <small class="muted">' . e((string) $r['country_code']) . '</small>', 15) ?>
    <h3 class="sub">Países</h3>
    <?= $bars($countries, 'country', 'n', null, 6) ?>
  </section>
</div>

<div class="adm-grid adm-grid-3">
  <section class="panel"><div class="panel-head"><h2>Dispositivos</h2></div><?= $bars($devices, 'device', 'n') ?></section>
  <section class="panel"><div class="panel-head"><h2>Navegadores</h2></div><?= $bars($browsers, 'browser', 'n') ?></section>
  <section class="panel"><div class="panel-head"><h2>Sistemas</h2></div><?= $bars($oses, 'os', 'n') ?>
    <h3 class="sub">Idiomas</h3><?= $bars($langs, 'lang', 'n', null, 5) ?></section>
</div>

<div class="adm-grid">
  <section class="panel"><div class="panel-head"><h2>Conversões e eventos</h2></div>
    <?= $bars($events, 'name', 'n', fn($r) => e($evLabels[$r['name']] ?? $r['name']) . ' <small class="muted">' . $n($r['u']) . ' pessoas</small>') ?>
  </section>
  <section class="panel"><div class="panel-head"><h2>Desempenho dos anúncios</h2><span class="muted">Moeda Nobre</span></div>
    <div class="table-scroll"><table class="adm-table">
      <thead><tr><th>Posição</th><th>Impressões</th><th>Cliques</th><th>CTR</th></tr></thead>
      <tbody>
      <?php foreach ($adPlacements as $r): ?>
        <tr><td><?= e($r['label']) ?></td><td><?= $n($r['views']) ?></td><td><?= $n($r['clicks']) ?></td><td><?= $r['views'] ? number_format($r['clicks'] / $r['views'] * 100, 2, ',', '.') . '%' : '—' ?></td></tr>
      <?php endforeach; if (!$adPlacements): ?><tr><td colspan="4" class="adm-empty">Sem dados no período.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>
</div>

<div class="adm-grid">
  <section class="panel"><div class="panel-head"><h2>Robôs de busca e de IA</h2><span class="muted">indexação</span></div>
    <div class="table-scroll"><table class="adm-table">
      <thead><tr><th>Robô</th><th>Tipo</th><th>Acessos</th><th>Último</th></tr></thead>
      <tbody>
      <?php foreach ($crawlers as $r): ?>
        <tr><td><?= e($r['bot']) ?></td><td><span class="tag <?= $r['family'] === 'ia' ? 'tag-blue' : ($r['family'] === 'busca' ? 'tag-green' : '') ?>"><?= e(strtoupper((string) $r['family'])) ?></span></td><td><?= $n($r['n']) ?></td><td><?= date_br((int) $r['last'], true) ?></td></tr>
      <?php endforeach; if (!$crawlers): ?><tr><td colspan="4" class="adm-empty">Nenhum robô registrado no período.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>
  <section class="panel"><div class="panel-head"><h2>Páginas mais lidas pelas IAs</h2></div>
    <?= $bars($crawlerPaths, 'path', 'n') ?>
    <h3 class="sub">Campanhas (UTM)</h3>
    <?= $bars($utm, 'utm_source', 'n', fn($r) => e($r['utm_source']) . ' <small class="muted">' . e(trim($r['utm_medium'] . ' / ' . $r['utm_campaign'], ' /')) . '</small>', 8) ?>
  </section>
</div>

<div class="adm-grid">
  <section class="panel"><div class="panel-head"><h2>Páginas de entrada</h2></div><?= $bars($entries, 'path', 'n') ?></section>
  <section class="panel"><div class="panel-head"><h2>Tempo real</h2><span class="muted">últimos 30 minutos</span></div>
    <?php if ($realtime): ?><ul class="rt">
      <?php foreach ($realtime as $r): ?><li><span class="muted"><?= date('H:i', (int) $r['ts']) ?></span> <b><?= e($r['path']) ?></b> <small class="muted"><?= e(trim(($r['city'] ?? '') . ' · ' . ($refLabels[$r['ref_type']] ?? '') . ' · ' . $r['device'], ' ·')) ?></small></li><?php endforeach; ?>
    </ul><?php else: ?><p class="adm-empty">Ninguém nos últimos 30 minutos.</p><?php endif; ?>
  </section>
</div>
<p class="muted" style="font-size:12.5px;margin-top:8px">Medição própria, sem cookies e sem armazenamento de IP (LGPD). Visitantes únicos usam código com sal diário; com consentimento, visitas recorrentes são identificadas por código anônimo. Robôs são excluídos das métricas de pessoas e listados à parte.</p>
