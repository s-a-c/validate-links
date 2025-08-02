<?php

declare(strict_types=1);

// Bootstrap file for PHPStan UTF-8 and emoji support

// Ensure UTF-8 encoding is set for internal PHP functions
if (! ini_get('default_charset') || ini_get('default_charset') !== 'UTF-8') {
    ini_set('default_charset', 'UTF-8');
}

// Set internal encoding for multibyte functions
if (extension_loaded('mbstring')) {
    mb_internal_encoding('UTF-8');
    mb_regex_encoding('UTF-8');
}

// Set locale for proper UTF-8 handling if available
if (function_exists('setlocale')) {
    // Try common UTF-8 locales
    $locales = ['en_US.UTF-8', 'C.UTF-8', 'POSIX.UTF-8', 'C'];
    foreach ($locales as $locale) {
        if (setlocale(LC_ALL, $locale) !== false) {
            break;
        }
    }
}

// Ensure iconv extension uses UTF-8
if (extension_loaded('iconv')) {
    iconv_set_encoding('internal_encoding', 'UTF-8');
    iconv_set_encoding('output_encoding', 'UTF-8');
}

// Set JSON encoding options to handle UTF-8 properly
if (! defined('JSON_UNESCAPED_UNICODE')) {
    define('JSON_UNESCAPED_UNICODE', 256);
}
