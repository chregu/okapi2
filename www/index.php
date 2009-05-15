<?php

require_once dirname(__FILE__) . '/../inc/api/init.php';
$sc = api_init::start();

$ctrl = $sc->controller;
$ctrl->process();
