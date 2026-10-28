<?php
declare(strict_types=1);

final class Pages
{
    public static function home(): void
    {
        view('pages/home', [
            'meta' => [
                'title' => 'Moeda Social e Economia Circular: guia completo para municípios',
                'description' => 'Tudo sobre moedas sociais e economia circular no Brasil: estudos com fontes, cases como Mumbuca e Palmas, marco legal, notícias diárias e diagnóstico gratuito para seu município.',
                'path' => '/',
                'schema' => [],
            ],
            'news' => News::latest(6),
            'posts' => Blog::published(3),
            'studies' => array_slice(Content::studies(), 0, 6, true),
        ]);
    }

    public static function studies(): void
    {
        view('pages/studies', [
            'meta' => [
                'title' => 'Estudos sobre moeda social: conceitos, cases, leis e implantação',
                'description' => 'Biblioteca de estudos sobre moedas sociais e economia circular: conceitos, finalidades, cases nacionais e mundiais, marco legal, visão do TCU e TCEs, riscos e roteiro de implantação.',
                'path' => '/estudos',
                'schema' => [Seo::breadcrumbs(['Início' => '/', 'Estudos' => '/estudos'])],
            ],
            'studies' => Content::studies(),
        ]);
    }

    public static function study(string $slug): void
    {
        $s = Content::study($slug) ?? not_found();
        $html = Content::render($s['md']);
        $faq = Content::faq($s['md']);
        $sources = Content::sources($s['md']);
        $all = array_values(Content::studies());
        $idx = array_search($slug, array_column($all, 'slug'), true);
        $path = '/estudos/' . $slug;
        view('pages/study', [
            'meta' => [
                'title' => $s['seo_title'] ?? $s['title'],
                'description' => $s['description'],
                'path' => $path,
                'type' => 'article',
                'schema' => [
                    Seo::breadcrumbs(['Início' => '/', 'Estudos' => '/estudos', $s['title'] => $path]),
                    Seo::article([
                        'title' => $s['title'], 'description' => $s['description'], 'path' => $path,
                        'published' => strtotime($s['published'] ?? $s['updated']), 'modified' => strtotime($s['updated']),
                        'sources' => $sources, 'words' => str_word_count(strip_tags($html)), 'keywords' => $s['keywords'] ?? null,
                    ]),
                    Seo::faqPage($faq),
                ],
            ],
            'study' => $s,
            'html' => $html,
            'toc' => toc($html),
            'prev' => $idx > 0 ? $all[$idx - 1] : null,
            'next' => $idx !== false && $idx < count($all) - 1 ? $all[$idx + 1] : null,
            'reading' => reading_minutes($html),
        ]);
    }

    public static function why(): void
    {
        $faq = require APP . '/views/data/why-faq.php';
        view('pages/why', [
            'meta' => [
                'title' => 'Por que seu município deve adotar uma moeda social?',
                'description' => 'Renda que fica na cidade, mais vendas no comércio local, empregos, arrecadação maior com a Reforma Tributária e transparência total: entenda, com dados, por que adotar uma moeda social municipal.',
                'path' => '/por-que-adotar-moeda-social',
                'schema' => [
                    Seo::breadcrumbs(['Início' => '/', 'Por que adotar' => '/por-que-adotar-moeda-social']),
                    Seo::faqPage($faq),
                ],
            ],
            'faq' => $faq,
        ]);
    }

    public static function diagnostic(): void
    {
        view('pages/diagnostic', [
            'meta' => [
                'title' => 'Diagnóstico gratuito: moeda social no seu município',
                'description' => 'Simulador gratuito para prefeituras: informe os dados do município e receba um diagnóstico com impacto na renda local, empregos, arrecadação, prontidão e roteiro de implantação da moeda social. Gere o PDF.',
                'path' => '/diagnostico',
                'schema' => [
                    Seo::breadcrumbs(['Início' => '/', 'Diagnóstico' => '/diagnostico']),
                    [
                        '@type' => 'WebApplication', 'name' => 'Diagnóstico de Moeda Social para Municípios',
                        'url' => url('/diagnostico', true), 'applicationCategory' => 'BusinessApplication',
                        'operatingSystem' => 'Web', 'inLanguage' => 'pt-BR', 'isAccessibleForFree' => true,
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'BRL'],
                    ],
                ],
            ],
        ]);
    }

    public static function news(): void
    {
        $cat = $_GET['categoria'] ?? null;
        $cat = isset(News::CATEGORIES[$cat]) ? $cat : null;
        $page = max(1, (int) ($_GET['pagina'] ?? 1));
        $per = 20;
        view('pages/news', [
            'meta' => [
                'title' => $cat ? 'Notícias: ' . News::CATEGORIES[$cat] : 'Notícias sobre moeda social, economia solidária e circular',
                'description' => 'Notícias atualizadas diariamente sobre moedas sociais, bancos comunitários, economia solidária, economia circular e regulação de pagamentos no Brasil.',
                'path' => '/noticias' . ($cat ? '?categoria=' . $cat : ''),
                'robots' => $page > 1 ? 'noindex,follow' : null,
                'schema' => [Seo::breadcrumbs(['Início' => '/', 'Notícias' => '/noticias'])],
            ],
            'items' => News::latest($per, $cat, ($page - 1) * $per),
            'total' => News::count($cat),
            'page' => $page,
            'per' => $per,
            'cat' => $cat,
        ]);
    }

    public static function blog(): void
    {
        $page = max(1, (int) ($_GET['pagina'] ?? 1));
        $per = 9;
        view('pages/blog', [
            'meta' => [
                'title' => 'Blog: moeda social, desenvolvimento local e economia circular',
                'description' => 'Artigos quinzenais com análises, guias práticos e casos sobre moedas sociais, economia circular, regulação e desenvolvimento econômico dos municípios.',
                'path' => '/blog',
                'robots' => $page > 1 ? 'noindex,follow' : null,
                'schema' => [Seo::breadcrumbs(['Início' => '/', 'Blog' => '/blog'])],
            ],
            'posts' => Blog::published($per, ($page - 1) * $per),
            'total' => Blog::count(),
            'page' => $page,
            'per' => $per,
        ]);
    }

    public static function post(string $slug): void
    {
        $p = Blog::find($slug) ?? not_found();
        $safe = $p['generated_by'] === 'agente-ia';
        $html = Content::render((string) $p['body'], $safe);
        $sources = json_decode((string) $p['sources'], true) ?: [];
        $faq = json_decode((string) $p['faq'], true) ?: [];
        $path = '/blog/' . $p['slug'];
        view('pages/post', [
            'meta' => [
                'title' => $p['title'],
                'description' => $p['description'],
                'path' => $path,
                'type' => 'article',
                'published' => (int) $p['published_at'],
                'schema' => [
                    Seo::breadcrumbs(['Início' => '/', 'Blog' => '/blog', $p['title'] => $path]),
                    Seo::article([
                        'type' => 'BlogPosting', 'title' => $p['title'], 'description' => $p['description'], 'path' => $path,
                        'published' => (int) $p['published_at'], 'modified' => (int) $p['updated_at'], 'author' => $p['author'],
                        'sources' => $sources, 'keywords' => $p['tags'], 'words' => str_word_count(strip_tags($html)),
                    ]),
                    Seo::faqPage($faq),
                ],
            ],
            'post' => $p,
            'html' => $html,
            'toc' => toc($html),
            'related' => Blog::related($p),
        ]);
    }

    public static function contact(array $state = []): void
    {
        view('pages/contact', [
            'meta' => [
                'title' => 'Apoio para implantar a moeda social no seu município',
                'description' => 'Fale com especialistas em moeda social e meios de pagamento: diagnóstico, modelagem do programa, minuta de lei, tecnologia, credenciamento do comércio e prestação de contas.',
                'path' => '/contato',
                'schema' => [Seo::breadcrumbs(['Início' => '/', 'Contato' => '/contato'])],
            ],
            'state' => $state,
        ]);
    }

    public static function contactSubmit(): void
    {
        $r = Contact::handle();
        if (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') {
            json_out($r, $r['ok'] ? 200 : 422);
        }
        self::contact($r);
    }

    public static function faq(): void
    {
        $faq = require APP . '/views/data/faq.php';
        $flat = array_merge(...array_values($faq));
        view('pages/faq', [
            'meta' => [
                'title' => 'Perguntas frequentes sobre moeda social',
                'description' => 'Respostas diretas sobre moeda social: é legal? Quem pode criar? Como funciona o lastro? Precisa de autorização do Banco Central? Como o Tribunal de Contas avalia? E muito mais.',
                'path' => '/perguntas-frequentes',
                'schema' => [Seo::breadcrumbs(['Início' => '/', 'Perguntas frequentes' => '/perguntas-frequentes']), Seo::faqPage($flat)],
            ],
            'faq' => $faq,
        ]);
    }

    public static function glossary(): void
    {
        $terms = require APP . '/views/data/glossary.php';
        ksort($terms, SORT_LOCALE_STRING);
        view('pages/glossary', [
            'meta' => [
                'title' => 'Glossário de moeda social e economia circular',
                'description' => 'Definições claras de lastro, paridade, arranjo de pagamento, banco comunitário, efeito multiplicador, demurrage, LM3, IBS e outros termos de moedas sociais.',
                'path' => '/glossario',
                'schema' => [
                    Seo::breadcrumbs(['Início' => '/', 'Glossário' => '/glossario']),
                    [
                        '@type' => 'DefinedTermSet', 'name' => 'Glossário de moeda social', 'url' => url('/glossario', true),
                        'hasDefinedTerm' => array_map(fn($t, $d) => ['@type' => 'DefinedTerm', 'name' => $t, 'description' => strip_tags($d)], array_keys($terms), $terms),
                    ],
                ],
            ],
            'terms' => $terms,
        ]);
    }

    public static function static(string $page): void
    {
        $titles = [
            'sobre' => ['Sobre o Portal Moeda Social', 'Quem somos, nossa missão e como produzimos o conteúdo do Portal Moeda Social.'],
            'privacidade' => ['Política de Privacidade', 'Como o Portal Moeda Social coleta, usa, armazena e protege dados pessoais, em conformidade com a LGPD.'],
            'cookies' => ['Política de Cookies', 'Quais cookies e tecnologias semelhantes o Portal Moeda Social utiliza e como gerenciar suas preferências.'],
            'termos' => ['Termos de Uso', 'Condições de uso do Portal Moeda Social, direitos autorais e responsabilidades.'],
        ];
        [$t, $d] = $titles[$page];
        view("pages/$page", ['meta' => ['title' => $t, 'description' => $d, 'path' => "/$page",
            'schema' => [Seo::breadcrumbs(['Início' => '/', $t => "/$page"])]]]);
    }

    public static function newsletterSubmit(): void
    {
        $json = ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
        $r = ['ok' => true, 'msg' => 'Obrigado!'];
        if (!empty($_POST['website'])) {
            $json ? json_out($r) : redirect('/');
        }
        if (!form_token_valid($_POST['_t'] ?? null, 2)) {
            $r = ['ok' => false, 'msg' => 'Atualize a página e tente novamente.'];
        } elseif (empty($_POST['consent'])) {
            $r = ['ok' => false, 'msg' => 'Para se inscrever, é preciso aceitar a Política de Privacidade.'];
        } elseif (rate_limited('nl:' . ip_hash(), 6, 3600)) {
            $r = ['ok' => false, 'msg' => 'Muitas tentativas. Tente novamente mais tarde.'];
        } else {
            $profile = input('profile', 60);
            $r = Newsletter::subscribe(
                input('email', 190), input('name', 150), isset(Newsletter::PROFILES[$profile]) ? $profile : '',
                input('municipality', 150), strtoupper(input('uf', 2)), input('source', 100) ?: 'site'
            );
            if ($r['ok']) {
                Analytics::event('newsletter_signup', input('source', 100) ?: 'site', input('source', 100));
            }
        }
        if ($json) {
            json_out($r, $r['ok'] ? 200 : 422);
        }
        view('pages/message', ['meta' => ['title' => $r['ok'] ? 'Inscrição recebida' : 'Não foi possível concluir', 'robots' => 'noindex'],
            'heading' => $r['ok'] ? 'Inscrição recebida!' : 'Ops, algo faltou', 'text' => $r['msg'], 'ok' => $r['ok']]);
    }

    public static function newsletterConfirm(): void
    {
        $s = Newsletter::confirm((string) ($_GET['t'] ?? ''));
        view('pages/message', ['meta' => ['title' => 'Confirmação de inscrição', 'robots' => 'noindex'],
            'heading' => $s ? 'Inscrição confirmada! 🎉' : 'Link inválido ou expirado',
            'text' => $s ? 'Pronto! Você receberá um aviso sempre que publicarmos um novo artigo. Enviamos um e-mail de boas-vindas com sugestões de leitura.' : 'Não encontramos esta inscrição. Tente se inscrever novamente no rodapé do site.',
            'ok' => (bool) $s]);
    }

    public static function newsletterCancel(): void
    {
        $s = Newsletter::unsubscribe((string) ($_GET['t'] ?? ''));
        if (is_post()) {
            http_response_code(200);
            exit;
        }
        view('pages/message', ['meta' => ['title' => 'Cancelamento de inscrição', 'robots' => 'noindex'],
            'heading' => $s ? 'Inscrição cancelada' : 'Link inválido',
            'text' => $s ? 'Você não receberá mais nossos e-mails. Se mudar de ideia, é só se inscrever de novo — as portas estão sempre abertas.' : 'Não encontramos esta inscrição.',
            'ok' => (bool) $s]);
    }
}
