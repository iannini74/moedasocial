<?php
/** @var array $stats @var string $mode inicio|cadastro|enviado|codigo @var array $errors @var array $data @var ?string $msg @var string $email */
$err = $errors;
$v = fn(string $k) => e($data[$k] ?? '');
$cls = fn(string $k) => isset($err[$k]) ? 'field invalid' : 'field';
$fe = fn(string $k) => isset($err[$k]) ? '<span class="err">' . e($err[$k]) . '</span>' : '';
$catIcons = ['moeda-social' => 'coins', 'vale-alimentacao' => 'cart', 'distribuicao-beneficios' => 'wallet', 'beneficios-sociais' => 'users'];
$last = (int) Db::setting('editais_last_run'); ?>
<section class="page-hero">
  <div class="wrap">
    <?php partial('crumbs', ['items' => ['Editais' => null]]) ?>
    <p class="kicker">Área restrita · pesquisada todos os dias</p>
    <h1>Editais de moeda social, vale-alimentação e benefícios sociais</h1>
    <p class="lead">Editais, chamamentos, licitações, credenciamentos, leis, decretos e anúncios oficiais de municípios, estados e do governo federal, reunidos e resumidos todos os dias. O acesso é gratuito e liberado após a aprovação do cadastro.</p>
    <div class="meta-line">
      <?php if ($last): ?><span><?= icon('clock', 'icon-sm') ?> Última pesquisa: <?= date_br($last, true) ?></span><?php endif; ?>
      <span><?= icon('lock', 'icon-sm') ?> Entrada com código enviado ao seu e-mail, sem senha</span>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="ed-stats">
      <div class="ed-stat ed-stat-main"><strong><?= (int) $stats['total'] ?></strong><span>publicações reunidas</span></div>
      <div class="ed-stat"><strong><?= (int) $stats['abertos'] ?></strong><span>com prazo aberto</span></div>
      <?php foreach (Editais::CATEGORIES as $k => $label): ?>
        <div class="ed-stat"><span class="ed-stat-icon"><?= icon($catIcons[$k] ?? 'file', 'icon-sm') ?></span><strong><?= (int) ($stats['categorias'][$k] ?? 0) ?></strong><span><?= e($label) ?></span></div>
      <?php endforeach; ?>
    </div>

    <div class="ed-gate">
      <div class="ed-preview" aria-hidden="true">
        <?php for ($i = 0; $i < 4; $i++): ?>
          <div class="ed-ghost"><i></i><div><b></b><b></b><b></b></div></div>
        <?php endfor; ?>
        <div class="ed-preview-lock"><?= icon('lock', 'icon', 28) ?><p>Conteúdo disponível para usuários cadastrados</p></div>
      </div>

      <div class="ed-forms">
        <?php if ($mode === 'enviado'): ?>
          <div class="card ed-box ed-done">
            <span class="ed-done-icon"><?= icon('check-circle', 'icon', 28) ?></span>
            <h2>Pedido recebido</h2>
            <p><?= e($msg) ?></p>
            <a class="btn btn-ghost btn-sm" href="/editais">Voltar</a>
          </div>

        <?php elseif ($mode === 'codigo'): ?>
          <form class="card ed-box" method="post" action="/editais/codigo" novalidate>
            <h2>Digite o código</h2>
            <p class="muted"><?= e($msg ?? 'Enviamos um código de 6 dígitos para o seu e-mail.') ?></p>
            <input type="hidden" name="email" value="<?= e($email) ?>">
            <label class="field<?= !empty($codeError) ? ' invalid' : '' ?>"><span>Código recebido em <?= e($email) ?></span>
              <input class="ed-code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus>
              <?php if (!empty($codeError)): ?><span class="err"><?= e($codeError) ?></span><?php endif; ?>
            </label>
            <button class="btn btn-primary">Entrar</button>
            <p class="ed-small">Não recebeu? Confira a caixa de spam. <a href="/editais">Pedir outro código</a></p>
          </form>

        <?php else: ?>
          <form class="card ed-box" method="post" action="/editais/entrar" novalidate>
            <h2>Já tenho acesso</h2>
            <p class="muted">Informe seu e-mail cadastrado. Enviaremos um código para você entrar.</p>
            <input type="hidden" name="_t" value="<?= e(form_token()) ?>">
            <label class="field<?= !empty($loginError) ? ' invalid' : '' ?>"><span>E-mail</span>
              <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
              <?php if (!empty($loginError)): ?><span class="err"><?= e($loginError) ?></span><?php endif; ?>
            </label>
            <button class="btn btn-primary">Receber código</button>
          </form>

          <form class="card ed-box" id="solicitar" method="post" action="/editais/solicitar#solicitar" novalidate>
            <h2>Solicitar acesso</h2>
            <p class="muted">Gratuito. Avisamos por e-mail quando o cadastro for aprovado.</p>
            <?php if (!empty($err['_form'])): ?><div class="alert alert-bad"><?= e($err['_form']) ?></div><?php endif; ?>
            <input type="hidden" name="_t" value="<?= e(form_token()) ?>">
            <div class="hp" aria-hidden="true"><label>Não preencha <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="form-grid">
              <label class="<?= $cls('name') ?> full"><span>Nome completo *</span><input type="text" name="name" value="<?= $v('name') ?>" autocomplete="name" required><?= $fe('name') ?></label>
              <label class="<?= $cls('email') ?>"><span>E-mail *</span><input type="email" name="email" value="<?= $v('email') ?>" autocomplete="email" required><?= $fe('email') ?></label>
              <label class="<?= $cls('phone') ?>"><span>Telefone / WhatsApp</span><input type="tel" name="phone" value="<?= $v('phone') ?>" autocomplete="tel" inputmode="tel"><?= $fe('phone') ?></label>
              <label class="<?= $cls('org_type') ?>"><span>Tipo de organização *</span>
                <select name="org_type" required><option value="">Selecione</option><?php foreach (Contact::ORG_TYPES as $k => $l): ?><option value="<?= $k ?>"<?= ($data['org_type'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select><?= $fe('org_type') ?></label>
              <label class="<?= $cls('organization') ?>"><span>Organização *</span><input type="text" name="organization" value="<?= $v('organization') ?>" placeholder="Ex.: Prefeitura de ..." required><?= $fe('organization') ?></label>
              <label class="field"><span>Cargo ou função</span><input type="text" name="role" value="<?= $v('role') ?>"></label>
              <label class="<?= $cls('municipality') ?>"><span>Município *</span><input type="text" name="municipality" value="<?= $v('municipality') ?>" autocomplete="address-level2" required><?= $fe('municipality') ?></label>
              <label class="<?= $cls('uf') ?>"><span>UF *</span>
                <select name="uf" required><option value="">UF</option><?php foreach (ufs() as $uf): ?><option<?= ($data['uf'] ?? '') === $uf ? ' selected' : '' ?>><?= $uf ?></option><?php endforeach; ?></select><?= $fe('uf') ?></label>
              <label class="field full"><span>Para que você vai usar as informações?</span><textarea name="purpose" rows="3" placeholder="Ex.: acompanhar editais de vale-alimentação na região"><?= $v('purpose') ?></textarea></label>
            </div>
            <label class="check <?= isset($err['consent']) ? 'invalid' : '' ?>"><input type="checkbox" name="consent" value="1" required> <span>Autorizo o uso dos meus dados para analisar este pedido e enviar os códigos de acesso, conforme a <a href="/privacidade" target="_blank">Política de Privacidade</a>. *</span></label>
            <?= $fe('consent') ?>
            <button class="btn btn-primary">Solicitar acesso</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
