<div class="adm-login">
  <form method="post" class="card">
    <a class="logo" href="/"><?php partial('logo') ?><span class="logo-text"><strong>moeda<span>social</span></strong><small>painel administrativo</small></span></a>
    <h1 style="font-size:22px;margin:22px 0 6px">Entrar</h1>
    <p class="muted" style="font-size:14px">Acesso restrito à equipe do portal.</p>
    <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
    <label class="field"><span>Usuário</span><input type="text" name="user" autocomplete="username" required></label>
    <label class="field" style="margin-top:12px"><span>Senha</span><input type="password" name="pass" autocomplete="current-password" required></label>
    <button class="btn btn-primary" style="width:100%;margin-top:18px">Entrar</button>
  </form>
</div>
