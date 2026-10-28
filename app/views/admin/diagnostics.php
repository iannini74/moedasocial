<div class="adm-head"><div><h1>Diagnósticos gerados</h1><p class="muted">Municípios que usaram o simulador — sinal de interesse para prospecção.</p></div>
<a class="btn btn-sm btn-ghost" href="/admin/exportar/diagnosticos.csv">Exportar CSV</a></div>
<section class="panel"><div class="table-scroll"><table class="adm-table">
<thead><tr><th>Data</th><th>Município</th><th>População</th><th>Prontidão</th><th>Injeção anual</th><th>Movimento local</th><th>Famílias × valor</th><th>Fonte</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): $d = json_decode((string) $r['data'], true) ?: []; $i = $d['inputs'] ?? []; $res = $d['results'] ?? []; ?>
<tr><td><?= date_br((int) $r['created_at'], true) ?></td><td><b><?= e($r['municipality']) ?></b>/<?= e($r['uf']) ?></td><td><?= number_format((int) $r['population'], 0, ',', '.') ?></td>
<td><?= (int) $r['score'] ?>/100</td><td><?= isset($res['injecao']) ? money_br((float) $res['injecao']) : '—' ?></td><td><?= isset($res['movimento']) ? money_br((float) $res['movimento']) : '—' ?></td>
<td><?= e((string) ($i['familias'] ?? '')) ?> × R$ <?= e((string) ($i['valor'] ?? '')) ?></td><td><?= e((string) ($i['fonte'] ?? '')) ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="adm-empty">Nenhum diagnóstico ainda.</td></tr><?php endif; ?>
</tbody></table></div></section>
