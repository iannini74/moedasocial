<?php
/** @var array $state */
$ok = !empty($state['ok']) && empty($state['errors']);
$err = $state['errors'] ?? [];
$d = $state['data'] ?? [];
$v = fn(string $k) => e((string) ($d[$k] ?? ''));
$sel = fn(string $k, string $val) => (($d[$k] ?? '') === $val) ? ' selected' : '';
$fe = function (string $k) use ($err) { return isset($err[$k]) ? '<span class="err">' . e($err[$k]) . '</span>' : ''; };
$cls = fn(string $k) => isset($err[$k]) ? 'field invalid' : 'field';
$prefill = e((string) ($_GET['municipio'] ?? ''));
?>
<section class="page-hero">
  <div class="wrap">
    <?php partial('crumbs', ['items' => ['Contato' => null]]) ?>
    <p class="kicker">Apoio à implantação</p>
    <h1>Vamos levar a moeda social para o seu município?</h1>
    <p class="lead">Conte um pouco sobre a sua realidade. Uma equipe especializada em moeda social, meios de pagamento e gestão pública responde em até 2 dias úteis — sem compromisso.</p>
  </div>
</section>

<section class="section">
  <div class="wrap two-col two-col-wide">
    <div>
      <?php if ($ok): ?>
        <div class="message-box" style="margin:0">
          <div class="icon"><?= icon('check-circle', 'icon', 34) ?></div>
          <h2>Mensagem recebida, <?= e(explode(' ', (string) ($state['name'] ?? ''))[0]) ?>!</h2>
          <p>Seu protocolo é <strong><?= e($state['protocol'] ?? '') ?></strong>. Enviamos uma confirmação para o seu e-mail com os próximos passos — se não encontrar, confira a caixa de spam.</p>
          <p class="muted">Enquanto isso, que tal adiantar o <a href="/diagnostico">diagnóstico gratuito do seu município</a>? Ele deixa a nossa conversa muito mais produtiva.</p>
          <a class="btn btn-primary" href="/diagnostico">Fazer o diagnóstico</a>
        </div>
      <?php else: ?>
        <?php if (isset($err['_form'])): ?><div class="alert alert-bad" role="alert"><?= e($err['_form']) ?></div>
        <?php elseif ($err): ?><div class="alert alert-bad" role="alert">Quase lá! Revise os campos destacados abaixo.</div><?php endif; ?>

        <form method="post" action="/contato" class="card" style="padding:clamp(20px,4vw,32px)" novalidate>
          <input type="hidden" name="_t" value="<?= e(form_token()) ?>">
          <input type="hidden" name="source" value="<?= e((string) ($_SERVER['HTTP_REFERER'] ?? '/contato')) ?>">
          <div class="hp" aria-hidden="true"><label>Não preencha <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

          <fieldset>
            <legend>1. Sobre você</legend>
            <div class="form-grid">
              <label class="<?= $cls('name') ?> full"><span>Nome completo *</span><input type="text" name="name" value="<?= $v('name') ?>" autocomplete="name" required><?= $fe('name') ?></label>
              <label class="<?= $cls('email') ?>"><span>E-mail *</span><input type="email" name="email" value="<?= $v('email') ?>" autocomplete="email" required><?= $fe('email') ?></label>
              <label class="<?= $cls('phone') ?>"><span>Telefone / WhatsApp</span><input type="tel" name="phone" value="<?= $v('phone') ?>" autocomplete="tel" placeholder="(31) 99999-9999" inputmode="tel"><?= $fe('phone') ?></label>
              <label class="field"><span>Cargo ou função</span><input type="text" name="role" value="<?= $v('role') ?>" placeholder="Ex.: Secretário(a) de Fazenda"></label>
              <label class="field"><span>Prefere ser contatado por</span>
                <select name="preferred_contact">
                  <?php foreach (['email' => 'E-mail', 'whatsapp' => 'WhatsApp', 'telefone' => 'Ligação', 'reuniao' => 'Videochamada'] as $k => $l): ?><option value="<?= $k ?>"<?= $sel('preferred_contact', $k) ?>><?= $l ?></option><?php endforeach; ?>
                </select>
              </label>
            </div>
          </fieldset>

          <fieldset class="mt-3">
            <legend>2. Sobre a organização e o município</legend>
            <div class="form-grid">
              <label class="<?= $cls('org_type') ?>"><span>Tipo de organização *</span>
                <select name="org_type" required><option value="">Selecione</option>
                  <?php foreach (Contact::ORG_TYPES as $k => $l): ?><option value="<?= $k ?>"<?= $sel('org_type', $k) ?>><?= e($l) ?></option><?php endforeach; ?>
                </select><?= $fe('org_type') ?></label>
              <label class="field"><span>Nome da organização</span><input type="text" name="organization" value="<?= $v('organization') ?>" placeholder="Ex.: Prefeitura de ..."></label>
              <label class="<?= $cls('municipality') ?>"><span>Município *</span><input type="text" name="municipality" value="<?= $v('municipality') ?: $prefill ?>" autocomplete="address-level2" required><?= $fe('municipality') ?></label>
              <label class="<?= $cls('uf') ?>"><span>UF *</span>
                <select name="uf" required><option value="">UF</option><?php foreach (ufs() as $u): ?><option<?= $sel('uf', $u) ?>><?= $u ?></option><?php endforeach; ?></select><?= $fe('uf') ?></label>
              <label class="field"><span>População aproximada</span>
                <select name="population"><option value="">Selecione</option><?php foreach (Contact::POPULATIONS as $k => $l): ?><option value="<?= $k ?>"<?= $sel('population', $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
              <label class="<?= $cls('stage') ?>"><span>Em que momento vocês estão? *</span>
                <select name="stage" required><option value="">Selecione</option><?php foreach (Contact::STAGES as $k => $l): ?><option value="<?= $k ?>"<?= $sel('stage', $k) ?>><?= e($l) ?></option><?php endforeach; ?></select><?= $fe('stage') ?></label>
              <label class="field full"><span>Prazo desejado</span>
                <select name="timeline"><option value="">Selecione</option><?php foreach (Contact::TIMELINES as $k => $l): ?><option value="<?= $k ?>"<?= $sel('timeline', $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
            </div>
          </fieldset>

          <fieldset class="mt-3">
            <legend>3. Como podemos ajudar?</legend>
            <p class="muted" style="font-size:14px;margin-top:-6px">Marque quantos quiser.</p>
            <div class="chips">
              <?php foreach (Contact::INTERESTS as $k => $l): ?>
                <label><input type="checkbox" name="interests[]" value="<?= $k ?>"<?= in_array($k, $d['interests'] ?? [], true) ? ' checked' : '' ?>><span><?= e($l) ?></span></label>
              <?php endforeach; ?>
            </div>
            <label class="field mt-2"><span>Mensagem <small>— objetivos, público que quer atender, dúvidas…</small></span>
              <textarea name="message" rows="5" placeholder="Ex.: Queremos transformar o auxílio-alimentação municipal em moeda social e incluir o comércio dos bairros. Temos cerca de 3 mil famílias no CadÚnico..."><?= $v('message') ?></textarea></label>
          </fieldset>

          <div class="stack mt-3">
            <label class="check <?= isset($err['consent']) ? 'invalid' : '' ?>"><input type="checkbox" name="consent" value="1" required> <span>Autorizo o uso dos meus dados para responder a esta solicitação, conforme a <a href="/privacidade" target="_blank">Política de Privacidade</a>. *</span></label>
            <?= $fe('consent') ?>
            <label class="check"><input type="checkbox" name="newsletter" value="1" checked> <span>Quero receber os novos artigos do portal por e-mail (opcional).</span></label>
            <button class="btn btn-primary btn-lg" type="submit">Enviar solicitação</button>
            <p class="muted" style="font-size:13px;margin:0"><?= icon('lock', 'icon-sm') ?> Seus dados são usados apenas para este atendimento. Você pode pedir a exclusão a qualquer momento.</p>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <aside class="aside">
      <div class="aside-box">
        <h3>O que acontece depois?</h3>
        <ol class="check-list" style="list-style:none">
          <li><strong>Em até 2 dias úteis</strong> — retornamos pelo canal que você preferir.</li>
          <li><strong>Conversa de 30 minutos</strong> — entendemos objetivos, público e orçamento.</li>
          <li><strong>Proposta de modelo</strong> — desenho do programa, cuidados jurídicos e de controle.</li>
          <li><strong>Plano de implantação</strong> — da minuta de lei ao credenciamento do comércio.</li>
        </ol>
      </div>
      <div class="aside-box">
        <h3>Prefere e-mail direto?</h3>
        <p>Escreva para <a href="mailto:<?= e(cfg('mail.contact_to')) ?>"><?= e(cfg('mail.contact_to')) ?></a>.</p>
      </div>
      <?php ad('contato-lateral', 'card') ?>
    </aside>
  </div>
</section>
