<?php $master = Membros::master(); ?>
<div class="adm-head"><div><h1>Acessos à área de editais</h1>
<p class="muted">Pedidos de cadastro para a página <a href="/editais" target="_blank">/editais</a>. Usuários aprovados entram com o e-mail e um código enviado a cada acesso. Usuário master: <b><?= e($master) ?></b> (sempre aprovado).</p></div>
<a class="btn btn-sm btn-ghost" href="/admin/exportar/acessos.csv">Exportar CSV</a></div>
<?php if ($flash): ?><div class="alert alert-ok"><?= e($flash) ?></div><?php endif; ?>
<nav class="filters" style="margin:12px 0">
<?php foreach (Membros::STATUS as $k => $v): ?><a href="/admin/acessos?status=<?= $k ?>" class="<?= $status === $k ? 'on' : '' ?>"><?= e($v) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</a><?php endforeach; ?>
</nav>
<section class="panel"><div class="table-scroll"><table class="adm-table">
<thead><tr><th>Pedido</th><th>Pessoa</th><th>Organização</th><th>Local</th><th>Motivo</th><th>Último acesso</th><th>Ações</th></tr></thead>
<tbody>
<?php foreach ($rows as $r):
    $btn = fn(string $to, string $label, string $confirm = '') => '<form method="post" style="display:inline"' . ($confirm ? ' onsubmit="return confirm(\'' . $confirm . '\')"' : '') . '><input type="hidden" name="_csrf" value="' . e($csrf) . '"><input type="hidden" name="do" value="' . ($to === 'excluir' ? 'excluir' : 'decidir') . '"><input type="hidden" name="to" value="' . $to . '"><input type="hidden" name="id" value="' . (int) $r['id'] . '"><input type="hidden" name="back" value="' . e($status) . '"><button class="linklike">' . $label . '</button></form>';
    $isMaster = $r['email'] === $master; ?>
<tr><td style="white-space:nowrap"><?= date_br((int) $r['created_at'], true) ?></td>
<td><b><?= e($r['name']) ?></b><?= $isMaster ? ' <span class="tag tag-blue">master</span>' : '' ?><br><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?= $r['phone'] ? '<br><span class="muted">' . e($r['phone']) . '</span>' : '' ?></td>
<td><?= e($r['organization']) ?><br><span class="muted"><?= e(Contact::ORG_TYPES[$r['org_type']] ?? '') ?><?= $r['role'] ? ' · ' . e($r['role']) : '' ?></span></td>
<td><?= e(trim($r['municipality'] . '/' . $r['uf'], '/')) ?></td>
<td class="muted" style="max-width:260px"><?= e((string) $r['purpose']) ?></td>
<td><?= $r['last_login_at'] ? date_br((int) $r['last_login_at'], true) . '<br><span class="muted">' . (int) $r['login_count'] . ' acessos</span>' : '<span class="muted">nunca</span>' ?></td>
<td style="white-space:nowrap">
<?php if ($r['status'] !== 'aprovado'): ?><?= $btn('aprovado', '<b>aprovar</b>') ?><?php endif; ?>
<?php if (!$isMaster): ?>
  <?php if ($r['status'] === 'pendente'): ?> · <?= $btn('recusado', 'recusar') ?><?php endif; ?>
  <?php if ($r['status'] === 'aprovado'): ?><?= $btn('suspenso', 'suspender', 'Suspender o acesso deste usuário?') ?><?php endif; ?>
  · <?= $btn('excluir', 'excluir', 'Excluir este cadastro definitivamente?') ?>
<?php endif; ?>
</td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="adm-empty">Nenhum cadastro com este status.</td></tr><?php endif; ?>
</tbody></table></div></section>
<p class="muted" style="font-size:13px">Ao aprovar, o usuário recebe um e-mail com as instruções de acesso. Suspender ou excluir encerra a sessão aberta na hora. Você, logado neste painel, vê a página de editais completa sem precisar de código.</p>
