<?php $id = 'nl' . substr(md5($source . mt_rand()), 0, 6); ?>
<form class="nl-form" method="post" action="/newsletter" data-ajax novalidate>
  <input type="hidden" name="_t" value="<?= e(form_token()) ?>">
  <input type="hidden" name="source" value="<?= e($source) ?>">
  <div class="hp" aria-hidden="true"><label>Não preencha <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
  <div class="nl-fields">
    <label class="field"><span>Nome</span><input type="text" name="name" autocomplete="name" placeholder="Como podemos te chamar?"></label>
    <label class="field"><span>E-mail *</span><input type="email" name="email" required autocomplete="email" placeholder="voce@email.com.br"></label>
    <label class="field"><span>Você é</span>
      <select name="profile">
        <option value="">Selecione (opcional)</option>
        <?php foreach (Newsletter::PROFILES as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach; ?>
      </select>
    </label>
  </div>
  <label class="check"><input type="checkbox" name="consent" value="1" required> <span>Quero receber os novos artigos e li a <a href="/privacidade" target="_blank">Política de Privacidade</a>.</span></label>
  <button class="btn btn-primary" type="submit">Quero receber</button>
  <p class="form-msg" role="status" aria-live="polite"></p>
</form>
