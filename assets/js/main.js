/* Portal Moeda Social — © TribeX */
(function () {
  'use strict';
  var store = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  };

  /* ---------- Menu móvel ---------- */
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('menu');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') !== 'true';
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
      nav.classList.toggle('open', open);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('open')) { toggle.click(); toggle.focus(); }
    });
  }

  /* ---------- Consentimento (LGPD) ---------- */
  var consent = null;
  try { consent = JSON.parse(store.get('ms_consent') || 'null'); } catch (e) {}
  var banner = document.getElementById('cookie');
  function applyConsent(c) {
    consent = c;
    store.set('ms_consent', JSON.stringify(c));
    if (banner) banner.hidden = true;
    if (c.stats) {
      if (!store.get('ms_vid')) store.set('ms_vid', randomId());
      loadGa();
    }
  }
  function randomId() {
    var a = new Uint8Array(16);
    (window.crypto || window.msCrypto).getRandomValues(a);
    return Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
  }
  function loadGa() {
    var id = window.MS && window.MS.ga4;
    if (!id || window.gtag) return;
    var s = document.createElement('script');
    s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', id, { anonymize_ip: true });
  }
  if (banner) {
    if (!consent) banner.hidden = false; else if (consent.stats) loadGa();
    banner.addEventListener('click', function (e) {
      var b = e.target.closest('[data-cookie]');
      if (!b) return;
      var act = b.getAttribute('data-cookie');
      var opts = banner.querySelector('.cookie-opts');
      if (act === 'custom') {
        if (opts.hidden) { opts.hidden = false; b.textContent = 'Salvar escolhas'; return; }
        applyConsent({ stats: banner.querySelector('[name=stats]').checked, marketing: banner.querySelector('[name=marketing]').checked, ts: Date.now() });
      } else {
        applyConsent({ stats: act === 'all', marketing: act === 'all', ts: Date.now() });
      }
    });
  }
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-cookie-prefs]') && banner) {
      banner.hidden = false;
      if (consent) {
        banner.querySelector('[name=stats]').checked = !!consent.stats;
        banner.querySelector('[name=marketing]').checked = !!consent.marketing;
      }
      banner.querySelector('.cookie-opts').hidden = false;
    }
  });

  /* ---------- Analytics próprio (sem cookies) ---------- */
  var path = location.pathname;
  var skip = path.indexOf('/admin') === 0 || navigator.webdriver;
  var pv = null, start = Date.now(), visibleMs = 0, lastShow = Date.now(), maxScroll = 0, sent75 = false;
  function post(url, data, beacon) {
    var body = JSON.stringify(data);
    if (beacon && navigator.sendBeacon) { navigator.sendBeacon(url, new Blob([body], { type: 'application/json' })); return Promise.resolve(); }
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true, credentials: 'same-origin' })
      .then(function (r) { return r.status === 200 ? r.json() : {}; }).catch(function () { return {}; });
  }
  window.msTrack = function (name, label, value) {
    if (skip) return;
    post('/api/e', { n: name, l: String(label || '').slice(0, 250), p: path, v: value || 0 }, true);
    if (window.gtag) window.gtag('event', name, { event_label: label });
  };
  if (!skip) {
    post('/api/t', {
      u: location.href, t: document.title, r: document.referrer, w: screen.width,
      l: navigator.language, id: consent && consent.stats ? store.get('ms_vid') : null
    }).then(function (r) { if (r && r.pv) pv = r.pv; });
    window.addEventListener('scroll', function () {
      var h = document.documentElement;
      var pct = Math.round((h.scrollTop + window.innerHeight) / h.scrollHeight * 100);
      if (pct > maxScroll) maxScroll = Math.min(100, pct);
      if (!sent75 && maxScroll >= 75) { sent75 = true; window.msTrack('scroll_75', path); }
    }, { passive: true });
    var flush = function () {
      if (!pv) return;
      visibleMs += Date.now() - lastShow; lastShow = Date.now();
      post('/api/p', { pv: pv, d: visibleMs, s: maxScroll }, true);
    };
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'hidden') flush(); else lastShow = Date.now();
    });
    window.addEventListener('pagehide', flush);
  }

  /* Cliques: anúncios, CTAs e links externos */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a, button');
    if (!a) return;
    var ad = a.getAttribute('data-ad-click');
    if (ad) { window.msTrack('ad_click', ad); return; }
    var ev = a.getAttribute('data-ev');
    if (ev) { window.msTrack(ev, a.getAttribute('data-ev-label') || a.textContent.trim().slice(0, 80)); }
    if (a.tagName === 'A' && a.host && a.host !== location.host && !ev) window.msTrack('outbound_click', a.href);
  });

  /* Visualização de anúncio: 50% visível por 1s */
  if ('IntersectionObserver' in window) {
    var timers = new WeakMap();
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          timers.set(en.target, setTimeout(function () { window.msTrack('ad_view', en.target.getAttribute('data-ad')); io.unobserve(en.target); }, 1000));
        } else clearTimeout(timers.get(en.target));
      });
    }, { threshold: 0.5 });
    document.querySelectorAll('[data-ad]').forEach(function (el) { io.observe(el); });

    var rv = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); rv.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px' });
    document.querySelectorAll('.reveal').forEach(function (el) { rv.observe(el); });

    /* Sumário ativo */
    var links = document.querySelectorAll('.toc a');
    if (links.length) {
      var map = {};
      links.forEach(function (l) { map[l.getAttribute('href').slice(1)] = l; });
      var to = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) { links.forEach(function (l) { l.classList.remove('active'); }); if (map[en.target.id]) map[en.target.id].classList.add('active'); }
        });
      }, { rootMargin: '-90px 0px -70% 0px' });
      Object.keys(map).forEach(function (id) { var h = document.getElementById(id); if (h) to.observe(h); });
    }
  } else {
    document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
  }

  /* ---------- Formulários AJAX (newsletter) ---------- */
  document.querySelectorAll('form[data-ajax]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = f.querySelector('.form-msg');
      var btn = f.querySelector('[type=submit]');
      var email = f.querySelector('[name=email]');
      if (email && !email.checkValidity()) { msg.className = 'form-msg bad'; msg.textContent = 'Confira o e-mail informado.'; email.focus(); return; }
      var c = f.querySelector('[name=consent]');
      if (c && !c.checked) { msg.className = 'form-msg bad'; msg.textContent = 'Marque o aceite da Política de Privacidade para continuar.'; return; }
      btn.disabled = true; var t = btn.textContent; btn.textContent = 'Enviando…';
      fetch(f.action, { method: 'POST', body: new FormData(f), headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (r) {
          msg.className = 'form-msg ' + (r.ok ? 'ok' : 'bad');
          msg.textContent = r.msg || (r.ok ? 'Pronto!' : 'Não foi possível enviar.');
          if (r.ok) f.reset();
        })
        .catch(function () { msg.className = 'form-msg bad'; msg.textContent = 'Falha de conexão. Tente novamente.'; })
        .finally(function () { btn.disabled = false; btn.textContent = t; });
    });
  });

  /* Compartilhar: copiar link */
  document.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var u = b.getAttribute('data-copy');
      (navigator.clipboard ? navigator.clipboard.writeText(u) : Promise.reject()).then(function () {
        b.textContent = 'Link copiado'; window.msTrack('share', 'copiar');
      }).catch(function () { prompt('Copie o link:', u); });
    });
  });
  document.querySelectorAll('[data-share]').forEach(function (a) {
    a.addEventListener('click', function () { window.msTrack('share', a.getAttribute('data-share')); });
  });
})();
