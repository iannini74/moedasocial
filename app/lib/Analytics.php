<?php
declare(strict_types=1);

/**
 * Analytics próprio, sem cookies e compatível com a LGPD:
 * - IP nunca é armazenado (apenas hash HMAC com sal diário para contar visitantes únicos);
 * - geolocalização aproximada (cidade/estado/país) consultada no servidor e cacheada por hash;
 * - robôs de busca e de IA são registrados à parte (crawler_hits) para medir indexação.
 */
final class Analytics
{
    public const EVENTS = [
        'ad_view', 'ad_click', 'cta_click', 'outbound_click', 'newsletter_signup', 'contact_submit',
        'simulator_start', 'simulator_run', 'simulator_pdf', 'simulator_print', 'share', 'search', 'scroll_75',
    ];

    /** Robôs conhecidos: [padrão no user-agent, nome, família] */
    private const BOTS = [
        ['GPTBot', 'GPTBot (OpenAI)', 'ia'], ['OAI-SearchBot', 'OAI-SearchBot (ChatGPT Search)', 'ia'],
        ['ChatGPT-User', 'ChatGPT-User', 'ia'], ['ClaudeBot', 'ClaudeBot (Anthropic)', 'ia'],
        ['Claude-User', 'Claude-User', 'ia'], ['Claude-SearchBot', 'Claude-SearchBot', 'ia'],
        ['anthropic-ai', 'Anthropic', 'ia'], ['PerplexityBot', 'PerplexityBot', 'ia'],
        ['Perplexity-User', 'Perplexity-User', 'ia'], ['Google-Extended', 'Google-Extended (Gemini)', 'ia'],
        ['GoogleOther', 'GoogleOther', 'ia'], ['Applebot-Extended', 'Applebot-Extended', 'ia'],
        ['meta-externalagent', 'Meta AI', 'ia'], ['Bytespider', 'Bytespider (TikTok)', 'ia'],
        ['CCBot', 'CCBot (Common Crawl)', 'ia'], ['cohere-ai', 'Cohere', 'ia'], ['DuckAssistBot', 'DuckAssist', 'ia'],
        ['MistralAI-User', 'Mistral', 'ia'], ['YouBot', 'You.com', 'ia'], ['Amazonbot', 'Amazonbot', 'ia'],
        ['Googlebot', 'Googlebot', 'busca'], ['bingbot', 'Bingbot', 'busca'], ['Applebot', 'Applebot', 'busca'],
        ['YandexBot', 'YandexBot', 'busca'], ['DuckDuckBot', 'DuckDuckBot', 'busca'], ['Baiduspider', 'Baidu', 'busca'],
        ['facebookexternalhit', 'Facebook (preview)', 'social'], ['LinkedInBot', 'LinkedIn (preview)', 'social'],
        ['WhatsApp', 'WhatsApp (preview)', 'social'], ['Twitterbot', 'X/Twitter (preview)', 'social'],
        ['TelegramBot', 'Telegram (preview)', 'social'], ['Slackbot', 'Slack (preview)', 'social'],
        ['AhrefsBot', 'Ahrefs', 'seo'], ['SemrushBot', 'Semrush', 'seo'], ['MJ12bot', 'Majestic', 'seo'],
        ['DotBot', 'Moz', 'seo'],
    ];

    private const AI_REFERRERS = [
        'chatgpt.com' => 'ChatGPT', 'chat.openai.com' => 'ChatGPT', 'perplexity.ai' => 'Perplexity',
        'claude.ai' => 'Claude', 'gemini.google.com' => 'Gemini', 'bard.google.com' => 'Gemini',
        'copilot.microsoft.com' => 'Copilot', 'you.com' => 'You.com', 'poe.com' => 'Poe',
        'chat.deepseek.com' => 'DeepSeek', 'meta.ai' => 'Meta AI', 'grok.com' => 'Grok', 'chat.mistral.ai' => 'Mistral',
    ];
    private const SEARCH = ['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'ecosia.', 'yandex.', 'baidu.', 'search.brave.', 'startpage.'];
    private const SOCIAL = [
        'facebook.' => 'Facebook', 'fb.' => 'Facebook', 'instagram.' => 'Instagram', 'linkedin.' => 'LinkedIn',
        'lnkd.in' => 'LinkedIn', 't.co' => 'X/Twitter', 'twitter.' => 'X/Twitter', 'x.com' => 'X/Twitter',
        'whatsapp.' => 'WhatsApp', 'wa.me' => 'WhatsApp', 'youtube.' => 'YouTube', 'tiktok.' => 'TikTok',
        't.me' => 'Telegram', 'telegram.' => 'Telegram', 'reddit.' => 'Reddit', 'pinterest.' => 'Pinterest',
        'threads.' => 'Threads', 'bsky.' => 'Bluesky',
    ];

    public static function detectBot(string $ua): ?array
    {
        foreach (self::BOTS as [$needle, $name, $family]) {
            if (stripos($ua, $needle) !== false) {
                return ['name' => $name, 'family' => $family];
            }
        }
        if (preg_match('/bot|crawl|spider|slurp|headless|lighthouse|curl|wget|python-requests|httpclient|scrapy/i', $ua)) {
            return ['name' => 'Outros robôs', 'family' => 'outros'];
        }
        return null;
    }

    /** Registra acesso de robôs (chamado pelo roteador em toda requisição GET de página). */
    public static function logCrawler(string $ua, string $path): void
    {
        $bot = self::detectBot($ua);
        if (!$bot) {
            return;
        }
        try {
            Db::insert('crawler_hits', [
                'ts' => time(), 'day' => date('Y-m-d'), 'bot' => $bot['name'],
                'family' => $bot['family'], 'path' => mb_substr($path, 0, 255),
            ]);
        } catch (Throwable) {
        }
    }

    public static function collect(array $in): array
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($ua === '' || self::detectBot($ua)) {
            return ['ok' => false];
        }
        $now = time();
        $pageUrl = (string) ($in['u'] ?? '');
        $path = parse_url($pageUrl, PHP_URL_PATH) ?: '/';
        if (str_starts_with($path, '/admin')) {
            return ['ok' => false];
        }
        parse_str((string) parse_url($pageUrl, PHP_URL_QUERY), $q);

        $visitor = !empty($in['id']) && preg_match('/^[a-f0-9]{16,40}$/', (string) $in['id'])
            ? 'c' . hash_hmac('sha256', (string) $in['id'], cfg('secret'))   // com consentimento
            : hash_hmac('sha256', client_ip() . $ua . date('Y-m-d'), cfg('secret') . 'visitor');
        $visitor = substr($visitor, 0, 40);

        // Sessão: mesma pessoa com atividade nos últimos 30 minutos
        $last = Db::one('SELECT session, ts FROM pageviews WHERE visitor = ? ORDER BY id DESC LIMIT 1', [$visitor]);
        $isEntry = !$last || ($now - (int) $last['ts']) > 1800;
        $session = $isEntry ? bin2hex(random_bytes(12)) : $last['session'];

        [$refType, $refDomain] = self::classifyReferrer((string) ($in['r'] ?? ''), $q);
        $geo = self::geo();
        [$device, $browser, $os] = self::parseUa($ua, (int) ($in['w'] ?? 0));

        $id = Db::insert('pageviews', [
            'ts' => $now, 'day' => date('Y-m-d', $now), 'hour' => (int) date('G', $now), 'dow' => (int) date('w', $now),
            'path' => mb_substr($path, 0, 255), 'section' => self::section($path),
            'title' => mb_substr(strip_tags((string) ($in['t'] ?? '')), 0, 255),
            'referrer' => mb_substr((string) ($in['r'] ?? ''), 0, 500), 'ref_domain' => $refDomain, 'ref_type' => $refType,
            'utm_source' => mb_substr((string) ($q['utm_source'] ?? ''), 0, 100) ?: null,
            'utm_medium' => mb_substr((string) ($q['utm_medium'] ?? ''), 0, 100) ?: null,
            'utm_campaign' => mb_substr((string) ($q['utm_campaign'] ?? ''), 0, 150) ?: null,
            'visitor' => $visitor, 'session' => $session, 'is_entry' => $isEntry ? 1 : 0,
            'country' => $geo['country'], 'country_code' => $geo['country_code'], 'region' => $geo['region'], 'city' => $geo['city'],
            'device' => $device, 'browser' => $browser, 'os' => $os,
            'lang' => mb_substr((string) ($in['l'] ?? ''), 0, 20), 'screen_w' => (int) ($in['w'] ?? 0) ?: null,
        ]);
        return ['ok' => true, 'pv' => $id . '.' . substr(hash_hmac('sha256', (string) $id, cfg('secret')), 0, 16)];
    }

    /** Atualiza tempo de permanência e rolagem de uma visualização. */
    public static function ping(string $pv, int $durationMs, int $scroll): void
    {
        [$id, $sig] = array_pad(explode('.', $pv, 2), 2, '');
        if (!ctype_digit($id) || !hash_equals(substr(hash_hmac('sha256', $id, cfg('secret')), 0, 16), $sig)) {
            return;
        }
        Db::run(
            'UPDATE pageviews SET duration_ms = ?, scroll_pct = CASE WHEN scroll_pct > ? THEN scroll_pct ELSE ? END WHERE id = ?',
            [min($durationMs, 3600000), $scroll, min(100, max(0, $scroll)), (int) $id]
        );
    }

    public static function event(string $name, string $label, string $path, int $value = 0): void
    {
        if (!in_array($name, self::EVENTS, true) || self::detectBot($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            return;
        }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        Db::insert('events', [
            'ts' => time(), 'day' => date('Y-m-d'), 'name' => $name, 'label' => mb_substr($label, 0, 255),
            'path' => mb_substr($path, 0, 255), 'value' => $value,
            'visitor' => substr(hash_hmac('sha256', client_ip() . $ua . date('Y-m-d'), cfg('secret') . 'visitor'), 0, 40),
        ]);
    }

    public static function section(string $path): string
    {
        $seg = explode('/', trim($path, '/'))[0] ?? '';
        return match ($seg) {
            '' => 'Início',
            'estudos' => 'Estudos',
            'blog' => 'Blog',
            'noticias' => 'Notícias',
            'por-que-adotar-moeda-social' => 'Por que adotar',
            'diagnostico' => 'Diagnóstico',
            'contato' => 'Contato',
            'perguntas-frequentes' => 'FAQ',
            'glossario' => 'Glossário',
            'privacidade', 'cookies', 'termos' => 'Institucional',
            'sobre' => 'Sobre',
            default => 'Outras',
        };
    }

    private static function classifyReferrer(string $ref, array $q): array
    {
        $src = strtolower((string) ($q['utm_source'] ?? ''));
        $medium = strtolower((string) ($q['utm_medium'] ?? ''));
        if ($medium === 'email' || $src === 'newsletter') {
            return ['email', $src ?: 'newsletter'];
        }
        if (in_array($src, ['chatgpt.com', 'chatgpt', 'perplexity', 'claude', 'gemini', 'copilot'], true)) {
            return ['ia', $src];
        }
        $host = strtolower((string) parse_url($ref, PHP_URL_HOST));
        $host = preg_replace('/^(www|m|l|lm)\./', '', $host);
        $own = preg_replace('/^www\./', '', (string) parse_url(cfg('site.url'), PHP_URL_HOST));
        if ($host === '' || $host === $own) {
            return [$src ? 'campanha' : 'direto', $src ?: null];
        }
        foreach (self::AI_REFERRERS as $d => $name) {
            if (str_ends_with($host, $d)) {
                return ['ia', $name];
            }
        }
        foreach (self::SOCIAL as $d => $name) {
            if (str_starts_with($host, $d) || str_contains($host, '.' . $d) || $host === rtrim($d, '.')) {
                return ['social', $name];
            }
        }
        foreach (self::SEARCH as $d) {
            if (str_starts_with($host, $d) || str_contains($host, '.' . $d)) {
                return ['busca', ucfirst(rtrim(explode('.', $d)[0], '.'))];
            }
        }
        return ['referencia', mb_substr($host, 0, 190)];
    }

    private static function geo(): array
    {
        $empty = ['country' => null, 'country_code' => null, 'region' => null, 'city' => null];
        $ip = client_ip();
        if (cfg('analytics.geo_provider') === 'none' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $empty;
        }
        $h = ip_hash($ip);
        $row = Db::one('SELECT country, country_code, region, city, ts FROM geo_cache WHERE ip_hash = ?', [$h]);
        if ($row && (time() - (int) $row['ts']) < 30 * 86400) {
            unset($row['ts']);
            return $row;
        }
        $ctx = stream_context_create(['http' => ['timeout' => 2, 'header' => "Accept: application/json\r\n"]]);
        $json = @file_get_contents('https://ipwho.is/' . rawurlencode($ip) . '?fields=success,country,country_code,region,city&lang=pt-BR', false, $ctx);
        $d = $json ? json_decode($json, true) : null;
        if (!is_array($d) || empty($d['success'])) {
            return $empty;
        }
        $geo = [
            'country' => mb_substr((string) $d['country'], 0, 80), 'country_code' => mb_substr((string) $d['country_code'], 0, 4),
            'region' => mb_substr((string) $d['region'], 0, 100), 'city' => mb_substr((string) $d['city'], 0, 120),
        ];
        Db::run('DELETE FROM geo_cache WHERE ip_hash = ?', [$h]);
        Db::insert('geo_cache', ['ip_hash' => $h] + $geo + ['ts' => time()]);
        return $geo;
    }

    private static function parseUa(string $ua, int $w): array
    {
        $device = match (true) {
            (bool) preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua) => 'Tablet',
            (bool) preg_match('/Mobi|iPhone|Android|IEMobile|Opera Mini/i', $ua) => 'Celular',
            $w > 0 && $w < 768 => 'Celular',
            default => 'Desktop',
        };
        $browser = match (true) {
            (bool) preg_match('/Edg\//', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/', $ua) => 'Opera',
            (bool) preg_match('/SamsungBrowser/', $ua) => 'Samsung Internet',
            (bool) preg_match('/Firefox\//', $ua) => 'Firefox',
            (bool) preg_match('/CriOS|Chrome\//', $ua) => 'Chrome',
            (bool) preg_match('/Safari\//', $ua) => 'Safari',
            default => 'Outro',
        };
        $os = match (true) {
            (bool) preg_match('/Windows/', $ua) => 'Windows',
            (bool) preg_match('/iPhone|iPad|iPod/', $ua) => 'iOS',
            (bool) preg_match('/Android/', $ua) => 'Android',
            (bool) preg_match('/Mac OS X|Macintosh/', $ua) => 'macOS',
            (bool) preg_match('/CrOS/', $ua) => 'ChromeOS',
            (bool) preg_match('/Linux/', $ua) => 'Linux',
            default => 'Outro',
        };
        return [$device, $browser, $os];
    }
}
