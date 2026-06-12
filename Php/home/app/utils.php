<?php
declare(strict_types=1);

use Tracy\Debugger;
use Tracy\Dumper;

const FULL = 'full';
const TIME = 'time';
const DATE = 'date';
const LONG_DATE = 'long_date';

/** Debug get_class_methods to page */
function ddc($value, bool $exit = true): void
{
    Debugger::dump(get_class_methods($value));

    if ($exit) {
        exit;
    }
}

/** Debug your data to page */
function dd($value, bool $exit = true): void
{
    Debugger::dump($value);

    if ($exit) {
        exit;
    }
}

/** Debug data to file */
function ddf(
    $value,
    bool $exit = false,
    string $filename = 'debug.txt',
    string $path = TEMP_DIR,
    bool $showTime = true
): void {

    $filename = "$path/$filename";
    $existsFile = file_exists($filename);
    $mode = $existsFile ? 'ab' : 'wb';
    $action = $existsFile ? 'open' : 'create';
    $handle = fopen($filename, $mode);

    if (!$handle) {
        dd("Cannot $action file:  $filename");
    }

    if ($showTime) {
        $microDate = DateTime::createFromFormat(
            'U.u',
            number_format(microtime(true), 6, '.', '')
        )->setTimezone(new DateTimeZone('Europe/Prague'))->format('Y-m-d H:i:s.u');
        fwrite($handle, "\n### $microDate ###\n");
    }

    fwrite($handle, Dumper::toText($value));
    fclose($handle);

    if ($exit) {
        exit;
    }
}

/** Debug your data to firelog */
function fl($value, bool $exit = false): void
{
    Debugger::fireLog($value);

    if ($exit) {
        exit;
    }
}

/** Debug your data to Tracy panel */
function cdd($value, bool $exit = false): void
{
    Debugger::barDump($value);

    if ($exit) {
        exit;
    }
}

/** Simple getEnv method from $_SERVER array */
function getAppEnv(bool $forceLoad = false): bool
{
    static $env = null;
    if ($env == null || $forceLoad) {
        $loader = new Nette\DI\Config\Loader();
        $debugMode = $loader->load(CONFIG_DIR . '/local.neon')['parameters']['developmentMode'];

        if (!is_bool($debugMode)) {
            throw new InvalidArgumentException(
                "No environment is set, use in config parameters.developmentMode: true/false !",
                1
            );
        }
        $env = $debugMode;
    }

    return $env;
}

/** Returns true, if running on local development machine */
function isDevelopment(): bool
{
    return getAppEnv();
}

/** Format datetime pattern according to locale */
function dateFormatter(string $locale, string $type = FULL): string
{
    if ($type == DATE) {
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
    } elseif ($type == TIME) {
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
    } elseif ($type == LONG_DATE) {
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::FULL, IntlDateFormatter::NONE);
    } else {
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
    }
    $pattern = $formatter->getPattern();
    $convertor = [
        'M.' => 'm.',
        'MMM' => 'M',
        'y' => 'Y',
        'yyyy' => 'Y',
        'y,' => 'Y',
        'H:mm' => 'H:i',
        'h:mm' => 'h:i',
        'EEEE' => 'l',
        'MMMM' => 'M'
    ];
    $parts = preg_split("/\s/", $pattern);
    foreach ($parts as $key => $value) {
        if (array_key_exists($value, $convertor)) {
            $parts[$key] = $convertor[$value];
        }
    }
    if ($type == DATE) {
        return implode('', $parts);
    }
    //  Czech full date format correction
    if ($pattern == 'd. M. y H:mm') {
        $output = implode(" ", $parts);
        return str_replace('d. m. Y', 'd.m.Y', $output);
    }
    return implode(" ", $parts);
}

/** Generates random string with given length on passed keyspace */
function generateRandomString(
    int $length,
    string $charSet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
): string {

    $randomString = '';
    $charSetLength = mb_strlen($charSet, '8bit');

    for ($i = 0; $i < $length; ++$i) {
        $randomString .= $charSet[random_int(0, $charSetLength - 1)];
    }

    return $randomString;
}

/** Convert hour string to float */
function hourToFloat(string $value): float
{
    if (empty($value)) {
        return 0;
    }
    $parts = explode(':', $value);
    return $parts[0] + floor(($parts[1] / 60) * 100) / 100;
}

/** Convert float to hour string */
function floatToHour(float $value): string
{
    return sprintf('%02d:%02d', (int) $value, fmod($value, 1) * 60);
}

/** Log exception and rethrow for development */
function throwDebug(Throwable $e): void
{
    if (isDevelopment()) {
        // DEBUG level will be filtered by the mailer log handler
        Debugger::log($e, Debugger::DEBUG);
        throw $e;
    }

    Debugger::log($e, Debugger::EXCEPTION);
}
