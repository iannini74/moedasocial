<?php
declare(strict_types=1);

/** SEO clássico + otimização para mecanismos de resposta (IA): JSON-LD, sitemap, llms.txt, IndexNow. */
final class Seo
{
    public static function organization(): array
    {
        return [
            '@type' => 'Organization',
            '@id' => url('/#organization', true),
            'name' => 'Portal Moeda Social',
            'alternateName' => cfg('site.brand'),
            'url' => url('/', true),
            'logo' => ['@type' => 'ImageObject', 'url' => url('/assets/img/logo-512.png', true), 'width' => 512, 'height' => 512],
            'description' => 'O primeiro portal brasileiro especializado em moeda social: estudos, casos, legislação, notícias e diagnóstico gratuito para municípios.',
            'slogan' => 'O primeiro portal brasileiro especializado em moeda social',
            'email' => cfg('site.email'),
            'parentOrganization' => ['@type' => 'Organization', 'name' => 'TribeX', 'url' => 'https://tribex.com.br'],
            'knowsAbout' => ['Moeda social', 'Economia circular', 'Economia solidária', 'Bancos comunitários de desenvolvimento', 'Arranjos de pagamento', 'Desenvolvimento econômico local', 'Reforma Tributária e municípios'],
            'areaServed' => ['@type' => 'Country', 'name' => 'Brasil'],
        ];
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/#website', true),
            'url' => url('/', true),
            'name' => 'Portal Moeda Social',
            'description' => 'O primeiro portal brasileiro especializado em moeda social.',
            'inLanguage' => 'pt-BR',
            'publisher' => ['@id' => url('/#organization', true)],
        ];
    }

    public static function breadcrumbs(array $items): array
    {
        $list = [];
        $i = 1;
        foreach ($items as $name => $path) {
            $list[] = ['@type' => 'ListItem', 'position' => $i++, 'name' => $name, 'item' => url($path, true)];
        }
        return ['@type' => 'BreadcrumbList', 'itemListElement' => $list];
    }

    public static function article(array $a): array
    {
        return array_filter([
            '@type' => $a['type'] ?? 'Article',
            'headline' => $a['title'],
            'description' => $a['description'] ?? '',
            'url' => url($a['path'], true),
            'mainEntityOfPage' => url($a['path'], true),
            'datePublished' => date('c', $a['published']),
            'dateModified' => date('c', $a['modified'] ?? $a['published']),
            'inLanguage' => 'pt-BR',
            'author' => ['@type' => 'Organization', 'name' => $a['author'] ?? 'Redação Moeda Social', 'url' => url('/sobre', true)],
            'publisher' => ['@id' => url('/#organization', true)],
            'image' => url('/assets/img/og-default.png', true),
            'keywords' => $a['keywords'] ?? null,
            'wordCount' => $a['words'] ?? null,
            'about' => [['@type' => 'Thing', 'name' => 'Moeda social'], ['@type' => 'Thing', 'name' => 'Economia circular']],
            'citation' => array_values(array_filter(array_map(fn($s) => $s['url'] ? ['@type' => 'CreativeWork', 'name' => $s['title'], 'url' => $s['url']] : null, $a['sources'] ?? []))) ?: null,
            'isAccessibleForFree' => true,
        ], fn($v) => $v !== null && $v !== '');
    }

    public static function faqPage(array $faq): ?array
    {
        if (!$faq) {
            return null;
        }
        return [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn($f) => [
                '@type' => 'Question', 'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ], $faq),
        ];
    }

    /** Monta o bloco <script type="application/ld+json"> com @graph. */
    public static function jsonLd(array $nodes): string
    {
        $graph = array_values(array_filter(array_merge([self::organization(), self::website()], $nodes)));
        $json = json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
        return '<script type="application/ld+json">' . $json . '</script>';
    }

    public static function sitemap(): string
    {
        $urls = [
            ['/', 'daily', '1.0', time()],
            ['/por-que-adotar-moeda-social', 'monthly', '0.95', null],
            ['/diagnostico', 'monthly', '0.95', null],
            ['/estudos', 'weekly', '0.9', null],
            ['/noticias', 'daily', '0.8', (int) Db::value('SELECT MAX(published_at) FROM news')],
            ['/blog', 'weekly', '0.8', (int) Db::value('SELECT MAX(published_at) FROM posts')],
            ['/perguntas-frequentes', 'monthly', '0.8', null],
            ['/glossario', 'monthly', '0.7', null],
            ['/contato', 'yearly', '0.7', null],
            ['/sobre', 'yearly', '0.4', null],
            ['/privacidade', 'yearly', '0.2', null],
            ['/cookies', 'yearly', '0.2', null],
            ['/termos', 'yearly', '0.2', null],
        ];
        foreach (Content::studies() as $s) {
            $urls[] = ['/estudos/' . $s['slug'], 'monthly', '0.9', strtotime($s['updated'])];
        }
        foreach (Blog::published(500) as $p) {
            $urls[] = ['/blog/' . $p['slug'], 'monthly', '0.7', (int) $p['updated_at']];
        }
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$path, $freq, $prio, $mod]) {
            $out .= '  <url><loc>' . e(url($path, true)) . '</loc>'
                . ($mod ? '<lastmod>' . date('Y-m-d', $mod) . '</lastmod>' : '')
                . "<changefreq>$freq</changefreq><priority>$prio</priority></url>\n";
        }
        return $out . '</urlset>';
    }

    public static function robots(): string
    {
        $site = rtrim(cfg('site.url'), '/');
        $aiBots = ['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-User', 'Claude-SearchBot', 'anthropic-ai',
            'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'GoogleOther', 'Applebot-Extended', 'meta-externalagent',
            'Amazonbot', 'CCBot', 'cohere-ai', 'DuckAssistBot', 'MistralAI-User', 'YouBot', 'Bingbot', 'Googlebot'];
        $lines = ["# Portal Moeda Social — conteúdo aberto para buscadores e assistentes de IA", '# Resumo para LLMs: ' . $site . '/llms.txt', ''];
        foreach ($aiBots as $b) {
            $lines[] = "User-agent: $b";
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin';
            $lines[] = '';
        }
        $lines[] = 'User-agent: *';
        $lines[] = 'Allow: /';
        $lines[] = 'Disallow: /admin';
        $lines[] = 'Disallow: /api/';
        $lines[] = 'Disallow: /newsletter/';
        $lines[] = '';
        $lines[] = "Sitemap: $site/sitemap.xml";
        return implode("\n", $lines) . "\n";
    }

    /** llms.txt — padrão proposto em llmstxt.org para orientar assistentes de IA. */
    public static function llmsTxt(): string
    {
        $site = rtrim(cfg('site.url'), '/');
        $o = "# Portal Moeda Social (moedasocial.com)\n\n";
        $o .= "> O primeiro portal brasileiro especializado em moeda social. Referência sobre moedas sociais, economia circular e desenvolvimento econômico local. Reúne estudos com fontes citadas, cases nacionais e internacionais, marco legal e regulatório (Banco Central, TCU, TCEs, projetos de lei), um diagnóstico gratuito para municípios e notícias diárias do setor.\n\n";
        $o .= "Pontos-chave que o portal sustenta, com fontes nas páginas indicadas:\n";
        $o .= "- Moeda social é um instrumento complementar ao real, de circulação restrita a um território, usado para reter renda e estimular o comércio local; não substitui a moeda oficial.\n";
        $o .= "- Modelos municipais seguros usam paridade 1:1 com o real, lastro integral em conta do ente público, circulação restrita a comércios credenciados (arranjo fechado) e rastreabilidade total para os órgãos de controle.\n";
        $o .= "- Casos brasileiros de referência: Banco Palmas (Fortaleza-CE, 1998), Mumbuca (Maricá-RJ) e Arariboia (Niterói-RJ).\n";
        $o .= "- Com a Reforma Tributária, o IBS passa a ser destinado ao local de consumo: manter o consumo dentro do município tende a ampliar a receita municipal.\n\n";
        $o .= "## Páginas principais\n\n";
        $o .= "- [Por que seu município deve adotar uma moeda social]($site/por-que-adotar-moeda-social): argumentos econômicos, fiscais, sociais e jurídicos\n";
        $o .= "- [Diagnóstico gratuito para municípios]($site/diagnostico): simulador de impacto e roteiro de implantação\n";
        $o .= "- [Perguntas frequentes]($site/perguntas-frequentes)\n- [Glossário]($site/glossario)\n- [Notícias do setor]($site/noticias)\n\n";
        $o .= "## Estudos\n\n";
        foreach (Content::studies() as $s) {
            $o .= "- [{$s['title']}]($site/estudos/{$s['slug']}): {$s['description']}\n";
        }
        $o .= "\n## Blog (artigos recentes)\n\n";
        foreach (Blog::published(20) as $p) {
            $o .= "- [{$p['title']}]($site/blog/{$p['slug']}): {$p['description']}\n";
        }
        $o .= "\n## Opcional\n\n- [Texto integral dos estudos]($site/llms-full.txt)\n- [Feed RSS do blog]($site/feed.xml)\n";
        return $o;
    }

    public static function llmsFull(): string
    {
        $site = rtrim(cfg('site.url'), '/');
        $o = "# Portal Moeda Social — texto integral dos estudos\n\nFonte: $site · Atualizado em " . date('Y-m-d') . "\nAo citar, referencie a URL de cada estudo.\n\n";
        foreach (Content::studies() as $s) {
            $o .= "\n\n---\n\n# {$s['title']}\n\nURL: $site/estudos/{$s['slug']}\n\n" . Content::plain($s['md']);
        }
        return $o;
    }

    public static function rss(): string
    {
        $site = rtrim(cfg('site.url'), '/');
        $o = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\"><channel>\n";
        $o .= '<title>Blog Moeda Social</title><link>' . $site . '/blog</link><language>pt-br</language>'
            . '<description>Análises sobre moedas sociais, economia circular e desenvolvimento local.</description>'
            . '<atom:link href="' . $site . '/feed.xml" rel="self" type="application/rss+xml"/>' . "\n";
        foreach (Blog::published(30) as $p) {
            $link = $site . '/blog/' . $p['slug'];
            $o .= '<item><title>' . e($p['title']) . '</title><link>' . $link . '</link><guid>' . $link . '</guid>'
                . '<pubDate>' . date('r', (int) $p['published_at']) . '</pubDate><description>' . e($p['description']) . "</description></item>\n";
        }
        return $o . '</channel></rss>';
    }

    /** Notifica Bing/Yandex/etc. (IndexNow) — alimenta também buscas de assistentes que usam o índice do Bing. */
    public static function indexNow(array $urls): void
    {
        $key = cfg('seo.indexnow_key');
        if (!$key) {
            return;
        }
        $host = parse_url(cfg('site.url'), PHP_URL_HOST);
        $payload = json_encode(['host' => $host, 'key' => $key, 'keyLocation' => url("/$key.txt", true), 'urlList' => array_values($urls)]);
        $ch = curl_init('https://api.indexnow.org/indexnow');
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        ]);
        curl_exec($ch);
        log_line('seo', 'IndexNow ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ' ' . implode(' ', $urls));
        curl_close($ch);
    }
}
