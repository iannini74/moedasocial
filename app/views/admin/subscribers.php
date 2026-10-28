<?php $st = array_column($stats, 'n', 'status'); ?>
<div class="adm-head"><div><h1>Inscritos na newsletter</h1>
<p class="muted"><?= (int) ($st['confirmed'] ?? 0) ?> confirmados · <?= (int) ($st['pending'] ?? 0) ?> aguardando confirmação · <?= (int) ($st['unsubscribed'] ?? 0) ?> cancelados</p></div>
<a class="btn btn-sm btn-ghost" href="/admin/exportar/inscritos.csv">Exportar CSV</a></div>
<section class="panel"><div class="table-scroll"><table class="adm-table">
<thead><tr><th>Inscrição</th><th>E-mail</th><th>Nome</th><th>Perfil</th><th>Município</th><th>Origem</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= date_br((int) $r['created_at']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['name']) ?></td><td><?= e(Newsletter::PROFILES[$r['profile']] ?? '—') ?></td>
<td><?= e(trim($r['municipality'] . '/' . $r['uf'], '/')) ?></td><td><small><?= e($r['source']) ?></small></td>
<td><span class="tag <?= $r['status'] === 'confirmed' ? 'tag-green' : ($r['status'] === 'pending' ? 'tag-warn' : '') ?>"><?= ['confirmed' => 'Confirmado', 'pending' => 'Pendente', 'unsubscribed' => 'Cancelado'][$r['status']] ?? $r['status'] ?></span></td>
<td><form method="post" onsubmit="return confirm('Excluir definitivamente este inscrito (pedido LGPD)?')"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="do" value="excluir"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="linklike">excluir</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="adm-empty">Nenhum inscrito ainda.</td></tr><?php endif; ?>
</tbody></table></div></section>
