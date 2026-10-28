<div class="adm-head"><div><h1>Blog</h1>
<p class="muted">Agente editorial: <?= $hasKey ? 'ativo' : '<b>inativo — configure a chave da API</b>' ?> · intervalo de <?= (int) cfg('ai.blog_interval_days') ?> dias · próximo artigo <?= $due ? '<b>pendente (sai na próxima execução do cron)</b>' : 'ainda não devido' ?></p></div>
<form method="post" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Gerando… pode levar alguns minutos'"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="do" value="gerar"><button class="btn btn-sm btn-primary"<?= $hasKey ? '' : ' disabled' ?>>Gerar artigo agora</button></form></div>
<?php if ($flash): ?><div class="alert alert-ok"><?= e($flash) ?></div><?php endif; ?>
<section class="panel"><div class="table-scroll"><table class="adm-table">
<thead><tr><th>Publicação</th><th>Título</th><th>Origem</th><th>Newsletter</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= date_br((int) $r['published_at'], true) ?></td><td><a href="/blog/<?= e($r['slug']) ?>" target="_blank"><?= e($r['title']) ?></a></td>
<td><span class="tag <?= $r['generated_by'] === 'agente-ia' ? 'tag-blue' : 'tag-green' ?>"><?= e($r['generated_by']) ?></span></td>
<td><?= $r['notified_at'] ? date_br((int) $r['notified_at']) : '<span class="muted">pendente</span>' ?></td>
<td><form method="post"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
<select name="status" onchange="this.form.submit()"><option value="published"<?= $r['status'] === 'published' ? ' selected' : '' ?>>Publicado</option><option value="draft"<?= $r['status'] !== 'published' ? ' selected' : '' ?>>Rascunho</option></select></form></td>
<td><form method="post" onsubmit="return confirm('Excluir este artigo?')"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="do" value="excluir"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="linklike">excluir</button></form></td></tr>
<?php endforeach; ?>
</tbody></table></div></section>
