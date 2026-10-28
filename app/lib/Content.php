<?php
declare(strict_types=1);

/**
 * Conteúdo editorial em Markdown com front matter simples:
 *
 *   ---
 *   title: Título
 *   description: Resumo para SEO
 *   order: 1
 *   ---
 *
 * Citações: use [^3] no texto e uma seção "## Fontes" com lista numerada.
 * Perguntas frequentes: seção "## Perguntas frequentes" com "### Pergunta?" + resposta.
 */
final class Content
{
    public static function parse(string $raw): array
    {
        $meta = [];
        $body = $raw;
        if (preg_match('/^---\R(.*?)\R---\R(.*)$/s', $raw, $m)) {
            foreach (preg_split('/\R/', $m[1]) as $line) {
                if (preg_match('/^([a-z_]+):\s*(.*)$/', $line, $kv)) {
                    $meta[$kv[1]] = trim($kv[2], " \"'");
                }
            }
            $body = $m[2];
        }
        return [$meta, ltrim($body)];
    }

    /** Lista de estudos ordenada. */
    public static function studies(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [];
        foreach (glob(CONTENT . '/estudos/*.md') as $file) {
            [$meta, $body] = self::parse((string) file_get_contents($file));
            $slug = preg_replace('/^\d+-/', '', basename($file, '.md'));
            $cache[$slug] = $meta + [
                'slug' => $slug, 'file' => $file, 'md' => $body, 'order' => 99,
                'updated' => date('Y-m-d', (int) filemtime($file)),
            ];
        }
        uasort($cache, fn($a, $b) => (int) $a['order'] <=> (int) $b['order']);
        return $cache;
    }

    public static function study(string $slug): ?array
    {
        return self::studies()[$slug] ?? null;
    }

    /** Renderiza markdown com citações numeradas e âncoras de fontes. */
    public static function render(string $md, bool $safe = false): string
    {
        $html = markdown($md, $safe);
        // [^n] -> sobrescrito clicável
        $html = preg_replace('/\[\^(\d+)\]/', '<sup class="cite"><a href="#fonte-$1" aria-label="Fonte $1">$1</a></sup>', $html);
        // Lista de fontes: ids nos itens
        $html = preg_replace_callback('/(<h2 id="fontes[^"]*">.*?<\/h2>\s*<ol>)(.*?)(<\/ol>)/s', function ($m) {
            $i = 0;
            $items = preg_replace_callback('/<li>/', function () use (&$i) {
                $i++;
                return '<li id="fonte-' . $i . '">';
            }, $m[2]);
            return str_replace('<ol>', '<ol class="sources">', $m[1]) . $items . $m[3];
        }, $html);
        // Callouts: > **Atenção:** ... vira caixa destacada
        $html = preg_replace_callback(
            '/<blockquote>\s*<p><strong>(Atenção|Importante|Na prática|Resumo|Dica|Ponto de atenção|Lição|O que funcionou|O que não funcionou)(:?)<\/strong>/u',
            fn($m) => '<blockquote class="callout callout-' . slugify($m[1]) . '"><p><strong>' . $m[1] . $m[2] . '</strong>',
            $html
        );
        // Tabelas responsivas
        $html = str_replace(['<table>', '</table>'], ['<div class="table-wrap"><table>', '</table></div>'], $html);
        return $html;
    }

    /** Extrai pares pergunta/resposta da seção "Perguntas frequentes" para FAQPage. */
    public static function faq(string $md): array
    {
        if (!preg_match('/^## Perguntas frequentes\s*$(.*?)(?=^## |\z)/msu', $md, $m)) {
            return [];
        }
        preg_match_all('/^### (.+?)\R+(.*?)(?=^### |\z)/msu', $m[1], $qs, PREG_SET_ORDER);
        return array_map(fn($q) => ['q' => trim($q[1]), 'a' => trim(strip_tags(markdown(trim($q[2]))))], $qs);
    }

    /** Lista de fontes [título, url] a partir da seção Fontes. */
    public static function sources(string $md): array
    {
        if (!preg_match('/^## Fontes.*?$(.*?)(?=^## |\z)/msu', $md, $m)) {
            return [];
        }
        preg_match_all('/^\d+\.\s+(.*)$/mu', $m[1], $lines);
        $out = [];
        foreach ($lines[1] as $l) {
            preg_match('/\((https?:\/\/[^)\s]+)\)|<(https?:\/\/[^>]+)>|(https?:\/\/\S+)/', $l, $u);
            $out[] = ['title' => trim(strip_tags(markdown($l))), 'url' => $u[1] ?? $u[2] ?? $u[3] ?? ''];
        }
        return $out;
    }

    /** Texto puro (para llms-full.txt e buscas). */
    public static function plain(string $md): string
    {
        return trim(preg_replace("/\n{3,}/", "\n\n", $md));
    }
}
