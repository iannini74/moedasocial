<?php
declare(strict_types=1);

function cfg(string $path, mixed $default = null): mixed
{
    $v = $GLOBALS['config'];
    foreach (explode('.', $path) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) {
            return $default;
        }
        $v = $v[$k];
    }
    return $v;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/', bool $absolute = false): string
{
    $path = '/' . ltrim($path, '/');
    return $absolute ? rtrim(cfg('site.url'), '/') . $path : $path;
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr(md5((string) filemtime($file)), 0, 8) : '1';
    return '/assets/' . ltrim($path, '/') . '?v=' . $v;
}

function sponsor_url(string $placement): string
{
    $sep = str_contains(cfg('sponsor.url'), '?') ? '&' : '?';
    return cfg('sponsor.url') . $sep . cfg('sponsor.utm') . '&utm_content=' . rawurlencode($placement);
}

/** Renderiza uma view dentro do layout. */
function view(string $name, array $data = [], ?string $layout = 'layout'): void
{
    $data['__view'] = $name;
    extract($data, EXTR_SKIP);
    ob_start();
    require APP . "/views/$name.php";
    $content = ob_get_clean();
    if ($layout === null) {
        echo $content;
        return;
    }
    require APP . "/views/$layout.php";
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP . "/views/partials/$name.php";
}

/** Bloco de anúncio do patrocinador, sempre sinalizado. */
function ad(string $placement, string $format = 'banner'): void
{
    partial('ad', ['placement' => $placement, 'format' => $format]);
}

function redirect(string $to, int $code = 302): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, int $max = 2000): string
{
    $v = $_POST[$key] ?? '';
    if (is_array($v)) {
        $v = implode(', ', array_map('strval', $v));
    }
    $v = trim(strip_tags((string) $v));
    return mb_substr($v, 0, $max);
}

function input_list(string $key, array $allowed): array
{
    $v = $_POST[$key] ?? [];
    $v = is_array($v) ? $v : [$v];
    return array_values(array_intersect(array_map('strval', $v), $allowed));
}

function client_ip(): string
{
    // Hostinger usa proxy reverso; confiar apenas em cabeçalhos conhecidos
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function ip_hash(?string $ip = null): string
{
    return hash_hmac('sha256', $ip ?? client_ip(), cfg('secret') . 'ip');
}

/** Token CSRF sem sessão: HMAC(timestamp). Valida idade mínima (anti-bot) e máxima. */
function form_token(): string
{
    $ts = (string) time();
    return $ts . '.' . hash_hmac('sha256', $ts, cfg('secret') . 'form');
}

function form_token_valid(?string $token, int $minAge = 2, int $maxAge = 7200): bool
{
    if (!$token || !str_contains($token, '.')) {
        return false;
    }
    [$ts, $sig] = explode('.', $token, 2);
    if (!ctype_digit($ts) || !hash_equals(hash_hmac('sha256', $ts, cfg('secret') . 'form'), $sig)) {
        return false;
    }
    $age = time() - (int) $ts;
    return $age >= $minAge && $age <= $maxAge;
}

/** Limita tentativas por chave dentro de uma janela (segundos). */
function rate_limited(string $key, int $max, int $window): bool
{
    $k = substr($key, 0, 120);
    $now = time();
    Db::run('DELETE FROM rate_limits WHERE ts < ?', [$now - 86400]);
    $n = (int) Db::value('SELECT COUNT(*) FROM rate_limits WHERE k = ? AND ts > ?', [$k, $now - $window]);
    if ($n >= $max) {
        return true;
    }
    Db::insert('rate_limits', ['k' => $k, 'ts' => $now]);
    return false;
}

function slugify(string $s): string
{
    $s = mb_strtolower($s);
    $s = strtr($s, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
    ]);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim(substr($s, 0, 90), '-');
}

function markdown(string $md, bool $safe = false): string
{
    static $pd = null;
    $pd ??= new Parsedown();
    $pd->setSafeMode($safe);
    $html = $pd->text($md);
    // Links externos: nova aba + rel seguro
    $html = preg_replace_callback('/<a href="(https?:\/\/[^"]+)"/', function ($m) {
        $host = parse_url($m[1], PHP_URL_HOST) ?: '';
        $own = str_ends_with($host, parse_url(cfg('site.url'), PHP_URL_HOST));
        return $own ? $m[0] : '<a href="' . $m[1] . '" target="_blank" rel="noopener"';
    }, $html);
    // IDs nos títulos h2/h3 para sumário e âncoras
    return preg_replace_callback('/<(h[23])>(.*?)<\/h[23]>/', function ($m) {
        $id = slugify(strip_tags(html_entity_decode($m[2])));
        return "<{$m[1]} id=\"$id\">{$m[2]}</{$m[1]}>";
    }, $html);
}

/** Extrai sumário (h2) de HTML renderizado. */
function toc(string $html): array
{
    preg_match_all('/<h2 id="([^"]+)">(.*?)<\/h2>/', $html, $m, PREG_SET_ORDER);
    return array_map(fn($x) => ['id' => $x[1], 'title' => strip_tags($x[2])], $m);
}

function reading_minutes(string $text): int
{
    return max(1, (int) round(str_word_count(strip_tags($text), 0, 'áàâãéêíóôõúçÁÀÂÃÉÊÍÓÔÕÚÇ') / 200));
}

function date_br(int|string|null $ts, bool $withTime = false): string
{
    if (!$ts) {
        return '';
    }
    $ts = is_numeric($ts) ? (int) $ts : strtotime((string) $ts);
    $months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    $s = date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    return $withTime ? $s . ', ' . date('H:i', $ts) : $s;
}

function date_long_br(int $ts): string
{
    $months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    return date('j', $ts) . ' de ' . $months[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

function time_ago(int $ts): string
{
    $d = time() - $ts;
    return match (true) {
        $d < 3600   => 'há ' . max(1, intdiv($d, 60)) . ' min',
        $d < 86400  => 'há ' . intdiv($d, 3600) . ' h',
        $d < 172800 => 'ontem',
        $d < 604800 => 'há ' . intdiv($d, 86400) . ' dias',
        default     => date_br($ts),
    };
}

function excerpt(string $text, int $len = 160): string
{
    $t = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
    return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len - 1)) . '…' : $t;
}

function money_br(float $v, int $dec = 0): string
{
    return 'R$ ' . number_format($v, $dec, ',', '.');
}

function ufs(): array
{
    return ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
}

function log_line(string $file, string $msg): void
{
    @file_put_contents(STORAGE . "/$file.log", '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function not_found(): never
{
    http_response_code(404);
    view('pages/404', ['meta' => ['title' => 'Página não encontrada', 'robots' => 'noindex']]);
    exit;
}

function icon(string $name, string $class = 'icon', int $size = 24): string
{
    return Icons::svg($name, $class, $size);
}
