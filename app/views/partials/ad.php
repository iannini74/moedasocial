<?php
/**
 * Anúncio do patrocinador (Moeda Nobre). Sempre sinalizado como "Anúncio" (CDC art. 36; CONAR).
 * @var string $placement  @var string $format banner|card|inline|mini|wide
 */
$ads = [
    ['h' => 'A riqueza que fica em casa.', 't' => 'Moeda social digital municipal: o benefício circula no comércio da sua cidade e fortalece cada elo da economia local.', 'c' => 'Conheça a Moeda Nobre'],
    ['h' => 'O dinheiro da sua cidade não precisa de passaporte.', 't' => 'App com pagamento por QR Code. Sem maquininha, sem cartão e sem custo de tecnologia para a prefeitura.', 'c' => 'Ver como funciona'],
    ['h' => 'Transparência total para sua gestão.', 't' => 'Cada transação rastreada de ponta a ponta, com trilha de auditoria e relatórios prontos para o Tribunal de Contas.', 'c' => 'Agende uma demonstração'],
    ['h' => 'Reforma Tributária: onde o cidadão gasta, importa.', 't' => 'Com o IBS no destino, manter o consumo na cidade é manter receita. A Moeda Nobre ajuda o dinheiro a não viajar.', 'c' => 'Entenda o impacto'],
    ['h' => 'Comércio local credenciado de graça.', 't' => 'O celular vira cartão e maquininha. Repasse direto na conta do lojista, com lastro 1:1 em reais.', 'c' => 'Quero conhecer'],
];
static $adSeq = null;
$adSeq ??= random_int(0, count($ads) - 1);
$ad = $ads[$adSeq++ % count($ads)];
$href = sponsor_url($placement);
?>
<aside class="ad ad-<?= e($format) ?>" data-ad="<?= e($placement) ?>" aria-label="Anúncio">
  <span class="ad-label">Anúncio</span>
  <a class="ad-link" href="<?= e($href) ?>" target="_blank" rel="sponsored noopener" data-ad-click="<?= e($placement) ?>">
    <span class="ad-brand"><img src="/assets/img/moeda-nobre-96.png" alt="" width="28" height="28" loading="lazy"> Moeda Nobre</span>
    <strong class="ad-h"><?= e($ad['h']) ?></strong>
    <?php if ($format !== 'mini'): ?><span class="ad-t"><?= e($ad['t']) ?></span><?php endif; ?>
    <span class="ad-cta"><?= e($ad['c']) ?> →</span>
  </a>
</aside>
