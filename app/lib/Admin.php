<?php
declare(strict_types=1);

/** Área administrativa: analytics completo, contatos, inscritos, diagnósticos, blog e notícias. */
final class Admin
{
    private static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('ms_admin');
            session_set_cookie_params(['lifetime' => 0, 'path' => '/admin', 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Strict']);
            session_start();
        }
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store');
    }

    private static function guard(): void
    {
        self::start();
        if (empty($_SESSION['admin']) || ($_SESSION['exp'] ?? 0) < time()) {
            redirect('/admin/login');
        }
        $_SESSION['exp'] = time() + 4 * 3600;
    }

    private static function csrf(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    }

    private static function checkCsrf(): void
    {
        if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['_csrf'] ?? ''))) {
            http_response_code(400);
            exit('Token inválido. Volte e recarregue a página.');
        }
    }

    public static function login(): void
    {
        self::start();
        $error = '';
        if (is_post()) {
            if (rate_limited('admin-login:' . ip_hash(), 8, 900)) {
                $error = 'Muitas tentativas. Aguarde 15 minutos.';
            } elseif (cfg('admin.password_hash') === '') {
                $error = 'Defina admin.password_hash no arquivo de configuração.';
            } elseif (hash_equals((string) cfg('admin.user'), input('user', 80)) && password_verify((string) ($_POST['pass'] ?? ''), (string) cfg('admin.password_hash'))) {
                session_regenerate_id(true);
                $_SESSION['admin'] = true;
                $_SESSION['exp'] = time() + 4 * 3600;
                redirect('/admin');
            } else {
                $error = 'Usuário ou senha incorretos.';
            }
        }
        view('admin/login', ['error' => $error], 'admin/layout');
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
        redirect('/admin/login');
    }

    /** Período selecionado: ?p=hoje|7d|30d|90d|12m|tudo ou ?de=YYYY-MM-DD&ate=YYYY-MM-DD */
    private static function range(): array
    {
        $p = $_GET['p'] ?? '30d';
        if (!empty($_GET['de']) && !empty($_GET['ate']) && strtotime($_GET['de']) && strtotime($_GET['ate'])) {
            return [date('Y-m-d', strtotime($_GET['de'])), date('Y-m-d', strtotime($_GET['ate'])), 'custom'];
        }
        $days = ['hoje' => 0, 'ontem' => 1, '7d' => 6, '30d' => 29, '90d' => 89, '12m' => 364, 'tudo' => 3650][$p] ?? 29;
        if ($p === 'ontem') {
            $d = date('Y-m-d', strtotime('-1 day'));
            return [$d, $d, $p];
        }
        return [date('Y-m-d', strtotime("-$days days")), date('Y-m-d'), $p];
    }

    public static function dashboard(): void
    {
        self::guard();
        [$from, $to, $p] = self::range();
        $w = 'day BETWEEN ? AND ?';
        $a = [$from, $to];
        $q = fn(string $sql) => Db::all($sql, $a);

        $vd = Db::isMysql() ? 'CONCAT(visitor, day)' : '(visitor || day)';
        $kpi = Db::one("SELECT COUNT(*) pv, COUNT(DISTINCT session) sessions, COUNT(DISTINCT $vd) visitors,
            AVG(CASE WHEN duration_ms > 0 THEN duration_ms END) avg_ms, AVG(CASE WHEN scroll_pct > 0 THEN scroll_pct END) avg_scroll
            FROM pageviews WHERE $w", $a);
        $bounce = Db::one("SELECT COUNT(*) total, SUM(CASE WHEN n = 1 THEN 1 ELSE 0 END) single FROM (SELECT session, COUNT(*) n FROM pageviews WHERE $w GROUP BY session) t", $a);

        // Comparação com o período anterior de mesma duração
        $len = (strtotime($to) - strtotime($from)) / 86400 + 1;
        $pFrom = date('Y-m-d', (int) (strtotime($from) - $len * 86400));
        $pTo = date('Y-m-d', strtotime($from) - 86400);
        $prev = Db::one("SELECT COUNT(*) pv, COUNT(DISTINCT session) sessions FROM pageviews WHERE day BETWEEN ? AND ?", [$pFrom, $pTo]);

        $data = [
            'from' => $from, 'to' => $to, 'p' => $p, 'kpi' => $kpi, 'bounce' => $bounce, 'prev' => $prev,
            'daily' => $q("SELECT day, COUNT(*) pv, COUNT(DISTINCT session) s FROM pageviews WHERE $w GROUP BY day ORDER BY day"),
            'heat' => $q("SELECT dow, hour, COUNT(*) n FROM pageviews WHERE $w GROUP BY dow, hour"),
            'pages' => $q("SELECT path, MAX(title) title, COUNT(*) pv, COUNT(DISTINCT session) s, AVG(CASE WHEN duration_ms > 0 THEN duration_ms END) ms, AVG(CASE WHEN scroll_pct > 0 THEN scroll_pct END) sc FROM pageviews WHERE $w GROUP BY path ORDER BY pv DESC LIMIT 25"),
            'entries' => $q("SELECT path, COUNT(*) n FROM pageviews WHERE $w AND is_entry = 1 GROUP BY path ORDER BY n DESC LIMIT 10"),
            'sections' => $q("SELECT section, COUNT(*) pv, COUNT(DISTINCT session) s FROM pageviews WHERE $w GROUP BY section ORDER BY pv DESC"),
            'refTypes' => $q("SELECT ref_type, COUNT(*) n FROM pageviews WHERE $w AND is_entry = 1 GROUP BY ref_type ORDER BY n DESC"),
            'refs' => $q("SELECT ref_type, ref_domain, COUNT(*) n FROM pageviews WHERE $w AND is_entry = 1 AND ref_domain IS NOT NULL GROUP BY ref_type, ref_domain ORDER BY n DESC LIMIT 25"),
            'utm' => $q("SELECT utm_source, utm_medium, utm_campaign, COUNT(*) n FROM pageviews WHERE $w AND utm_source IS NOT NULL GROUP BY utm_source, utm_medium, utm_campaign ORDER BY n DESC LIMIT 15"),
            'countries' => $q("SELECT country, COUNT(*) n FROM pageviews WHERE $w AND country IS NOT NULL GROUP BY country ORDER BY n DESC LIMIT 15"),
            'regions' => $q("SELECT region, country_code, COUNT(*) n FROM pageviews WHERE $w AND region IS NOT NULL GROUP BY region, country_code ORDER BY n DESC LIMIT 27"),
            'cities' => $q("SELECT city, region, COUNT(*) n, COUNT(DISTINCT session) s FROM pageviews WHERE $w AND city IS NOT NULL GROUP BY city, region ORDER BY n DESC LIMIT 30"),
            'devices' => $q("SELECT device, COUNT(*) n FROM pageviews WHERE $w GROUP BY device ORDER BY n DESC"),
            'browsers' => $q("SELECT browser, COUNT(*) n FROM pageviews WHERE $w GROUP BY browser ORDER BY n DESC"),
            'oses' => $q("SELECT os, COUNT(*) n FROM pageviews WHERE $w GROUP BY os ORDER BY n DESC"),
            'langs' => $q("SELECT lang, COUNT(*) n FROM pageviews WHERE $w AND lang <> '' GROUP BY lang ORDER BY n DESC LIMIT 8"),
            'events' => $q("SELECT name, COUNT(*) n, COUNT(DISTINCT visitor) u FROM events WHERE $w GROUP BY name ORDER BY n DESC"),
            'adPlacements' => $q("SELECT label, SUM(CASE WHEN name = 'ad_view' THEN 1 ELSE 0 END) views, SUM(CASE WHEN name = 'ad_click' THEN 1 ELSE 0 END) clicks FROM events WHERE $w AND name IN ('ad_view','ad_click') GROUP BY label ORDER BY clicks DESC, views DESC"),
            'crawlers' => $q("SELECT bot, family, COUNT(*) n, MAX(ts) last FROM crawler_hits WHERE $w GROUP BY bot, family ORDER BY n DESC LIMIT 30"),
            'crawlerPaths' => $q("SELECT path, COUNT(*) n FROM crawler_hits WHERE $w AND family = 'ia' GROUP BY path ORDER BY n DESC LIMIT 15"),
            'realtime' => Db::all('SELECT path, city, region, ref_type, device, ts FROM pageviews WHERE ts > ? ORDER BY ts DESC LIMIT 25', [time() - 1800]),
            'realtimeCount' => (int) Db::value('SELECT COUNT(DISTINCT session) FROM pageviews WHERE ts > ?', [time() - 300]),
            'funnel' => [
                'Visitaram o diagnóstico' => (int) Db::value("SELECT COUNT(DISTINCT session) FROM pageviews WHERE $w AND path = '/diagnostico'", $a),
                'Geraram diagnóstico' => (int) Db::value("SELECT COUNT(*) FROM events WHERE $w AND name = 'simulator_run'", $a),
                'Baixaram/imprimiram' => (int) Db::value("SELECT COUNT(*) FROM events WHERE $w AND name IN ('simulator_pdf','simulator_print')", $a),
                'Enviaram contato' => (int) Db::value('SELECT COUNT(*) FROM contacts WHERE created_at BETWEEN ? AND ?', [strtotime($from), strtotime($to . ' 23:59:59')]),
            ],
            'leads' => [
                'contatos' => (int) Db::value('SELECT COUNT(*) FROM contacts WHERE created_at BETWEEN ? AND ?', [strtotime($from), strtotime($to . ' 23:59:59')]),
                'inscritos' => (int) Db::value("SELECT COUNT(*) FROM subscribers WHERE status = 'confirmed' AND confirmed_at BETWEEN ? AND ?", [strtotime($from), strtotime($to . ' 23:59:59')]),
                'diagnosticos' => (int) Db::value('SELECT COUNT(*) FROM diagnostics WHERE created_at BETWEEN ? AND ?', [strtotime($from), strtotime($to . ' 23:59:59')]),
                'total_inscritos' => (int) Db::value("SELECT COUNT(*) FROM subscribers WHERE status = 'confirmed'"),
            ],
        ];
        view('admin/dashboard', $data + ['csrf' => self::csrf()], 'admin/layout');
    }

    public static function section(string $s): void
    {
        self::guard();
        $csrf = self::csrf();
        match ($s) {
            'contatos' => view('admin/contacts', ['rows' => Db::all('SELECT * FROM contacts ORDER BY created_at DESC LIMIT 500'), 'csrf' => $csrf], 'admin/layout'),
            'inscritos' => view('admin/subscribers', [
                'rows' => Db::all('SELECT * FROM subscribers ORDER BY created_at DESC LIMIT 1000'),
                'stats' => Db::all('SELECT status, COUNT(*) n FROM subscribers GROUP BY status'), 'csrf' => $csrf,
            ], 'admin/layout'),
            'diagnosticos' => view('admin/diagnostics', ['rows' => Db::all('SELECT * FROM diagnostics ORDER BY created_at DESC LIMIT 500'), 'csrf' => $csrf], 'admin/layout'),
            'blog' => view('admin/blog', [
                'rows' => Db::all('SELECT id, slug, title, status, generated_by, published_at, notified_at FROM posts ORDER BY published_at DESC'),
                'due' => BlogAgent::due(), 'hasKey' => (bool) cfg('ai.openai_api_key'),
                'flash' => $_SESSION['flash'] ?? null, 'csrf' => $csrf,
            ], 'admin/layout'),
            'noticias' => view('admin/news', [
                'rows' => Db::all('SELECT * FROM news ORDER BY pinned DESC, published_at DESC LIMIT 300'),
                'last' => (int) Db::setting('news_last_fetch'), 'flash' => $_SESSION['flash'] ?? null, 'csrf' => $csrf,
            ], 'admin/layout'),
            default => not_found(),
        };
        unset($_SESSION['flash']);
    }

    public static function action(string $s): void
    {
        self::guard();
        self::checkCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $do = (string) ($_POST['do'] ?? '');
        switch ("$s:$do") {
            case 'blog:gerar':
                set_time_limit(900);
                $r = BlogAgent::run(true);
                $_SESSION['flash'] = $r['msg'];
                break;
            case 'blog:testar':
                $r = BlogAgent::testConnection();
                $_SESSION['flash'] = ($r['ok'] ? 'OK — ' : 'Falha — ') . $r['msg'];
                break;
            case 'blog:status':
                Db::run('UPDATE posts SET status = ?, updated_at = ? WHERE id = ?', [$_POST['status'] === 'published' ? 'published' : 'draft', time(), $id]);
                break;
            case 'blog:excluir':
                Db::run('DELETE FROM posts WHERE id = ?', [$id]);
                break;
            case 'noticias:atualizar':
                $r = News::refresh();
                $_SESSION['flash'] = "+{$r['added']} notícias novas" . ($r['errors'] ? ' (' . count($r['errors']) . ' fontes indisponíveis)' : '');
                break;
            case 'noticias:ocultar':
                Db::run('UPDATE news SET hidden = 1 - hidden WHERE id = ?', [$id]);
                break;
            case 'noticias:fixar':
                Db::run('UPDATE news SET pinned = 1 - pinned WHERE id = ?', [$id]);
                break;
            case 'contatos:status':
                Db::run('UPDATE contacts SET status = ? WHERE id = ?', [in_array($_POST['status'], ['novo', 'em-atendimento', 'proposta', 'fechado', 'descartado'], true) ? $_POST['status'] : 'novo', $id]);
                break;
            case 'inscritos:excluir':
                Db::run('DELETE FROM subscribers WHERE id = ?', [$id]);   // atendimento a pedido de eliminação (LGPD art. 18)
                break;
        }
        redirect('/admin/' . $s);
    }

    public static function export(string $type): void
    {
        self::guard();
        $map = [
            'contatos.csv' => 'SELECT * FROM contacts ORDER BY created_at DESC',
            'inscritos.csv' => 'SELECT email, name, profile, municipality, uf, status, source, created_at, confirmed_at FROM subscribers ORDER BY created_at DESC',
            'diagnosticos.csv' => 'SELECT id, created_at, municipality, uf, population, score, data FROM diagnostics ORDER BY created_at DESC',
            'acessos.csv' => 'SELECT day, path, section, ref_type, ref_domain, utm_source, utm_medium, utm_campaign, country, region, city, device, browser, os, duration_ms, scroll_pct FROM pageviews ORDER BY id DESC LIMIT 100000',
        ];
        $sql = $map[$type] ?? not_found();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="moedasocial-' . date('Ymd') . '-' . $type . '"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        $first = true;
        foreach (Db::run($sql)->fetchAll() as $row) {
            foreach (['created_at', 'confirmed_at'] as $c) {
                if (isset($row[$c]) && is_numeric($row[$c])) {
                    $row[$c] = date('Y-m-d H:i', (int) $row[$c]);
                }
            }
            if ($first) {
                fputcsv($out, array_keys($row), ';');
                $first = false;
            }
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;
    }
}
