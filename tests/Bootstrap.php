<?php

error_reporting(E_ALL | E_STRICT);

if (class_exists('PHPUnit_Runner_Version', true)) {
    $phpUnitVersion = PHPUnit_Runner_Version::id();

    if ('@package_version@' !== $phpUnitVersion && version_compare($phpUnitVersion, '3.6.0', '<')) {
        echo 'This version of PHPUnit (' . PHPUnit_Runner_Version::id() . ') is not supported in iHMS 1.x unit tests.' . PHP_EOL;
        exit(1);
    }

    unset($phpUnitVersion);
}

unset($phpUnitVersion);

/*
 * Determine the root, library, and tests directories of the i-HMS base library.
 */
$ihmsRoot = realpath(dirname(__DIR__));
$ihmsCoreLibrary = "$ihmsRoot/library";
$ihmsCoreTests = "$ihmsRoot/tests";

/*
 * Prepend the iHMS library/ and tests/ directories to the include_path. This allows the tests to run out of the box and
 * helps prevent loading other copies of the iHMS code and tests that would supersede this copy.
 */
$paths = array($ihmsCoreLibrary, $ihmsCoreTests, get_include_path());
set_include_path(implode(PATH_SEPARATOR, $paths));

/**
 * Setup autoloading
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include_once __DIR__ . '/../vendor/autoload.php';
} else {
    // if composer autoloader is missing, explicitly add the iHMS library path
    require_once __DIR__ . '/../library/iHMS/Loader/UniversalLoader.php';

    $loader = new iHMS\Loader\UniversalLoader();
    $loader
        ->add('iHMS', __DIR__ . '/../library')
        ->add('iHMSTest', __DIR__)
        ->register();
}

/*
 * Unset global variables that are no longer needed.
 */
unset($ihmsRoot, $ihmsCoreLibrary, $ihmsCoreTests, $paths);
