<?php
declare(strict_types=1);

umask(0000);

require_once(VENDOR_DIR . '/nette/neon/src/Neon/Neon.php');
require_once(VENDOR_DIR . '/nette/neon/src/Neon/Decoder.php');

$pathsFile = VENDOR_DIR . '/../config/paths.neon';
$paths = Nette\Neon\Neon::decode(file_get_contents($pathsFile));
$homeDir = dirname(realpath($pathsFile), 2);

define('HOME_DIR', $homeDir);
define('ROOT_DIR', dirname($homeDir));
define('APP_DIR', HOME_DIR . $paths['app_dir']);
define('MODULES_DIR', APP_DIR . $paths['modules_dir']);
define('CORE_DIR', APP_DIR . $paths['core_dir']);
define('TEMP_DIR', HOME_DIR . $paths['temp_dir']);
define('LOG_DIR', HOME_DIR . $paths['log_dir']);
define('DATA_DIR', WWW_DIR . $paths['data_dir']);
define('CONFIG_DIR', HOME_DIR . $paths['config_dir']);
define('ASSETS_DIR', HOME_DIR . $paths['assets_dir']);
define('CACHE_DIR', HOME_DIR . $paths['cache_dir']);
