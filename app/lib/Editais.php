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

    /** Executa os três agentes. Cada fonte falha de forma isolada, sem interromper as demais. */
    public static function run(bool $force = false): array
    {
        if (!$force && !self::due()) {
            return ['ok' => false, 'msg' => 'Pesquisa já feita nas últimas ' . cfg('editais.refresh_hours') . ' horas.'];
        }
        $lock = (int) (Db::setting('editais_lock') ?? 0);
        if ($lock && time() - $lock < 1800) {
            return ['ok' => false, 'msg' => 'Uma pesquisa já está em andamento.'];
        }
        Db::setting('editais_lock', (string) time());
        $start = time();
        try {
            $first = !Db::value('SELECT id FROM editais LIMIT 1');
            $since = time() - cfg($first ? 'editais.first_run_days' : 'editais.lookback_days', 10) * 86400;

            $sources = EditaisColetor::run($since);
            if (cfg('editais.web_research', true) && Ai::enabled()) {
                $sources['web'] = EditaisPesquisador::run($first ? 60 : (int) cfg('editais.lookback_days', 10));
            }
            // Na primeira execução o acúmulo dos últimos meses é analisado de uma vez
            $analysis = EditaisAnalista::run($first ? 400 : (int) cfg('editais.max_analyze', 80));

            // Descartados e duplicados ficam 90 dias para auditoria e depois são apagados
            Db::run("DELETE FROM editais WHERE status IN ('descartado','duplicado') AND created_at < ?", [time() - 90 * 86400]);

            $published = (int) Db::value("SELECT COUNT(*) FROM editais WHERE status = 'publicado' AND analyzed_at >= ?", [$start]);
            if ($published) {
                Seo::indexNow([url('/editais', true)]);
            }
            $status = ['ts' => time(), 'fontes' => $sources, 'analise' => $analysis, 'publicados' => $published];
            Db::setting('editais_status', json_encode($status, JSON_UNESCAPED_UNICODE));
            Db::setting('editais_last_run', (string) time());
            $found = array_sum(array_column($sources, 'novos'));
            $msg = "Pesquisa concluída: $found itens novos encontrados, {$analysis['analisados']} analisados, $published publicados, {$analysis['revisar']} para revisar.";
            log_line('editais', $msg);
            return ['ok' => true, 'msg' => $msg] + $status;
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
