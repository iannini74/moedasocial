<div class="cookie" id="cookie" role="dialog" aria-modal="false" aria-labelledby="cookie-title" hidden>
  <div class="cookie-box">
    <h2 id="cookie-title">Sua privacidade importa</h2>
    <p>Usamos tecnologias essenciais para o portal funcionar e, com a sua permissão, estatísticas para entender quais conteúdos ajudam mais os municípios. Nossa medição própria não usa cookies e nunca guarda seu IP. <a href="/cookies">Saiba mais</a>.</p>
    <div class="cookie-opts" hidden>
      <label class="check"><input type="checkbox" checked disabled> <span><strong>Essenciais</strong> — segurança dos formulários e suas preferências. Sempre ativos.</span></label>
      <label class="check"><input type="checkbox" name="stats" value="1"> <span><strong>Estatísticas</strong> — identificador anônimo para medir visitas recorrentes<?= cfg('analytics.ga4_id') ? ' e Google Analytics 4' : '' ?>.</span></label>
      <label class="check"><input type="checkbox" name="marketing" value="1"> <span><strong>Marketing</strong> — medir a efetividade de campanhas e anúncios do patrocinador.</span></label>
    </div>
    <div class="cookie-actions">
      <button type="button" class="btn btn-sm btn-ghost" data-cookie="custom">Personalizar</button>
      <button type="button" class="btn btn-sm btn-ghost" data-cookie="necessary">Somente essenciais</button>
      <button type="button" class="btn btn-sm btn-primary" data-cookie="all">Aceitar todos</button>
    </div>
  </div>
</div>
