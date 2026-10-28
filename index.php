<?php
declare(strict_types=1);

/**
 * Portal Moeda Social — controlador frontal.
 * © TribeX (tribex.com.br). Todos os direitos reservados.
 */
// Servidor embutido do PHP (desenvolvimento): entrega arquivos estáticos diretamente
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
    && !preg_match('#^/(app|content|cron|storage|vendor)/#', $_SERVER['REQUEST_URI'])) {
    return false;
}

require __DIR__ . '/app/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$path = $path !== '/' ? rtrim($path, '/') : '/';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

if ($method === 'GET' && !str_starts_with($path, '/api/') && !str_starts_with($path, '/assets/')) {
    Analytics::logCrawler($ua, $path);
}

// Rotinas de manutenção executadas após a resposta (contingência caso o cron não esteja configurado)
register_shutdown_function(function () {
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    try {
        if (News::due()) {
            News::refresh();
        }
    } catch (Throwable $e) {
        log_line('news', 'Erro: ' . $e->getMessage());
    }
});

Blog::seed();

$routes = [
    'GET /'                              => fn() => Pages::home(),
    'GET /estudos'                       => fn() => Pages::studies(),
    'GET /estudos/{slug}'                => fn($slug) => Pages::study($slug),
    'GET /por-que-adotar-moeda-social'   => fn() => Pages::why(),
    'GET /diagnostico'                   => fn() => Pages::diagnostic(),
    'GET /noticias'                      => fn() => Pages::news(),
    'GET /blog'                          => fn() => Pages::blog(),
    'GET /blog/{slug}'                   => fn($slug) => Pages::post($slug),
    'GET /contato'                       => fn() => Pages::contact(),
    'POST /contato'                      => fn() => Pages::contactSubmit(),
    'GET /perguntas-frequentes'          => fn() => Pages::faq(),
    'GET /glossario'                     => fn() => Pages::glossary(),
    'GET /sobre'                         => fn() => Pages::static('sobre'),
    'GET /privacidade'                   => fn() => Pages::static('privacidade'),
    'GET /cookies'                       => fn() => Pages::static('cookies'),
    'GET /termos'                        => fn() => Pages::static('termos'),
    'POST /newsletter'                   => fn() => Pages::newsletterSubmit(),
    'GET /newsletter/confirmar'          => fn() => Pages::newsletterConfirm(),
    'GET /newsletter/cancelar'           => fn() => Pages::newsletterCancel(),
    'POST /newsletter/cancelar'          => fn() => Pages::newsletterCancel(),
    'POST /api/t'                        => fn() => Api::track(),
    'POST /api/p'                        => fn() => Api::ping(),
    'POST /api/e'                        => fn() => Api::event(),
    'POST /api/diagnostico'              => fn() => Api::diagnostic(),
    'GET /sitemap.xml'                   => fn() => Api::text(Seo::sitemap(), 'application/xml'),
    'GET /robots.txt'                    => fn() => Api::text(Seo::robots()),
    'GET /llms.txt'                      => fn() => Api::text(Seo::llmsTxt(), 'text/markdown'),
    'GET /llms-full.txt'                 => fn() => Api::text(Seo::llmsFull(), 'text/markdown'),
    'GET /feed.xml'                      => fn() => Api::text(Seo::rss(), 'application/rss+xml'),
    'GET /cron/{token}/{job}'            => fn($token, $job) => Api::cron($token, $job),
    'GET /admin'                         => fn() => Admin::dashboard(),
    'GET /admin/login'                   => fn() => Admin::login(),
    'POST /admin/login'                  => fn() => Admin::login(),
    'GET /admin/sair'                    => fn() => Admin::logout(),
    'GET /admin/{section}'               => fn($s) => Admin::section($s),
    'POST /admin/{section}'              => fn($s) => Admin::action($s),
    'GET /admin/exportar/{type}'         => fn($t) => Admin::export($t),
];

// Chave IndexNow servida na raiz
$key = cfg('seo.indexnow_key');
if ($key && $path === "/$key.txt") {
    Api::text($key);
}

foreach ($routes as $route => $handler) {
    [$m, $pattern] = explode(' ', $route, 2);
    if ($m !== $method) {
        continue;
    }
    $regex = '#^' . preg_replace('#\{[a-z]+\}#', '([a-z0-9\-\.]+)', $pattern) . '$#';
    if (preg_match($regex, $path, $args)) {
        array_shift($args);
        $handler(...$args);
        exit;
    }
}

not_found();
