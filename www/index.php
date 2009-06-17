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
try {
    $command = $sc->$command;
} catch (InvalidArgumentException $e) {
    $command = new $command($sc->route, $sc->request, $sc->response, $sc->config);
}
// handle view
if ($viewName = $ctrl->process($command)) {
    try {
        $view = $sc->$viewName;
    } catch  (InvalidArgumentException $e) {
        $view = new $viewName($sc->route, $sc->request, $sc->response, $sc->config);
    }
    $view->prepare();
    $data = $command->getData();
    $view->dispatch($data, $ctrl->getExceptions());
}
