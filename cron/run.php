<?php
/**
 * Rotinas agendadas — execute pela linha de comando (Cron Jobs da Hostinger):
 *
 *   php /home/USUARIO/domains/moedasocial.com/public_html/cron/run.php news        (a cada 6 horas)
 *   php /home/USUARIO/domains/moedasocial.com/public_html/cron/run.php blog        (1x por dia; publica a cada 15 dias)
 *   php /home/USUARIO/domains/moedasocial.com/public_html/cron/run.php newsletter  (a cada hora; envia em lotes)
 *   php /home/USUARIO/domains/moedasocial.com/public_html/cron/run.php manutencao  (1x por semana)
 *   php .../cron/run.php blog --force                                               (gera um artigo agora)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
set_time_limit(1200);

$job = $argv[1] ?? '';
$force = in_array('--force', $argv, true);
$start = microtime(true);
$result = match ($job) {
    'news' => News::refresh(),
    'blog' => BlogAgent::run($force),
    'newsletter' => ['enviados' => Jobs::pendingNewsletters()],
    'manutencao' => Jobs::maintenance(),
    default => null,
};
if ($result === null) {
    fwrite(STDERR, "Uso: php cron/run.php news|blog|newsletter|manutencao [--force]\n");
    exit(1);
}
$line = sprintf('[%s] %s (%.1fs): %s', date('Y-m-d H:i:s'), $job, microtime(true) - $start, json_encode($result, JSON_UNESCAPED_UNICODE));
echo $line, PHP_EOL;
log_line('cron', $line);
