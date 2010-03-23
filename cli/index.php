#!/usr/bin/env php
<?php

/*

Usage (example calling the clitools command with the buildCache method):
export OKAPI_ENV=dev
./index.php clitools buildCache

Usage (example calling the clitools command with the buildCache method and env local):
export OKAPI_ENV=dev
./index.php clitools buildCache local

*/

if (PHP_SAPI !== 'cli') {
    die("may only be called via the CLI\n");
}

if (!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3])) {
    die("no okapi env/command/action parameters set\n");
}

array_shift($argv);
$_SERVER['OKAPI_ENV'] = array_shift($argv);
$_SERVER['REQUEST_URI'] = '/'.array_shift($argv).'/'.array_shift($argv);
$_GET = array('params' => array_values($argv));
$_SERVER['SCRIPT_NAME'] = '/index.php';

chdir(dirname(__FILE__).'/../www');
require 'index.php';
