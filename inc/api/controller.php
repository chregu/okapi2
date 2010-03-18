<?php
/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * Used in views to indicate that the view has been prepared and
 * can be used now.
 */
define('API_STATE_READY', 1);

/**
 * Used in views to indicate that the view is still it it's uninitialized
 * state.
 */
define('API_STATE_FALSE', 0);

/**
 * Main controller to handle whole request.
 *
 * @author   Silvan Zurbruegg
 */
class api_controller {
    /**
     * api_request: Request container. Contains parsed information about
     * the current request.
     */
    protected $request = null;

    /**
     * array: Route which matched the current request.
     * Return value of api_routing::getRoute().
     */
    protected $route = null;

    /**
     * array: All non-fatal exceptions which have been caught.
     */
    protected $exceptions = array();

    /**
     * object sfEventDispatcher the EventDispatcher
     */

    protected $dispatcher = null;

    protected $requestFilters = array();

    /**
     * Constructor. Gets instances of api_request and api_response
     * but doesn't yet do anything else.
     */
    public function __construct(api_request $request, api_routing $routing, array $events = array()) {
        $this->request = $request;
        $this->routing = $routing;
        $this->events = $events;
    }

    public function run() {
        // load the routes
        $this->sc->routingcontainer;

        $this->dispatcher = $this->sc->dispatcher;

        if (isset($this->events['application.request'])) {
            $this->requestFilters = $this->events['application.request'];
            unset($this->events['application.request']);
        }

        $this->dispatcher->connect('application.request', array(
            $this,
            'requestDispatcher'
        ));

        $this->dispatcher->connect('application.load_controller', array(
            $this,
            'loadController'
        ));

        $this->dispatcher->connect('application.view', array(
            $this,
            'view'
        ));


        $this->dispatcher->connect('application.exception', array(
            $this,
            'exception'
        ));

        foreach ($this->events as $event => $handler) {
            $controller_connected = false;
            foreach ($handler as $callable) {
                if (is_string($callable['service'])) {
                    $callable['service'] = $this->sc->getService($callable['service']);
                }
                if (!$controller_connected && $callable['service'] === $this) {
                    $controller_connected = true;
                }
                $this->dispatcher->connect($event, array(
                    $callable['service'],
                    $callable['method']
                ));
            }
        }

        $handler = $this->sc->requesthandler;
        $response = $handler->handle($this->request);
        return $response;
    }

    public function setServiceContainer($sc) {
        $this->sc = $sc;
    }

    public function exception(sfEvent $event) {
        $r = $this->sc->response_exception;
        $r->data = $event['exception'];
        $event->setReturnValue($r);

        // render exception
        $viewName = $this->getViewName($this->route, $this->request, $r);
        if ($viewName) {
            try {
                $view = $this->sc->$viewName;

                $r->command = $this->sc->response->command;
                $view->setResponse($r);
                $view->prepare();
                $data = $r->getInputData();
                $view->dispatchException($data);
            } catch (Exception $e) {
                // The exception view could not be rendered, this is
                // a total disaster case, therefore we output plain text
                $r->renderPlainException($e);
            }
        }

        return true;
    }

    public function requestDispatcher(sfEvent $event) {
        foreach ($this->requestFilters as $callable) {
            if (is_string($callable['service'])) {
                $callable['service'] = $callable['service'] === ' controller'
                    ? $this : $this->sc->getService($callable['service']);
            }
            $callable['service']->{$callable['method']}($event);
        }
    }

    public function request(sfEvent $event) {
        $this->loadRoute($event);
    }

    protected function loadRoute(sfEvent $event) {
        $this->route = $this->routing->matchRoute($event['request']);
        $this->sc->setService('route', $this->route);
    }

    public function loadController(sfEvent $event) {
        $commandName = $this->findCommandName($this->route);
        $command = $this->sc->$commandName;
        $event->setReturnValue(array(
            array(
                $this,
                'processCommand'
            ),
            array($command)
        ));
        return true;
    }

    public function view(sfEvent $event, $response) {
        $viewName = $this->getViewName($this->route, $this->request, $response);
        if ($viewName) {
            try {
                $view = $this->sc->$viewName;
            } catch (InvalidArgumentException $e) {
                throw new api_exception('Could not find service for view: '.$viewName);
            }
            $view->setResponse($response);
            $view->prepare();
            $data = $response->getInputData();
            $view->dispatch($data);
        }
        return $response;
    }

    /**
     * Load command based on routing configuration. Uses
     * api_routing::getRoute() to get the command name for the current
     * request. The prefix "{namespace}_commands_" is added to the command name
     * to get a class name and that class is initialized.
     * Namespace is also defined in the routing
     *
     * The instance variables command and route are set to the command
     * object and the route returned by api_routing respectively.
     *
     * @exception api_exception_noCommandFound if no route matched the
     *            current request or if the command class doesn't exist.
     *
     * \deprecated The naming of commands has been renamed on 2008-02-25
     *             from {namespace}_commands_* to {namespace}_command_*. The old behaviour
     *             is currently supported but will be removed in a future
     *             release.
     */
    public function findCommandName($route) {
        if (!($route instanceOf api_routing_route)) {
            throw new api_exception_noCommandFound();
        }

        if (isset($route['namespace'])) {
            $route['namespace'] = api_helpers_string::clean($route['namespace']);
        } else {
            $route['namespace'] = API_NAMESPACE;
        }

        return $route['namespace'].'_command_' . $route['command'];
    }

    /**
     * Calls the api_command::isAllowed() method to check if the command
     * can be executed. Then api_command::process() is called.
     *
     * @exception api_exception_commandNotAllowed if api_command::isAllowed()
     *            returns false.
     *
     */
    public function processCommand($command) {
        $allowed = $command->isAllowed();
        if (!$allowed) {
            throw new api_exception_commandNotAllowed("Command access not allowed: ".get_class($command));
        }
        if (is_string($allowed)) {
            $this->route->config(array('method' => $allowed));
        }
        if (is_callable(array($command, 'preAction'))) {
            call_user_func(array($command, 'preAction'));
        }
        $response = $command->process();
        if (is_callable(array($command, 'postAction'))) {
            call_user_func(array($command, 'postAction'));
        }
        return $response;
    }

    /**
     * Loads the view and uses it to display the response for the
     * current request.
     *
     * Calls the following methods in that order:
     *    - api_controller::updateViewParams()
     *    - api_controller::prepare()
     *    - api_controller::dispatch()
     */
    public function getViewName($route, $request, $response) {
        $viewParams = $this->initViewParams($route, $response);
        $route['view'] = $viewParams;
        if (empty($viewParams) || empty($viewParams['ignore'])) {
            if (isset($viewParams['class'])) {
                $viewName = $viewParams['class'];
            } else {
                $viewName = $request->getExtension();
            }
            return $viewName;
        }

        // Ignore view
        return;
    }

    /**
     * Override the XSLT style sheet to load. Currently used by the
     * exception handler to load another view.
     *
     * @param $xsl string: XSLT stylesheet path, relative to the theme folder.
     */
    public function setXsl($xsl) {
        $this->route['view']['xsl'] = $xsl;
    }

    /**
     * Uses api_command::getXslParams() method to overwrite the
     * view parameters. All parameters returned by the command
     * are written into the 'view' array of the route.
     */
    protected function initViewParams($route, $response) {
        $response->viewParams = array_merge($route['view'], $response->viewParams);
        return $response->viewParams;
    }

    /**
     * Returns the command name, needed by tests
     *
     * FIXME: I'd like to get rid of $this->command ...
     */
    public function getCommandName() {
        return get_class($this->command);
    }

    /**
     * Returns the final, dispatched view  name, needed by tests
     *
     * FIXME: I'd like to get rid of $this->view ...
     */
    public function getFinalViewName() {
        return get_class($this->view);
    }
}
