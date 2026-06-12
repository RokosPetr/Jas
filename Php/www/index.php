<?php
declare(strict_types=1);

if (file_exists('maintenance.lock')) {
    // HTTP status dřív než začneme cokoliv vypisovat
    http_response_code(503);

    // --- Pokus o odhlášení: zrušení PHP session + session cookie ---
    // Pozn.: funguje jen pokud ještě nebyl odeslán výstup (žádné echo/HTML před tímto blokem).

    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Pokud máš session name nastavený jinde, nech to být; session_start si ho vezme z konfigurace.
        @session_start();
    }

    // Vymazání dat v session
    if (isset($_SESSION)) {
        $_SESSION = [];
    }

    // Smazání session cookie (musí sedět path/domain/secure/httponly)
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    }

    // Zničení session na serveru
    @session_destroy();

    // --- Volitelně: smazání dalších cookies aplikace ---
    // Pozor: cookies s jiným domain/path tímhle nesmažeš.
    foreach (array_keys($_COOKIE ?? []) as $name) {
        // path "/" je nejběžnější; pokud některé cookies používají jiný path/domain, je potřeba je smazat i s těmi parametry.
        setcookie($name, '', time() - 3600, '/');
    }

    // Teprve teď render error stránky / textový fallback
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        require __DIR__ . '/../home/app/Modules/Presenters/templates/Error/503.phtml';
        exit;
    }

    die("\nÚDRŽBA APLIKACE\n\n");
}

use App\Bootstrap;

const WWW_DIR = __DIR__;
const VENDOR_DIR = __DIR__ . '/../home/vendor';

require VENDOR_DIR . '/autoload.php';
require __DIR__ . '/../home/app/constants.php';
require __DIR__ . '/../home/app/utils.php';

Bootstrap::boot()
    ->createContainer()
    ->getByType(Nette\Application\Application::class)
    ->run();
