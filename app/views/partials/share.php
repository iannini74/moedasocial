<?php $u = url($path, true); $t = rawurlencode($title); $ue = rawurlencode($u . '?utm_source=compartilhar'); ?>
<div class="share">
  <span>Compartilhe:</span>
  <a href="https://wa.me/?text=<?= $t ?>%20<?= $ue ?>" target="_blank" rel="noopener" data-share="whatsapp">WhatsApp</a>
  <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $ue ?>" target="_blank" rel="noopener" data-share="linkedin">LinkedIn</a>
  <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $ue ?>" target="_blank" rel="noopener" data-share="facebook">Facebook</a>
  <a href="https://twitter.com/intent/tweet?text=<?= $t ?>&url=<?= $ue ?>" target="_blank" rel="noopener" data-share="x">X</a>
  <a href="mailto:?subject=<?= $t ?>&body=<?= $ue ?>" data-share="email">E-mail</a>
  <button type="button" data-copy="<?= e($u) ?>">Copiar link</button>
</div>
