<?php
declare(strict_types=1);

/**
 * Agregador de notícias (RSS) — atualizado por cron diário/6h e, como
 * contingência, de forma preguiçosa ao final de uma requisição.
 */
final class News
{
    public const CATEGORIES = [
        'moeda-social'        => 'Moedas sociais',
        'bancos-comunitarios' => 'Bancos comunitários',
        'economia-solidaria'  => 'Economia solidária',
        'economia-circular'   => 'Economia circular',
        'regulacao'           => 'Regulação',
    ];

    /** Termos que reforçam relevância; itens sem nenhum deles em categorias amplas são descartados. */
    private const RELEVANCE = [
        'moeda social', 'moedas sociais', 'moeda local', 'moeda digital', 'banco comunitário', 'bancos comunitários',
        'economia solidária', 'economia circular', 'renda básica', 'mumbuca', 'arariboia', 'palmas', 'e-dinheiro',
        'reciclagem', 'cooperativa', 'arranjo de pagamento', 'instituição de pagamento', 'banco central', 'pix',
        'drex', 'inclusão financeira', 'finanças solidárias', 'senaes', 'catadores', 'resíduos', 'logística reversa',
        'transferência de renda', 'microcrédito', 'comércio local',
    ];

    public static function due(): bool
    {
        $last = (int) (Db::setting('news_last_fetch') ?? 0);
        return time() - $last > cfg('news.refresh_hours', 6) * 3600;
    }

    public static function refresh(): array
    {
        Db::setting('news_last_fetch', (string) time());
        $added = 0;
        $errors = [];
        $minTs = time() - cfg('news.max_age_days', 120) * 86400;
        foreach (cfg('news.feeds', []) as [$cat, $feedUrl]) {
            $xml = self::fetch($feedUrl);
            if (!$xml) {
                $errors[] = $feedUrl;
                continue;
            }
            $strict = !str_contains($feedUrl, 'news.google.com');
            foreach (self::items($xml) as $item) {
                $title = trim(html_entity_decode($item['title'], ENT_QUOTES, 'UTF-8'));
                $source = $item['source'] ?: self::sourceName($feedUrl);
                if ($source !== '' && str_ends_with($title, ' - ' . $source)) {
                    $title = trim(mb_substr($title, 0, -mb_strlen(' - ' . $source)));
                }
                $link = $item['link'];
                if (self::isForeign($item['source_url'] ?: $link)) {
                    continue;
                }
                $pub = strtotime($item['date']) ?: time();
                if ($title === '' || $link === '' || $pub < $minTs || $pub > time() + 3600) {
                    continue;
                }
                $desc = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($item['desc']), ENT_QUOTES, 'UTF-8')));
                $hay = mb_strtolower($title . ' ' . $desc);
                if (!self::isRelevant($hay, $strict)) {
                    continue;
                }
                $guid = hash('sha256', self::normalize($title));
                if (Db::value('SELECT id FROM news WHERE guid = ?', [$guid])) {
                    continue;
                }
                $category = self::refineCategory($cat, $hay);
                Db::insert('news', [
                    'guid' => $guid, 'title' => mb_substr($title, 0, 400), 'url' => mb_substr($link, 0, 1000),
                    'source' => mb_substr($source ?: (string) parse_url($link, PHP_URL_HOST), 0, 190),
                    'category' => $category,
                    'summary' => mb_substr(self::isEcho($desc, $title, $source) ? '' : $desc, 0, 600),
                    'published_at' => $pub, 'fetched_at' => time(),
                ]);
                $added++;
            }
        }
        // Limpeza de itens antigos não fixados
        Db::run('DELETE FROM news WHERE pinned = 0 AND published_at < ?', [time() - 365 * 86400]);
        log_line('news', "Atualização: +$added itens; falhas: " . count($errors));
        return ['added' => $added, 'errors' => $errors];
    }

    public static function latest(int $limit = 8, ?string $category = null, int $offset = 0): array
    {
        $where = 'hidden = 0';
        $params = [];
        if ($category && isset(self::CATEGORIES[$category])) {
            $where .= ' AND category = ?';
            $params[] = $category;
        }
        return Db::all("SELECT * FROM news WHERE $where ORDER BY pinned DESC, published_at DESC LIMIT $limit OFFSET $offset", $params);
    }

    public static function count(?string $category = null): int
    {
        if ($category && isset(self::CATEGORIES[$category])) {
            return (int) Db::value('SELECT COUNT(*) FROM news WHERE hidden = 0 AND category = ?', [$category]);
        }
        return (int) Db::value('SELECT COUNT(*) FROM news WHERE hidden = 0');
    }

    /** Feeds gerais (Agência Brasil, Câmara, Senado, BCB) exigem termos específicos do setor. */
    private const STRICT = [
        'moeda social', 'moedas sociais', 'moeda local', 'banco comunitário', 'bancos comunitários', 'economia solidária',
        'economia circular', 'renda básica', 'arranjo de pagamento', 'arranjos de pagamento', 'instituição de pagamento',
        'instituições de pagamento', 'inclusão financeira', 'finanças solidárias', 'drex', 'catadores', 'logística reversa',
        'microcrédito', 'cooperativismo de crédito', 'reforma tributária', 'ibs', 'transferência de renda',
    ];

    /** Normaliza itens RSS 2.0 e Atom. */
    private static function items(SimpleXMLElement $xml): array
    {
        $out = [];
        foreach ($xml->channel->item ?? [] as $i) {
            $out[] = [
                'title' => (string) $i->title, 'link' => trim((string) $i->link), 'date' => (string) $i->pubDate,
                'desc' => (string) $i->description, 'source' => trim((string) ($i->source ?? '')),
                'source_url' => (string) ($i->source['url'] ?? ''),
            ];
        }
        foreach ($xml->entry ?? [] as $i) {
            $link = '';
            foreach ($i->link as $l) {
                if ((string) ($l['rel'] ?? 'alternate') === 'alternate') {
                    $link = (string) $l['href'];
                }
            }
            $out[] = [
                'title' => (string) $i->title, 'link' => trim($link ?: (string) ($i->link['href'] ?? $i->id)),
                'date' => (string) ($i->published ?? $i->updated), 'desc' => (string) ($i->summary ?? $i->content), 'source' => '', 'source_url' => '',
            ];
        }
        return $out;
    }

    /** Descarta veículos de outros países (Google Notícias mistura Portugal, Espanha etc.). */
    private static function isForeign(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || str_ends_with($host, 'news.google.com')) {
            return false;
        }
        // Domínios com código de país diferente de .br são descartados; genéricos (.com, .org...) passam
        return (bool) preg_match('/\.([a-z]{2})$/', $host, $m) && $m[1] !== 'br';
    }

    /** Descrições do Google Notícias costumam repetir título + fonte; nesse caso não há resumo útil. */
    private static function isEcho(string $desc, string $title, string $source): bool
    {
        $n = fn($x) => preg_replace('/[^a-z0-9]/', '', slugify($x));
        $d = $n($desc);
        return $d === '' || str_starts_with($d, substr($n($title), 0, 40)) || $d === $n($title . $source);
    }

    private static function sourceName(string $feedUrl): string
    {
        $host = (string) parse_url($feedUrl, PHP_URL_HOST);
        return match (true) {
            str_contains($host, 'bcb.gov.br') => 'Banco Central do Brasil',
            str_contains($host, 'agenciabrasil') => 'Agência Brasil',
            str_contains($host, 'camara.leg.br') => 'Agência Câmara',
            str_contains($host, 'senado.leg.br') => 'Agência Senado',
            str_contains($host, 'ciclovivo') => 'CicloVivo',
            default => '',
        };
    }

    private static function isRelevant(string $hay, bool $strict = false): bool
    {
        if ($strict) {
            foreach (self::STRICT as $term) {
                if (preg_match('/\b' . preg_quote($term, '/') . '\b/u', $hay)) {
                    return true;
                }
            }
            return false;
        }
        foreach (self::RELEVANCE as $term) {
            if (str_contains($hay, $term)) {
                return true;
            }
        }
        return false;
    }

    private static function refineCategory(string $cat, string $hay): string
    {
        if (preg_match('/moeda(s)? (social|sociais|local|digital municipal)|mumbuca|arariboia|e-dinheiro/u', $hay)) {
            return 'moeda-social';
        }
        if (preg_match('/banco central|arranjo de pagamento|instituição de pagamento|projeto de lei|tcu|tribunal de contas|resolução|regulament/u', $hay)) {
            return 'regulacao';
        }
        return $cat;
    }

    private static function normalize(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', slugify($s));
    }

    private static function fetch(string $url): ?SimpleXMLElement
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'MoedaSocialNewsBot/1.0 (+' . cfg('site.url') . ')',
            CURLOPT_ENCODING => '',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$body || $code >= 400) {
            return null;
        }
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_use_internal_errors($prev);
        return $xml ?: null;
    }
}
