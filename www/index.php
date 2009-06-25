<?php
define('API_CACHE_YAML',false);
require_once dirname(__FILE__) . '/../inc/api/init.php';
$sc = api_init::start();
// get route
$sc->routingcontainer;
$ctrl = $sc->controller;

$ctrl->setServiceContainer($sc);

$ctrl->run()->send();

