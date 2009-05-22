<?php

require_once dirname(__FILE__) . '/../inc/api/init.php';
$sc = api_init::start();

// get route
$sc->routingcontainer;
$ctrl = $sc->controller;
$route = $sc->routing->getRoute($sc->request);
$sc->setService('route', $route);

// get command
$command = $ctrl->findCommandName($route);
$command = $sc->$command;

// handle view
if ($viewName = $ctrl->process($command)) {
    $view = $sc->$viewName;
    $view->prepare();
    $data = $command->getData();
    $view->dispatch($data, $ctrl->getExceptions());
}
