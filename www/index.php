<?php

// define caching behavior of the bootstrap.yml
define('API_CACHE_BOOTSTRAP_YAML', 1);

require_once dirname(__FILE__) . '/../inc/api/init.php';
$sc = api_init::createServiceContainer();

$ctrl = $sc->controller;
$ctrl->setServiceContainer($sc);

$ctrl->run()->send();
