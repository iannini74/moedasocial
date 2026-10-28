<?php
/**
 * Copie este arquivo para FORA da pasta pública, um nível acima de public_html:
 *   /home/USUARIO/domains/moedasocial.com/moedasocial.config.php
 * (ou para app/config.local.php, que é ignorado pelo Git) e preencha.
 * Só inclua as chaves que deseja sobrescrever.
 */
return [
    'secret'     => 'GERE-UMA-STRING-ALEATORIA-LONGA', // php -r "echo bin2hex(random_bytes(32));"
    'cron_token' => 'OUTRA-STRING-ALEATORIA',           // usada em /cron/{token}/news

    'admin' => [
        'user'          => 'admin',
        'password_hash' => '', // php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT);"
    ],

    'mail' => [
        'contact_to' => 'contato@tribex.com',
        'from_email' => 'contato@moedasocial.com',  // caixa criada na Hostinger
        'reply_to'   => 'contato@moedasocial.com',
        'smtp_user'  => 'contato@moedasocial.com',
        'smtp_pass'  => 'SENHA-DA-CAIXA-DE-EMAIL',
    ],

    'ai' => [
        'openai_api_key' => 'sk-...',  // platform.openai.com/api-keys
        // 'models' => ['gpt-5.2', 'gpt-5'],  // opcional: ordem de preferência
    ],

    // Opcional: MySQL em vez de SQLite
    // 'db' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306, 'name' => 'u123_moeda', 'user' => 'u123_moeda', 'pass' => '...'],

    'analytics' => [
        'ga4_id' => '', // opcional, carregado só com consentimento
    ],
    'seo' => [
        'indexnow_key'  => '', // php -r "echo bin2hex(random_bytes(16));"
        'google_verify' => '',
        'bing_verify'   => '',
    ],
];
