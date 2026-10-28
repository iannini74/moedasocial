<?php /** @var array $p @var int $k */ $tags = array_filter(array_map('trim', explode(',', (string) $p['tags']))); ?>
<a class="card post-card reveal" href="/blog/<?= e($p['slug']) ?>">
  <div class="post-cover c<?= ((int) $p['id']) % 4 ?>" aria-hidden="true"></div>
  <div class="post-body">
    <?php if ($tags): ?><div><span class="tag tag-green"><?= e(reset($tags)) ?></span></div><?php endif; ?>
    <h3><?= e($p['title']) ?></h3>
    <p><?= e(excerpt((string) $p['description'], 150)) ?></p>
    <div class="news-meta"><span><?= date_br((int) $p['published_at']) ?></span><span>· <?= (int) $p['reading_min'] ?> min de leitura</span></div>
  </div>
</a>
