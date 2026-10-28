<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', __DIR__);
define('STORAGE', ROOT . '/storage');
define('CONTENT', ROOT . '/content');

require ROOT . '/vendor/autoload.php';

// Configuração: padrão + sobrescritas locais (fora do Git)
$config = require APP . '/config.php';
foreach ([dirname(ROOT) . '/moedasocial.config.php', APP . '/config.local.php'] as $override) {
    if (is_file($override)) {
        $config = array_replace_recursive($config, require $override);
    }
}
$GLOBALS['config'] = $config;

date_default_timezone_set($config['site']['timezone']);
mb_internal_encoding('UTF-8');

$isCli = PHP_SAPI === 'cli';
ini_set('display_errors', getenv('APP_DEBUG') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE . '/php-error.log');
error_reporting(E_ALL);

foreach (glob(APP . '/lib/*.php') as $lib) {
    require_once $lib;
}

Db::migrate();
