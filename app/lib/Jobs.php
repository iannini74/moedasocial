<?php
declare(strict_types=1);

/** Rotinas agendadas (cron). */
final class Jobs
{
    /** Continua envios de newsletter de artigos ainda não notificados (em lotes). */
    public static function pendingNewsletters(): int
    {
        $sent = 0;
        foreach (Db::all("SELECT * FROM posts WHERE status = 'published' AND notified_at IS NULL AND published_at > ? ORDER BY published_at", [time() - 30 * 86400]) as $post) {
            $n = Newsletter::broadcastPost($post);
            $sent += $n;
            $remaining = (int) Db::value("SELECT COUNT(*) FROM subscribers WHERE status = 'confirmed' AND (last_sent_at IS NULL OR last_sent_at < ?)", [(int) $post['published_at']]);
            if ($remaining === 0) {
                Db::run('UPDATE posts SET notified_at = ? WHERE id = ?', [time(), $post['id']]);
            }
        }
        return $sent;
    }

    /** Limpeza conforme a política de retenção (LGPD: minimização). */
    public static function maintenance(): array
    {
        $cut = time() - cfg('analytics.retention_days', 730) * 86400;
        $pv = Db::run('DELETE FROM pageviews WHERE ts < ?', [$cut])->rowCount();
        $ev = Db::run('DELETE FROM events WHERE ts < ?', [$cut])->rowCount();
        $cr = Db::run('DELETE FROM crawler_hits WHERE ts < ?', [$cut])->rowCount();
        Db::run('DELETE FROM geo_cache WHERE ts < ?', [time() - 30 * 86400]);
        Db::run("DELETE FROM subscribers WHERE status = 'pending' AND created_at < ?", [time() - 30 * 86400]);
        if (!Db::isMysql()) {
            Db::pdo()->exec('PRAGMA optimize');
        }
        return ['pageviews' => $pv, 'events' => $ev, 'crawlers' => $cr];
    }
}
