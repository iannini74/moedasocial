/* Tooltip + crosshair do gráfico de linhas do painel */
(function () {
  var svg = document.querySelector('.linechart');
  if (!svg) return;
  var pts = JSON.parse(svg.getAttribute('data-points'));
  var g = JSON.parse(svg.getAttribute('data-geom'));
  var tip = svg.parentNode.querySelector('.tip');
  var xh = svg.querySelector('.xhair');
  function fmt(n) { return Number(n).toLocaleString('pt-BR'); }
  svg.addEventListener('mousemove', function (e) {
    var r = svg.getBoundingClientRect();
    var x = (e.clientX - r.left) / r.width * g.w;
    var span = g.w - g.pl - g.pr;
    var i = g.n > 1 ? Math.round((x - g.pl) / span * (g.n - 1)) : 0;
    i = Math.max(0, Math.min(g.n - 1, i));
    var px = g.pl + (g.n > 1 ? i / (g.n - 1) : .5) * span;
    xh.setAttribute('x1', px); xh.setAttribute('x2', px); xh.setAttribute('visibility', 'visible');
    tip.hidden = false;
    tip.style.left = (px / g.w * r.width) + 'px';
    tip.style.top = (e.clientY - r.top) + 'px';
    tip.innerHTML = '<b>' + pts[i][0] + '</b>Visualizações: ' + fmt(pts[i][1]) + '<br>Sessões: ' + fmt(pts[i][2]);
  });
  svg.addEventListener('mouseleave', function () { tip.hidden = true; xh.setAttribute('visibility', 'hidden'); });
})();
