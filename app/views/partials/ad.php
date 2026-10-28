<?php
/**
 * Anúncio do patrocinador (Moeda Nobre). Sempre sinalizado como "Anúncio" (CDC art. 36; CONAR).
 * @var string $placement  @var string $format banner|card|inline|mini|wide
 */
$ads = [
    ['h' => 'Moeda social digital para prefeituras', 't' => 'Benefícios pagos por aplicativo e aceitos apenas no comércio credenciado do município, com lastro 1:1 em reais.', 'c' => 'Conheça a Moeda Nobre'],
    ['h' => 'Pagamento por QR Code, sem máquina de cartão', 't' => 'O comércio recebe pelo celular e resgata em reais na própria conta. Sem custo de tecnologia para a prefeitura.', 'c' => 'Ver como funciona'],
    ['h' => 'Cada transação registrada e auditável', 't' => 'Trilha de auditoria completa e relatórios prontos para o Tribunal de Contas e a LRF.', 'c' => 'Agende uma demonstração'],
    ['h' => 'Reforma Tributária: o IBS vai para onde o consumo acontece', 't' => 'A Moeda Nobre direciona benefícios municipais para o comércio local e ajuda a proteger a base de arrecadação.', 'c' => 'Entenda o impacto'],
    ['h' => 'Credenciamento gratuito para o comércio local', 't' => 'Cadastro simples, recebimento pelo celular e repasse direto na conta do lojista.', 'c' => 'Quero conhecer'],
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
