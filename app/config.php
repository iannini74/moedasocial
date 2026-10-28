<?php
/**
 * Configuração padrão do portal.
 *
 * NÃO coloque senhas ou chaves aqui. Crie um arquivo de sobrescrita fora do
 * repositório (recomendado) em:
 *   ../moedasocial.config.php   (um nível acima da pasta pública)
 * ou, alternativamente, em app/config.local.php (ignorado pelo Git).
 * Veja app/config.sample.php.
 */
return [
    'site' => [
        'name'        => 'Moeda Social',
        'brand'       => 'MoedaSocial.com',
        'tagline'     => 'Conhecimento para fazer o dinheiro circular na sua cidade',
        'url'         => 'https://moedasocial.com',
        'locale'      => 'pt_BR',
        'lang'        => 'pt-BR',
        'timezone'    => 'America/Sao_Paulo',
        'email'       => 'contato@moedasocial.com',
        'copyright'   => 'TribeX',
        'developer'   => ['name' => 'TribeX', 'url' => 'https://tribex.com.br'],
        'twitter'     => '',
    ],

    // Patrocinador. Os anúncios sempre são sinalizados como "Anúncio".
    'sponsor' => [
        'name' => 'Moeda Nobre',
        'url'  => 'https://moedanobre.com',
        'utm'  => 'utm_source=moedasocial.com&utm_medium=banner&utm_campaign=portal',
    ],

    'db' => [
        // 'sqlite' (padrão, zero configuração) ou 'mysql'
        'driver'  => 'sqlite',
        'sqlite'  => __DIR__ . '/../storage/moedasocial.sqlite',
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => '',
        'user'    => '',
        'pass'    => '',
    ],

    'mail' => [
        // Destino dos contatos de apoio à implantação
        'contact_to'   => 'contato@tribex.com',
        'from_email'   => 'contato@moedasocial.com',
        'from_name'    => 'Portal Moeda Social',
        'reply_to'     => 'contato@moedasocial.com',
        // 'smtp' (recomendado na Hostinger), 'mail' (função mail() do PHP) ou 'log' (desenvolvimento)
        'transport'    => 'smtp',
        'smtp_host'    => 'smtp.hostinger.com',
        'smtp_port'    => 465,
        'smtp_secure'  => 'ssl',   // ssl (465) | tls (587)
        'smtp_user'    => '',
        'smtp_pass'    => '',
    ],

    'admin' => [
        'user'          => 'admin',
        // Gere com: php -r "echo password_hash('SUA_SENHA', PASSWORD_DEFAULT);"
        'password_hash' => '',
    ],

    // Segredo para assinar tokens (CSRF, hash de visitantes). OBRIGATÓRIO trocar.
    'secret' => 'troque-este-segredo',

    // Token para disparar rotinas via URL (cron externo): /cron/{token}/news
    'cron_token' => '',

    'ai' => [
        'anthropic_api_key' => '',
        'model'             => 'claude-opus-5-5',
        'blog_interval_days'=> 15,
        'blog_author'       => 'Redação Moeda Social',
    ],

    'news' => [
        'refresh_hours' => 6,
        'max_age_days'  => 120,
        'feeds' => [
            // [categoria, url] — testados em out/2026. "when:Nd" força itens recentes no Google News.
            ['moeda-social',        'https://news.google.com/rss/search?q=%22moeda+social%22+OR+%22moedas+sociais%22+when:30d&hl=pt-BR&gl=BR&ceid=BR:pt-419'],
            ['moeda-social',        'https://news.google.com/rss/search?q=%22moeda+social%22+prefeitura+OR+munic%C3%ADpio+when:30d&hl=pt-BR&gl=BR&ceid=BR:pt-419'],
            ['bancos-comunitarios', 'https://news.google.com/rss/search?q=%22economia+solid%C3%A1ria%22+OR+%22banco+comunit%C3%A1rio%22+when:7d&hl=pt-BR&gl=BR&ceid=BR:pt-419'],
            ['economia-solidaria',  'https://news.google.com/rss/search?q=site:gov.br+%22economia+solid%C3%A1ria%22+when:30d&hl=pt-BR&gl=BR&ceid=BR:pt-419'],
            ['economia-circular',   'https://news.google.com/rss/search?q=%22economia+circular%22+when:7d&hl=pt-BR&gl=BR&ceid=BR:pt-419'],
            ['regulacao',           'https://news.google.com/rss/search?q=%22arranjo+de+pagamento%22+OR+%22institui%C3%A7%C3%A3o+de+pagamento%22+Banco+Central+when:30d&hl=pt-BR&gl=BR&ceid=BR:pt-419'],
            ['regulacao',           'https://news.google.com/rss/search?q=%22reforma+tribut%C3%A1ria%22+munic%C3%ADpios+IBS+when:7d&hl=pt-BR&gl=BR&ceid=BR:pt-419'],
            ['regulacao',           'https://news.google.com/rss/search?q=%22renda+b%C3%A1sica%22+munic%C3%ADpio+when:30d&hl=pt-BR&gl=BR&ceid=BR:pt-419'],
            ['regulacao',           'https://www.bcb.gov.br/api/feed/sitebcb/sitefeeds/noticias'],
            ['economia-solidaria',  'https://agenciabrasil.ebc.com.br/rss/economia/feed.xml'],
            ['regulacao',           'https://www.camara.leg.br/noticias/rss/dinamico/ECONOMIA'],
            ['regulacao',           'https://www12.senado.leg.br/noticias/rss'],
            ['economia-circular',   'https://ciclovivo.com.br/feed/'],
        ],
    ],

    'analytics' => [
        // Provedor de geolocalização por IP (consulta servidor-a-servidor, IP nunca é armazenado)
        'geo_provider'   => 'ipwho',   // ipwho | none
        'ga4_id'         => '',        // opcional: G-XXXXXXX (carregado somente com consentimento)
        'retention_days' => 730,
    ],

    'seo' => [
        'indexnow_key'  => '',         // gere uma chave de 32 caracteres hex
        'google_verify' => '',
        'bing_verify'   => '',
    ],
];
