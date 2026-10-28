<?php
declare(strict_types=1);

final class Api
{
    private static function body(): array
    {
        $raw = file_get_contents('php://input', false, null, 0, 20000) ?: '';
        $d = json_decode($raw, true);
        return is_array($d) ? $d : $_POST;
    }

    private static function sameOrigin(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        if ($origin === '') {
            return true; // sendBeacon pode omitir
        }
        $host = parse_url($origin, PHP_URL_HOST);
        return $host === ($_SERVER['HTTP_HOST'] ?? '') || $host === parse_url(cfg('site.url'), PHP_URL_HOST);
    }

    public static function track(): never
    {
        if (!self::sameOrigin()) {
            json_out(['ok' => false], 403);
        }
        try {
            json_out(Analytics::collect(self::body()));
        } catch (Throwable $e) {
            log_line('analytics', $e->getMessage());
            json_out(['ok' => false]);
        }
    }

    public static function ping(): never
    {
        $d = self::body();
        try {
            Analytics::ping((string) ($d['pv'] ?? ''), (int) ($d['d'] ?? 0), (int) ($d['s'] ?? 0));
        } catch (Throwable) {
        }
        http_response_code(204);
        exit;
    }

    public static function event(): never
    {
        if (!self::sameOrigin()) {
            json_out(['ok' => false], 403);
        }
        $d = self::body();
        try {
            Analytics::event((string) ($d['n'] ?? ''), (string) ($d['l'] ?? ''), (string) ($d['p'] ?? ''), (int) ($d['v'] ?? 0));
        } catch (Throwable) {
        }
        http_response_code(204);
        exit;
    }

    /** Guarda o diagnóstico gerado (dados agregados do município, sem dados pessoais). */
    public static function diagnostic(): never
    {
        if (!self::sameOrigin() || rate_limited('diag:' . ip_hash(), 30, 3600)) {
            json_out(['ok' => false], 429);
        }
        $d = self::body();
        $inputs = array_slice(is_array($d['inputs'] ?? null) ? $d['inputs'] : [], 0, 40);
        $clean = [];
        foreach ($inputs as $k => $v) {
            if (preg_match('/^[a-z_]{1,40}$/', (string) $k)) {
                $clean[$k] = is_scalar($v) ? mb_substr((string) $v, 0, 120) : null;
            }
        }
        $id = Db::insert('diagnostics', [
            'created_at' => time(),
            'municipality' => mb_substr(strip_tags((string) ($clean['municipio'] ?? '')), 0, 150),
            'uf' => strtoupper(mb_substr((string) ($clean['uf'] ?? ''), 0, 2)),
            'population' => (int) ($clean['populacao'] ?? 0),
            'score' => (int) ($d['score'] ?? 0),
            'data' => json_encode(['inputs' => $clean, 'results' => array_slice((array) ($d['results'] ?? []), 0, 40)], JSON_UNESCAPED_UNICODE),
            'visitor' => substr(hash_hmac('sha256', client_ip() . ($_SERVER['HTTP_USER_AGENT'] ?? '') . date('Y-m-d'), cfg('secret') . 'visitor'), 0, 40),
        ]);
        json_out(['ok' => true, 'id' => 'DX-' . date('Y') . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT)]);
    }

    public static function text(string $body, string $type = 'text/plain'): never
    {
        header("Content-Type: $type; charset=utf-8");
        header('Cache-Control: public, max-age=3600');
        echo $body;
        exit;
    }

    /** Disparo de rotinas por URL (para cron externo ou Hostinger "wget"). */
    public static function cron(string $token, string $job): never
    {
        $expected = (string) cfg('cron_token');
        if ($expected === '' || !hash_equals($expected, $token)) {
            not_found();
        }
        ignore_user_abort(true);
        set_time_limit(900);
        $out = match ($job) {
            'news' => News::refresh(),
            'blog' => BlogAgent::run(),
            'newsletter' => ['sent' => Jobs::pendingNewsletters()],
            'manutencao' => Jobs::maintenance(),
            default => ['erro' => 'rotina desconhecida'],
        };
        json_out(['job' => $job, 'result' => $out]);
    }
}
