<?php /** @var array $n */ $ts = (int) $n['published_at']; ?>
<li class="news-item">
  <div class="news-date"><b><?= date('d', $ts) ?></b><?= ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'][(int) date('n', $ts) - 1] ?></div>
  <div>
    <div class="news-meta">
      <a class="tag tag-green" href="/noticias?categoria=<?= e($n['category']) ?>"><?= e(News::CATEGORIES[$n['category']] ?? 'Notícia') ?></a>
      <span><?= e($n['source']) ?></span><span>· <?= e(time_ago($ts)) ?></span>
      <?php if (!empty($n['pinned'])): ?><span class="tag tag-blue">Destaque</span><?php endif; ?>
    </div>
    <h3><a href="<?= e($n['url']) ?>" target="_blank" rel="noopener nofollow"><?= e($n['title']) ?></a></h3>
    <?php if (!empty($n['summary']) && empty($compact)): ?><p><?= e(excerpt($n['summary'], 180)) ?></p><?php endif; ?>
  </div>
</li>
