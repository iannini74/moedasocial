<?php
$sourceNames = ['pncp' => 'PNCP', 'diarios' => 'Diários oficiais (Querido Diário)', 'imprensa' => 'Google Notícias', 'web' => 'Pesquisa web (OpenAI)'];
$last = (int) Db::setting('editais_last_run'); ?>
<div class="adm-head"><div><h1>Editais</h1>
<p class="muted">Pesquisa diária com três agentes: coleta, pesquisa na web e análise · última execução: <?= $last ? date_br($last, true) : 'nunca' ?><?= $hasKey ? '' : ' · <b>sem chave da OpenAI: pesquisa web desligada e tudo vai para revisão</b>' ?></p></div>
<form method="post" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Agendando…'"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="do" value="pesquisar"><button class="btn btn-sm btn-primary">Pesquisar agora</button></form></div>
<?php if ($flash): ?><div class="alert alert-ok"><?= e($flash) ?></div><?php endif; ?>

<?php if (!empty($run['fontes'])): ?>
<section class="panel"><h2>Última execução por fonte</h2><div class="table-scroll"><table class="adm-table">
<thead><tr><th>Fonte</th><th>Situação</th><th>Encontrados</th><th>Novos</th><th>Observação</th></tr></thead><tbody>
<?php foreach ($run['fontes'] as $k => $s): ?>
<tr><td><?= e($sourceNames[$k] ?? $k) ?></td><td><span class="tag <?= $s['ok'] ? 'tag-green' : 'tag-warn' ?>"><?= $s['ok'] ? 'ok' : 'falhou' ?></span></td>
<td><?= (int) $s['encontrados'] ?></td><td><?= (int) $s['novos'] ?></td><td class="muted"><?= e($s['erro'] ?? ($s['modelo'] ?? '')) ?></td></tr>
<?php endforeach; $a = $run['analise'] ?? []; ?>
<tr><td><b>Análise (OpenAI)</b></td><td><span class="tag <?= empty($a['erro']) ? 'tag-green' : 'tag-warn' ?>"><?= empty($a['erro']) ? 'ok' : 'falhou' ?></span></td>
<td><?= (int) ($a['analisados'] ?? 0) ?> analisados</td><td><?= (int) ($a['publicados'] ?? 0) ?> publicados</td>
<td class="muted"><?= (int) ($a['revisar'] ?? 0) ?> para revisar · <?= (int) ($a['descartados'] ?? 0) ?> descartados · <?= (int) ($a['duplicados'] ?? 0) ?> duplicados<?= !empty($a['erro']) ? ' · ' . e($a['erro']) : '' ?></td></tr>
</tbody></table></div></section>
<?php endif; ?>

<?php if ($reanalysis): ?><p class="muted" style="margin:16px 0 0"><?= $reanalysis ?> itens em reanálise pelas regras atualizadas. Eles continuam no status atual (e visíveis, se publicados) até a nova análise.</p><?php endif; ?>
<nav class="filters" style="margin:20px 0 12px">
<?php foreach (Editais::STATUS as $k => $v): ?><a href="/admin/editais?status=<?= $k ?>" class="<?= $status === $k ? 'on' : '' ?>"><?= e($v) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</a><?php endforeach; ?>
</nav>
<section class="panel"><div class="table-scroll"><table class="adm-table">
<thead><tr><th>Data</th><th>Título</th><th>Local</th><th>Tema / tipo</th><th>Fonte</th><th>Conf.</th><th>Ações</th></tr></thead>
<tbody>
<?php foreach ($rows as $r):
    $btn = fn(string $do, string $label, array $extra = []) => '<form method="post" style="display:inline"><input type="hidden" name="_csrf" value="' . e($csrf) . '"><input type="hidden" name="do" value="' . $do . '"><input type="hidden" name="id" value="' . (int) $r['id'] . '"><input type="hidden" name="back" value="' . e($status) . '">'
        . implode('', array_map(fn($k, $v) => '<input type="hidden" name="' . $k . '" value="' . e($v) . '">', array_keys($extra), $extra)) . '<button class="linklike">' . $label . '</button></form>'; ?>
<tr><td style="white-space:nowrap"><?= date_br((int) $r['published_at']) ?><?php if ($r['deadline_at']): ?><br><span class="muted">prazo <?= date_br((int) $r['deadline_at']) ?></span><?php endif; ?></td>
<td><a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"><?= e($r['title']) ?></a><?php if ($r['summary']): ?><br><span class="muted"><?= e(excerpt((string) $r['summary'], 200)) ?></span><?php elseif ($r['trecho']): ?><br><span class="muted"><?= e(excerpt((string) $r['trecho'], 200)) ?></span><?php endif; ?></td>
<td><?= e(trim($r['municipio'] . '/' . $r['uf'], '/')) ?></td>
<td><?= e(Editais::CATEGORIES[$r['categoria']] ?? '—') ?><br><span class="muted"><?= e(Editais::TYPES[$r['tipo']] ?? $r['tipo']) ?></span></td>
<td><?= e($r['source']) ?></td><td><?= $r['confianca'] !== null ? (int) $r['confianca'] : '—' ?></td>
<td style="white-space:nowrap">
<?php if ($r['status'] !== 'publicado'): ?><?= $btn('status', 'publicar', ['to' => 'publicado']) ?> · <?php endif; ?>
<?php if ($r['status'] !== 'descartado'): ?><?= $btn('status', 'descartar', ['to' => 'descartado']) ?><?php endif; ?>
<?php if ($r['status'] === 'publicado'): ?> · <?= $btn('fixar', $r['pinned'] ? 'desafixar' : 'fixar') ?><?php endif; ?>
</td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="adm-empty">Nenhum item com este status.</td></tr><?php endif; ?>
</tbody></table></div></section>
<p class="muted" style="font-size:13px">Itens publicados pelo agente de análise têm confiança igual ou maior que <?= (int) cfg('editais.min_confidence') ?>. Itens em “Revisar” só aparecem no site depois de publicados aqui. Descartados e duplicados são apagados após 90 dias.</p>
