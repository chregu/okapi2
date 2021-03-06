<?php
/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * Configures how requests are routed to controllers.
 */
class api_routing extends sfPatternRouting {

    /**
     * @var api_routing_route
     */
    protected $route = false;

    /**
     * caches the current route name to avoid many lookups in the routes array
     * @var string
     */
    protected $routeName;

    /**
     * @var api_request
     */
    protected $request;

    /**
     * @var bool if true, generates absolute urls for non-ssl routes when the current request is ssl
     */
    protected $forceUnsecure;

    public function __construct($dispatcher, $request = null, $forceUnsecure = false) {
        $this->request = $request;
        $this->forceUnsecure = $forceUnsecure;
        $this->options['context']['prefix'] = API_HOST;
        $this->options['context']['host'] = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
        $this->options['context']['is_secure'] = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443';
        parent::__construct($dispatcher);
    }

    /**
     * Removes all defined routes.
     */
    public function clear() {
        $this->clearRoutes();
    }

    /**
     * return the last matched route, this is typically the current
     * page's route, or a route by name if a name is provided
     *
     * @param string $name route name only if required
     * @return api_routing_route|false
     */
    public function getRoute($name = null) {
        if ($name === null) {
            return $this->route;
        }
        return $this->routes[$name];
    }

    public function getRouteName() {
        if (!$this->routeName) {
            $this->routeName = array_search($this->route, $this->routes);
        }
        return $this->routeName;
    }

    /**
     * generates an url from a route name
     *
     * if null is passed to the route name, the current page url is returned
     *
     * @param string $name route name
     * @param array $params route parameters to build the url
     * @param bool $absolute true to get a full url with the domain name etc
     */
    public function gen($name, $params = array(), $absolute = null) {
        if ($name === null) {
            $url = API_MOUNTPATH.$this->request->getLang().$this->request->getPath();
            parse_str(ltrim($this->request->getQueryArgs(), '?'), $curParams);
            if ($params) {
                $curParams = array_merge($curParams, $params);
            }
            if ($curParams) {
                $url = rtrim($url, '?') . '?' . http_build_query($params);
            }
            if ($absolute) {
                return API_HOST.$url;
            }
            return $url;
        }
        $url = $this->generate($name, $params);

        // force https links for ssl routes
        if ($this->routes[$name]['ssl'] && substr(API_HOST, 0, 5) !== 'https') {
            return str_replace('http://', 'https://', API_HOST).API_MOUNTPATH.$this->request->getLang().$url;
        }
        // force http links for non-ssl routes if forceUnsecure is enabled
        // you can disable this for POST urls by explicitly passing absolute as false
        if ($this->forceUnsecure && $absolute !== false && !$this->routes[$name]['ssl'] && substr(API_HOST, 0, 5) === 'https') {
            return str_replace('https://', 'http://', API_HOST).API_MOUNTPATH.$this->request->getLang().$url;
        }

        if ($absolute) {
            return API_HOST.API_MOUNTPATH.$this->request->getLang().$url;
        }
        return API_MOUNTPATH.$this->request->getLang().$url;
    }

    /**
     * Adds an api_routing_route object to the routing table.
     *
     * @param api_routing_route $route route to add.
     */
    public function add($name, $route) {
        $this->appendRoute($name, $route);
        return $route;
    }

    /**
     * @return api_routing_route the created route object
     */
    public function route($name, $pattern, $options = array(), $defaults = array(), $requirements = array()) {
        $route = new api_routing_route($pattern, (array)$defaults, (array)$requirements, $options);
        $this->appendRoute($name, $route);
        return $route;
    }

    /**
     * Returns the correct route for the given request. Returns false
     * if no route matches.
     *
     * @param api_request $request the request object
     * @return api_routing_route|false matched route
     */
    public function matchRoute($request) {
        $this->request = $request;
        $uri = $request->getPath();

        $match = $this->parse($uri);
        if ($match) {
            $match = $match['_sf_route'];
            $match->mergeProperties();
        }
        // reparse without trailing slash if we hit the home
        // because /foo/ doesn't match /foo/:optionalparam for example
        if ($match == $this->routes['default'] && $uri !== '/' && $uri !== '') {
            $match = $this->parse(rtrim($uri, '/'));
            if ($match) {
                $match = $match['_sf_route'];
                $match->mergeProperties();
            }
        }
        if (empty($match)) {
            throw new api_exception('Could not match a route');
        }
        $this->route = $match;
        $this->routeName = null;
        $this->request->setRoute($this->route);
        return $match;
    }

    /**
     * this method is a copy of the parent sfPatternRouting::getRouteThatMatchesUrl
     * with some changes to support okapi's optionalextension parameter
     */
    protected function getRouteThatMatchesUrl($url)
    {
        $ext = $this->request->getExtension();
        $len = strlen($ext);
        $urlNoExt = substr($url, -$len-1) == '.'.$ext
            ? substr($url, 0, -$len-1) : $url;
        $baseUrl = $url;

        foreach ($this->routes as $name => $route) {
            $route->setDefaultParameters($this->defaultParameters);

            // Remove the extension if the user wished so
            $url = (isset($route['optionalextension']) && $route['optionalextension'])
                ? $urlNoExt : $baseUrl;

            if (false === $parameters = $route->matchesUrl($url, $this->options['context'])) {
                continue;
            }

            return array('name' => $name, 'pattern' => $route->getPattern(), 'parameters' => $parameters);
        }

        return false;
    }
}
