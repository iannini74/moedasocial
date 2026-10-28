<?php
declare(strict_types=1);

final class Blog
{
    public static function published(int $limit = 12, int $offset = 0): array
    {
        return Db::all("SELECT * FROM posts WHERE status = 'published' AND published_at <= ? ORDER BY published_at DESC LIMIT $limit OFFSET $offset", [time()]);
    }

    public static function count(): int
    {
        return (int) Db::value("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at <= ?", [time()]);
    }

    public static function find(string $slug): ?array
    {
        return Db::one("SELECT * FROM posts WHERE slug = ? AND status = 'published'", [$slug]);
    }

    public static function uniqueSlug(string $slug): string
    {
        $base = slugify($slug) ?: 'artigo';
        $s = $base;
        for ($i = 2; Db::value('SELECT id FROM posts WHERE slug = ?', [$s]); $i++) {
            $s = "$base-$i";
        }
        return $s;
    }

    public static function create(array $p): int
    {
        $now = time();
        return Db::insert('posts', [
            'slug' => self::uniqueSlug($p['slug'] ?? $p['title']),
            'title' => $p['title'],
            'description' => $p['description'] ?? excerpt($p['body'], 300),
            'body' => $p['body'],
            'tags' => $p['tags'] ?? '',
            'sources' => json_encode($p['sources'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'faq' => json_encode($p['faq'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'author' => $p['author'] ?? cfg('ai.blog_author'),
            'status' => $p['status'] ?? 'published',
            'generated_by' => $p['generated_by'] ?? 'editorial',
            'reading_min' => reading_minutes($p['body']),
            'published_at' => $p['published_at'] ?? $now,
            'updated_at' => $now,
        ]);
    }

    /** Importa artigos iniciais de content/blog/*.md (apenas uma vez por slug). */
    public static function seed(): void
    {
        if (Db::setting('blog_seeded') === (string) count(glob(CONTENT . '/blog/*.md'))) {
            return;
        }
        foreach (glob(CONTENT . '/blog/*.md') as $file) {
            [$meta, $body] = Content::parse((string) file_get_contents($file));
            $slug = preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', basename($file, '.md'));
            if (Db::value('SELECT id FROM posts WHERE slug = ?', [$slug])) {
                continue;
            }
            self::create([
                'slug' => $slug, 'title' => $meta['title'] ?? $slug, 'description' => $meta['description'] ?? null,
                'body' => $body, 'tags' => $meta['tags'] ?? '', 'sources' => Content::sources($body), 'faq' => Content::faq($body),
                'published_at' => isset($meta['date']) ? strtotime($meta['date'] . ' 09:00') : time(),
                'author' => $meta['author'] ?? cfg('ai.blog_author'),
            ]);
        }
        Db::setting('blog_seeded', (string) count(glob(CONTENT . '/blog/*.md')));
    }

    public static function related(array $post, int $n = 3): array
    {
        $tags = array_filter(array_map('trim', explode(',', (string) $post['tags'])));
        $all = self::published(30);
        $scored = [];
        foreach ($all as $p) {
            if ($p['id'] === $post['id']) {
                continue;
            }
            $common = count(array_intersect($tags, array_map('trim', explode(',', (string) $p['tags']))));
            $scored[] = [$common, $p['published_at'], $p];
        }
        usort($scored, fn($a, $b) => [$b[0], $b[1]] <=> [$a[0], $a[1]]);
        return array_slice(array_column($scored, 2), 0, $n);
    }
}
