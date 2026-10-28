<div class="adm-head"><div><h1>Contatos de implantação</h1><p class="muted"><?= count($rows) ?> registros · enviados para <?= e(cfg('mail.contact_to')) ?></p></div>
<a class="btn btn-sm btn-ghost" href="/admin/exportar/contatos.csv">Exportar CSV</a></div>
<section class="panel"><div class="table-scroll"><table class="adm-table">
<thead><tr><th>Data</th><th>Protocolo</th><th>Contato</th><th>Organização</th><th>Município</th><th>Estágio / prazo</th><th>Interesses e mensagem</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?= date_br((int) $r['created_at'], true) ?></td>
  <td><?= e(Contact::protocol((int) $r['id'])) ?><br><small class="muted"><?= e((string) $r['email_status']) ?></small></td>
  <td><b><?= e($r['name']) ?></b><br><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><br><?= e($r['phone']) ?> <small class="muted"><?= e($r['preferred_contact']) ?></small></td>
  <td><?= e(Contact::ORG_TYPES[$r['org_type']] ?? $r['org_type']) ?><br><small><?= e($r['organization']) ?> <?= $r['role'] ? '· ' . e($r['role']) : '' ?></small></td>
  <td><?= e($r['municipality']) ?>/<?= e($r['uf']) ?><br><small class="muted"><?= e(Contact::POPULATIONS[$r['population']] ?? '') ?></small></td>
  <td><?= e(Contact::STAGES[$r['stage']] ?? '') ?><br><small class="muted"><?= e(Contact::TIMELINES[$r['timeline']] ?? '') ?></small></td>
  <td style="max-width:360px"><small><?= e(implode(', ', array_map(fn($i) => Contact::INTERESTS[$i] ?? $i, array_filter(explode(',', (string) $r['interests']))))) ?></small><br><?= nl2br(e(excerpt((string) $r['message'], 400))) ?></td>
  <td><form method="post"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
    <select name="status" onchange="this.form.submit()"><?php foreach (['novo' => 'Novo', 'em-atendimento' => 'Em atendimento', 'proposta' => 'Proposta', 'fechado' => 'Fechado', 'descartado' => 'Descartado'] as $k => $l): ?><option value="<?= $k ?>"<?= $r['status'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></form></td>
</tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="adm-empty">Nenhum contato ainda.</td></tr><?php endif; ?>
</tbody></table></div></section>
