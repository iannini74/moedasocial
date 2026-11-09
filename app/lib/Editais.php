<?php
declare(strict_types=1);

/**
 * Editais de moeda social, distribuição de benefícios e benefícios sociais.
 *
 * Pesquisa diária (cron "editais") feita por três agentes, nesta ordem:
 * 1. EditaisColetor     — consulta PNCP, diários oficiais (Querido Diário) e Google Notícias;
 * 2. EditaisPesquisador — pesquisa na web com a OpenAI (web_search) em sites oficiais;
 * 3. EditaisAnalista    — confere cada item, descarta o que não é do tema, classifica,
 *                         resume em linguagem direta e publica.
 */
final class Editais
{
    public const CATEGORIES = [
        'moeda-social'            => 'Moeda social',
        'distribuicao-beneficios' => 'Distribuição de benefícios',
        'beneficios-sociais'      => 'Benefícios sociais',
    ];

    public const TYPES = [
        'edital'         => 'Edital',
        'chamamento'     => 'Chamamento público',
        'credenciamento' => 'Credenciamento',
        'licitacao'      => 'Licitação',
        'lei'            => 'Lei',
        'decreto'        => 'Decreto',
        'inscricoes'     => 'Inscrições abertas',
        'outro'          => 'Publicação oficial',
    ];

    public const STATUS = [
        'novo'       => 'Aguardando análise',
        'publicado'  => 'Publicado',
        'revisar'    => 'Revisar',
        'descartado' => 'Descartado',
        'duplicado'  => 'Duplicado',
    ];

    public static function due(): bool
    {
        $last = (int) (Db::setting('editais_last_run') ?? 0);
        return time() - $last > cfg('editais.refresh_hours', 24) * 3600 - 1800;
    }

    /**
     * Executa os três agentes de uma vez (cron e botão do painel).
     * Cada fonte falha de forma isolada, sem interromper as demais.
     */
    public static function run(bool $force = false): array
    {
        if (!$force && !self::due()) {
            return ['ok' => false, 'msg' => 'Pesquisa já feita nas últimas ' . cfg('editais.refresh_hours') . ' horas.'];
        }
        if (self::locked()) {
            return ['ok' => false, 'msg' => 'Uma pesquisa já está em andamento.'];
        }
        return self::withLock(function () {
            $first = !Db::value('SELECT id FROM editais LIMIT 1');
            $sources = self::collect();
            if (self::researchEnabled()) {
                $sources['web'] = self::research();
            }
            // Na primeira execução o acúmulo dos últimos meses é analisado de uma vez
            $analysis = self::analyze($first ? 400 : (int) cfg('editais.max_analyze', 80));
            $found = array_sum(array_column($sources, 'novos'));
            $msg = "Pesquisa concluída: $found itens novos encontrados, {$analysis['analisados']} analisados, {$analysis['publicados']} publicados, {$analysis['revisar']} para revisar.";
            log_line('editais', $msg);
            return ['ok' => true, 'msg' => $msg, 'fontes' => $sources, 'analise' => $analysis];
        });
    }

    /**
     * Contingência sem cron, chamada depois que a página já foi entregue ao visitante.
     * Pela web o tempo de execução é limitado, então cada visita faz uma única etapa:
     * coleta, pesquisa na web ou um lote de análise.
     */
    public static function step(): void
    {
        if (self::locked()) {
            return;
        }
        $webDue = self::researchEnabled() && time() - (int) Db::setting('editais_last_web') > cfg('editais.refresh_hours', 24) * 3600 - 1800;
        $analysisDue = time() - (int) Db::setting('editais_last_analysis') > 120
            && Db::value("SELECT id FROM editais WHERE status = 'novo' LIMIT 1");
        match (true) {
            self::due() => self::withLock(fn() => self::collect()),
            $webDue => self::withLock(fn() => self::research()),
            (bool) $analysisDue => self::withLock(fn() => self::analyze(15)),
            default => null,
        };
    }

    /** Agente de coleta. A data de execução é gravada antes: se o processo cair, não repete em seguida. */
    private static function collect(): array
    {
        Db::setting('editais_last_run', (string) time());
        $first = !Db::value('SELECT id FROM editais LIMIT 1');
        $since = time() - cfg($first ? 'editais.first_run_days' : 'editais.lookback_days', 10) * 86400;
        $sources = EditaisColetor::run($since);
        self::saveStatus(fn($st) => ['fontes' => $sources + array_intersect_key($st['fontes'] ?? [], ['web' => 1])] + $st);
        return $sources;
    }

    private static function researchEnabled(): bool
    {
        return cfg('editais.web_research', true) && Ai::enabled();
    }

    /** Agente de pesquisa na web. */
    private static function research(): array
    {
        Db::setting('editais_last_web', (string) time());
        $first = !Db::value("SELECT id FROM editais WHERE source = 'Pesquisa web' LIMIT 1");
        $r = EditaisPesquisador::run($first ? 60 : (int) cfg('editais.lookback_days', 10));
        log_line('editais', 'Pesquisa web: ' . json_encode($r, JSON_UNESCAPED_UNICODE));
        self::saveStatus(function ($st) use ($r) {
            $st['fontes']['web'] = $r;
            return $st;
        });
        return $r;
    }

    /** Agente de análise, seguido da limpeza e do aviso ao IndexNow. */
    private static function analyze(int $max): array
    {
        Db::setting('editais_last_analysis', (string) time());
        $r = EditaisAnalista::run($max);
        // Descartados e duplicados ficam 90 dias para auditoria e depois são apagados
        Db::run("DELETE FROM editais WHERE status IN ('descartado','duplicado') AND created_at < ?", [time() - 90 * 86400]);
        if ($r['publicados']) {
            Seo::indexNow([url('/editais', true)]);
        }
        if ($r['analisados'] || !empty($r['erro'])) {
            log_line('editais', 'Análise: ' . json_encode($r, JSON_UNESCAPED_UNICODE));
            self::saveStatus(fn($st) => ['analise' => $r] + $st);
        }
        return $r;
    }

    private static function saveStatus(callable $change): void
    {
        $st = json_decode((string) Db::setting('editais_status'), true) ?: [];
        Db::setting('editais_status', json_encode(['ts' => time()] + $change($st), JSON_UNESCAPED_UNICODE));
    }

    private static function locked(): bool
    {
        $lock = (int) (Db::setting('editais_lock') ?? 0);
        return $lock && time() - $lock < 900;
    }

    private static function withLock(callable $fn): mixed
    {
        Db::setting('editais_lock', (string) time());
        if (PHP_SAPI !== 'cli') {
            ignore_user_abort(true);
            @set_time_limit(600);
        }
        try {
            return $fn();
        } catch (Throwable $e) {
            log_line('editais', 'ERRO: ' . $e->getMessage());
            return ['ok' => false, 'msg' => $e->getMessage()];
        } finally {
            Db::setting('editais_lock', '0');
        }
    }

    /** Registra um item encontrado por um agente. Retorna false se já existia. */
    public static function add(array $c): bool
    {
        $url = trim((string) ($c['url'] ?? ''));
        $title = trim(preg_replace('/\s+/', ' ', (string) ($c['title'] ?? '')));
        if ($title === '' || !preg_match('#^https?://#i', $url)) {
            return false;
        }
        $guid = hash('sha256', $c['guid'] ?? self::normalizeUrl($url));
        if (Db::value('SELECT id FROM editais WHERE guid = ?', [$guid])) {
            return false;
        }
        $uf = strtoupper(trim((string) ($c['uf'] ?? '')));
        Db::insert('editais', [
            'guid' => $guid,
            'status' => 'novo',
            'title' => mb_substr($title, 0, 400),
            'orgao' => mb_substr(trim((string) ($c['orgao'] ?? '')), 0, 255),
            'municipio' => mb_substr(trim((string) ($c['municipio'] ?? '')), 0, 150),
            'uf' => in_array($uf, ufs(), true) ? $uf : '',
            'tipo' => mb_substr((string) ($c['tipo'] ?? ''), 0, 40),
            'trecho' => mb_substr(trim((string) ($c['trecho'] ?? '')), 0, 4000),
            'url' => mb_substr($url, 0, 1000),
            'source' => mb_substr((string) ($c['source'] ?? ''), 0, 60),
            'published_at' => (int) ($c['published_at'] ?? 0) ?: time(),
            'deadline_at' => (int) ($c['deadline_at'] ?? 0) ?: null,
            'valor' => (float) ($c['valor'] ?? 0) ?: null,
            'created_at' => time(),
        ]);
        return true;
    }

    /** Filtros públicos: categoria, uf, abertos (prazo ainda não vencido). */
    private static function where(array $f): array
    {
        $w = ["status = 'publicado'"];
        $p = [];
        if (!empty($f['categoria']) && isset(self::CATEGORIES[$f['categoria']])) {
            $w[] = 'categoria = ?';
            $p[] = $f['categoria'];
        }
        if (!empty($f['uf']) && in_array($f['uf'], ufs(), true)) {
            $w[] = 'uf = ?';
            $p[] = $f['uf'];
        }
        if (!empty($f['abertos'])) {
            $w[] = 'deadline_at >= ?';
            $p[] = strtotime('today');
        }
        return [implode(' AND ', $w), $p];
    }

    public static function latest(int $limit = 20, array $f = [], int $offset = 0): array
    {
        [$w, $p] = self::where($f);
        return Db::all("SELECT * FROM editais WHERE $w ORDER BY pinned DESC, published_at DESC LIMIT $limit OFFSET $offset", $p);
    }

    public static function count(array $f = []): int
    {
        [$w, $p] = self::where($f);
        return (int) Db::value("SELECT COUNT(*) FROM editais WHERE $w", $p);
    }

    /** Estados com ao menos um item publicado (para o filtro). */
    public static function states(): array
    {
        return array_column(Db::all("SELECT uf, COUNT(*) n FROM editais WHERE status = 'publicado' AND uf <> '' GROUP BY uf ORDER BY uf"), 'n', 'uf');
    }

    public static function normalizeUrl(string $url): string
    {
        $u = strtolower(preg_replace('#^https?://(www\.)?#i', '', trim($url)));
        $u = preg_replace('/[?&](utm_[a-z]+|fbclid|gclid)=[^&]*/', '', $u);
        return rtrim($u, '/?&');
    }

    /** Converte datas em formatos variados (ISO, dd/mm/aaaa) para timestamp. */
    public static function toTs(mixed $v): ?int
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})#', $v, $m)) {
            $v = "$m[3]-$m[2]-$m[1]";
        }
        $ts = strtotime($v);
        return $ts && $ts > 946684800 ? $ts : null;
    }
}
