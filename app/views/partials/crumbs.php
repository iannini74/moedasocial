<nav class="crumbs" aria-label="Você está em">
  <a href="/">Início</a>
  <?php foreach ($items as $label => $href): ?>
    <span><?php if ($href): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><?= e($label) ?><?php endif; ?></span>
  <?php endforeach; ?>
</nav>
