<?php
declare(strict_types=1);

/**
 * Acesso a dados (SQLite por padrão, MySQL opcional) com migração automática.
 */
final class Db
{
    private static ?PDO $pdo = null;
    private const SCHEMA_VERSION = 3;

    public static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }
        $c = cfg('db');
        if ($c['driver'] === 'mysql') {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$pdo->exec("SET time_zone = '-03:00'");
        } else {
            $file = $c['sqlite'];
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0775, true);
            }
            self::$pdo = new PDO('sqlite:' . $file, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$pdo->exec('PRAGMA journal_mode = WAL');
            self::$pdo->exec('PRAGMA synchronous = NORMAL');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
        }
        return self::$pdo;
    }

    public static function isMysql(): bool
    {
        return cfg('db.driver') === 'mysql';
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(',', $cols),
            implode(',', array_map(fn($c) => ':' . $c, $cols))
        );
        self::run($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    public static function setting(string $key, ?string $value = null): ?string
    {
        if ($value === null) {
            $v = self::value('SELECT v FROM settings WHERE k = ?', [$key]);
            return $v === null ? null : (string) $v;
        }
        if (self::isMysql()) {
            self::run('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $value]);
        } else {
            self::run('INSERT INTO settings (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v', [$key, $value]);
        }
        return $value;
    }

    public static function migrate(): void
    {
        $pdo = self::pdo();
        $my = self::isMysql();
        $pk = $my ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $text = $my ? 'MEDIUMTEXT' : 'TEXT';
        $str = fn(int $n) => $my ? "VARCHAR($n)" : 'TEXT';
        $tail = $my ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (k {$str(100)} PRIMARY KEY, v $text)$tail");
        $current = (int) (self::value('SELECT v FROM settings WHERE k = ?', ['schema_version']) ?? 0);
        if ($current >= self::SCHEMA_VERSION) {
            return;
        }

        $tables = [
            "pageviews (
                id $pk,
                ts INTEGER NOT NULL,
                day {$str(10)} NOT NULL,
                hour INTEGER NOT NULL,
                dow INTEGER NOT NULL,
                path {$str(255)} NOT NULL,
                section {$str(60)},
                title {$str(255)},
                referrer {$str(500)},
                ref_domain {$str(190)},
                ref_type {$str(20)},
                utm_source {$str(100)},
                utm_medium {$str(100)},
                utm_campaign {$str(150)},
                visitor {$str(64)},
                session {$str(64)},
                is_entry INTEGER DEFAULT 0,
                country {$str(80)},
                country_code {$str(4)},
                region {$str(100)},
                city {$str(120)},
                device {$str(20)},
                browser {$str(40)},
                os {$str(40)},
                lang {$str(20)},
                screen_w INTEGER,
                duration_ms INTEGER DEFAULT 0,
                scroll_pct INTEGER DEFAULT 0
            )",
            "events (
                id $pk,
                ts INTEGER NOT NULL,
                day {$str(10)} NOT NULL,
                name {$str(60)} NOT NULL,
                label {$str(255)},
                path {$str(255)},
                visitor {$str(64)},
                session {$str(64)},
                value INTEGER
            )",
            "crawler_hits (
                id $pk,
                ts INTEGER NOT NULL,
                day {$str(10)} NOT NULL,
                bot {$str(60)} NOT NULL,
                family {$str(20)},
                path {$str(255)}
            )",
            "geo_cache (
                ip_hash {$str(64)} PRIMARY KEY,
                country {$str(80)},
                country_code {$str(4)},
                region {$str(100)},
                city {$str(120)},
                ts INTEGER
            )",
            "subscribers (
                id $pk,
                email {$str(190)} NOT NULL UNIQUE,
                name {$str(150)},
                profile {$str(60)},
                municipality {$str(150)},
                uf {$str(2)},
                status {$str(20)} NOT NULL DEFAULT 'pending',
                token {$str(64)} NOT NULL,
                source {$str(100)},
                consent {$str(255)},
                created_at INTEGER,
                confirmed_at INTEGER,
                unsubscribed_at INTEGER,
                last_sent_at INTEGER
            )",
            "contacts (
                id $pk,
                created_at INTEGER NOT NULL,
                name {$str(150)},
                email {$str(190)},
                phone {$str(40)},
                org_type {$str(60)},
                organization {$str(190)},
                role {$str(120)},
                municipality {$str(150)},
                uf {$str(2)},
                population {$str(40)},
                stage {$str(60)},
                interests $text,
                timeline {$str(60)},
                message $text,
                preferred_contact {$str(30)},
                newsletter INTEGER DEFAULT 0,
                consent {$str(255)},
                source_page {$str(255)},
                status {$str(20)} DEFAULT 'novo',
                email_status {$str(40)}
            )",
            "diagnostics (
                id $pk,
                created_at INTEGER NOT NULL,
                municipality {$str(150)},
                uf {$str(2)},
                population INTEGER,
                score INTEGER,
                data $text,
                visitor {$str(64)}
            )",
            "news (
                id $pk,
                guid {$str(64)} NOT NULL UNIQUE,
                title {$str(400)} NOT NULL,
                url {$str(1000)} NOT NULL,
                source {$str(190)},
                category {$str(40)},
                summary $text,
                published_at INTEGER,
                fetched_at INTEGER,
                hidden INTEGER DEFAULT 0,
                pinned INTEGER DEFAULT 0
            )",
            "posts (
                id $pk,
                slug {$str(190)} NOT NULL UNIQUE,
                title {$str(255)} NOT NULL,
                description {$str(500)},
                body $text,
                tags {$str(255)},
                sources $text,
                faq $text,
                author {$str(120)},
                status {$str(20)} DEFAULT 'published',
                generated_by {$str(40)},
                reading_min INTEGER,
                published_at INTEGER,
                updated_at INTEGER,
                notified_at INTEGER
            )",
            "rate_limits (
                id $pk,
                k {$str(120)} NOT NULL,
                ts INTEGER NOT NULL
            )",
        ];
        foreach ($tables as $t) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS $t$tail");
        }

        $indexes = [
            'idx_pv_day'      => 'pageviews (day)',
            'idx_pv_ts'       => 'pageviews (ts)',
            'idx_pv_session'  => 'pageviews (session)',
            'idx_pv_path'     => 'pageviews (path)',
            'idx_ev_day'      => 'events (day, name)',
            'idx_cr_day'      => 'crawler_hits (day)',
            'idx_news_pub'    => 'news (published_at)',
            'idx_posts_pub'   => 'posts (status, published_at)',
            'idx_rl_k'        => 'rate_limits (k, ts)',
        ];
        foreach ($indexes as $name => $def) {
            try {
                $pdo->exec($my ? "CREATE INDEX $name ON $def" : "CREATE INDEX IF NOT EXISTS $name ON $def");
            } catch (PDOException) {
                // índice já existe (MySQL)
            }
        }

        self::setting('schema_version', (string) self::SCHEMA_VERSION);
    }
}
