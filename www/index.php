<?php

require_once dirname(__FILE__) . '/../inc/api/init.php';
$sc = api_init::start();
$sc->log;
// get route
$sc->routingcontainer;
$ctrl = $sc->controller;

$ctrl->setScLookup(new ServicesLookup($sc));

$ctrl->run()->send();


class ServicesLookup {

    function __construct($sc) {
        $this->sc = $sc;
    }

    function getCommand($command) {
        return $this->sc->$command;
}

    function getView($viewName) {
    try {
            $view = $this->sc->$viewName;
    } catch  (InvalidArgumentException $e) {
            $view = new $viewName($this->sc->route, $this->sc->request, $this->sc->response, $this->sc->config);
        }
        return $view;
    }

    function setRoute($route) {
        $this->sc->setService('route', $route);
    }
}
