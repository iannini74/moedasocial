<div class="adm-head"><div><h1>Notícias</h1><p class="muted">Última atualização: <?= $last ? date_br($last, true) : 'nunca' ?> · atualiza a cada <?= (int) cfg('news.refresh_hours') ?>h</p></div>
<form method="post"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="do" value="atualizar"><button class="btn btn-sm btn-primary">Atualizar agora</button></form></div>
<?php if ($flash): ?><div class="alert alert-ok"><?= e($flash) ?></div><?php endif; ?>
<section class="panel"><div class="table-scroll"><table class="adm-table">
<thead><tr><th>Data</th><th>Título</th><th>Fonte</th><th>Categoria</th><th>Ações</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr<?= $r['hidden'] ? ' style="opacity:.45"' : '' ?>><td><?= date_br((int) $r['published_at']) ?></td><td><a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"><?= e($r['title']) ?></a></td><td><?= e($r['source']) ?></td><td><?= e(News::CATEGORIES[$r['category']] ?? $r['category']) ?></td>
<td style="white-space:nowrap">
<form method="post" style="display:inline"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="do" value="fixar"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="linklike"><?= $r['pinned'] ? 'desafixar' : 'fixar' ?></button></form> ·
<form method="post" style="display:inline"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="do" value="ocultar"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="linklike"><?= $r['hidden'] ? 'mostrar' : 'ocultar' ?></button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="5" class="adm-empty">Nenhuma notícia coletada ainda. Clique em “Atualizar agora”.</td></tr><?php endif; ?>
</tbody></table></div></section>
