<?php
/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * Parses the request and stores all information it can extract from
 * the request in a easily accessible form.
 */
class api_request {
    /** Hostname of the current request. */
    protected $host = '';
    /** Subdomain of the current request's hostname. */
    protected $path = '';
    /** Query arguments (?foo=bar) string taken from the url */
    protected $args = '';
    /** Full URL of the current request. */
    protected $url = '';
    /** HTTP verb of the current request. */
    protected $verb = '';
    /** Language for the current request. */
    protected $lang = '';
    /** if the default language was used or not */
    protected $langDefaultUsed = false;
    /** api_params: Request parameters. */
    protected $params;
    /** Filename extracted from the path. */
    protected $filename = '';
    /** Extension extracted from the path. */
    protected $extension = false;
    /** Default & allowed extentions */
    protected $extensions = '';
    /** Copy of the $_COOKIES array to allow easier testing */
    protected $cookies;
    /**
     * Matched route
     * @var api_routing_route
     */
    protected $route;

    /**
     * Constructor. Parses the request and fills in all the
     * values it can.
     */
    public function __construct($params, $lang = null, $extensions = null) {
        $this->host = API_HOST;
        $this->outputLangs = empty($lang['languages']) ? array('en') : $lang['languages'];
        $this->defaultLang = empty($lang['default']) ? reset($this->outputLangs) : $lang['default'];
        $this->forceLang = isset($lang['forceLang']) ? (bool)$lang['forceLang'] : true;
        $this->acceptBrowserLang = !empty($lang['acceptBrowserLang']);
        $this->cookies = $_COOKIE;

        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $args = '';
        if (strpos($path, '?') !== FALSE) {
            $args = substr($path, strpos($path, '?'));
            $path = substr($path, 0, strpos($path, '?'));
        }

        /* TODO restore if declared useful by anyone
        if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] != '/index.php') {
            $scriptpathlen = strlen($_SERVER['SCRIPT_NAME']) -10;
            $path = substr($path,$scriptpathlen);
        }*/
        // Get language from the beginning of the URL
        $lang = $this->getLanguageFromPath($path);
        if ($lang !== null) {
            $this->lang = $lang['lang'];
            $this->langDefaultUsed = $lang['defaultUsed'];
            $path = $lang['path'];
        }

        // HTTP verb - assume GET as default
        $this->verb = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

        $this->params = $params;
        $this->params->setGet($_GET);
        if ($this->verb == 'POST') {
            $this->params->setPost($_POST);
        }

        if ($this->lang === '') {
            $lang = $this->parseLanguage($path);
            $this->lang = $lang['lang'];
            $this->langDefaultUsed = $lang['defaultUsed'];
            $path = $lang['path'];
        }

        $this->url = $this->lang
            ? API_HOST.'/'.$this->lang.API_MOUNTPATH.substr($path, 1)
            : API_HOST.API_MOUNTPATH.substr($path, 1);
        $this->path = api_helpers_string::removeDoubleSlashes($path);
        $this->args = $args;

        // Path
        $this->filename = $this->parseFilename($this->path);

        $matches = array();
        if ($this->filename != '') {
            /* if you set an extension: [xml, foo, rss, html] node in your
             * config file, only these extensions are valid extensions.
             * the rest is not parsed as an extension */
            if (isset($extensions['allowed'])) {
                $aExtensions = $extensions['allowed'];

                if (preg_match("#\.([a-z]+)$#", $this->filename, $matches)) {
                    if (is_array($aExtensions) && in_array($matches[1], $aExtensions)) {
                        $this->extension = $matches[1];
                    }
                }
            }
        }
        if (!$this->extension) {
            $this->extension = isset($extensions['default']) ? $extensions['default'] : 'html';
        }
    }

    /**
     * Set the matched route.
     */
    public function setRoute($route) {
        $this->route = $route;
        $this->params->setRoute($this->route->getParams());
    }

    /**
     * Returns if the default lang was used
     */
    public function getLangDefaultUsed() {
        return $this->langDefaultUsed;
    }

    /**
     * Returns the hostname of the current request.
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * Returns the path of the current request. Language and path prefix
     * are removed.
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Returns the query args of the current request
     */
    public function getQueryArgs() {
        return $this->args;
    }

    /**
     * Returns the full URL of the current request. (not
     * including query parameters)
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Returns the verb / request method of the current request.
     * The verb is always upper case.
     */
    public function getVerb() {
        return $this->verb;
    }

    /**
     * Returns the detected language of the current request.
     */
    public function getLang() {
        return $this->lang;
    }

    /**
     * Returns a list of all configured languages.
     */
    public function getLanguages() {
        return $this->outputLangs;
    }

    /**
     * Returns the configured default language.
     */
    public function getDefaultLanguage() {
        return $this->defaultLang;
    }

    /**
     * Returns the file name of the current request.
     */
    public function getFilename() {
        return $this->filename;
    }

    /**
     * Returns the extension of the file name. This consists of three or
     * four letters.
     */
    public function getExtension() {
        return $this->extension;
    }

    /**
     * Returns the request parameters.
     */
    public function getParameters() {
        return $this->params;
    }

    /**
     * Returns the client IP address. In case of a clustered (load balancer)
     * setup, this returns the real client IP by looking at the
     * X-Cluster-Client-IP header.
     */
    public function getClientIp() {
        $headers = array(
            'HTTP_X_FORWARDED_FOR', 'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR', 'HTTP_X_FORWARDED',
            'HTTP_FORWARDED', 'HTTP_VIA', 'HTTP_X_COMING_FROM',
            'HTTP_X_COMING_FROM', 'HTTP_COMING_FROM',
            'REMOTE_ADDR'
        );
        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }
    }

    /**
     * Returns a single request parameter.
     * You can pass in a default value which is returned in case the
     * param does not exist. Null is returned by default.
     * @param $param string: Key of the request parameter to return.
     * @param $default string: Default value to return if the key does
     *        not exist in the request parameters.
     * @return string: Request parameter or default.
     */
    public function getParam($param, $default = null) {
        if (isset($this->params[$param])) {
            return $this->params[$param];
        }
        return $default;
    }

    /**
     * returns a cookie value, it allows for easier testing
     * to wrap cookies in the request
     * @param string $name cookie name
     * @param string $default default value
     * @return string
     */
    public function getCookie($name, $default = null) {
        if (isset($this->cookies[$name])) {
            return $this->cookies[$name];
        }
        return $default;
    }

    /**
     * Parses out a file name from the current path.
     * The last path component is returned if it contains an extension
     * of at least one character.
     * @param $path string: Path to parse.
     * @return string: File name
     */
    protected function parseFilename($path) {
        preg_match("#[\s\w\xc0-\xff\-\_\%2F\+]*\.[a-z0-9]{1,}$#i", $path, $matches);
        if (isset($matches[0])) {
            return api_helpers_string::ensureUtf8(urldecode($matches[0]));
        }
        return '';
    }

    /**
     * Gets the language from the given path.
     * On finding a language, an associative array is returned
     * containing the new path and the language.
     * If no language is found, null is returned.
     * @param $path string: Path to parse.
     * @return hash: Parsed path.
     */
    protected function getLanguageFromPath($path) {
        // Path
        preg_match("#^\/([a-z]{2})(\/|$)#", $path, $matches);
        if (isset($matches[1]) && in_array($matches[1], $this->outputLangs)) {
            $lang = $matches[1];
            $newpath = (string) substr($path, 3);
            return array(
                'path' => $newpath,
                'lang' => $lang,
                'defaultUsed' => false
            );
        }

        return null;
    }

    /**
     * Gets a language from the current request. The following
     * positions are checked for a language:
     *   - Path (beginning of path).
     *   - HTTP Accept headers.
     *   - Default.
     *
     * @param $path string: Path to parse.
     * @return hash: Parsed path.
     */
    protected function parseLanguage($path) {
        $newpath = $path;

        if ($retval = $this->getLanguageFromPath($path)) {
            return $retval;
        }

        // lang is in ACCEPT_LANGUAGE
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $this->acceptBrowserLang) {
            $accls = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach($accls as $accl) {
                // Does not respect coefficient
                $lang = substr($accl, 0, 2);
                if (in_array($lang, $this->outputLangs)) {
                    return array('path' => $newpath, 'lang' => $lang, 'defaultUsed' => false);
                }
            }
        }

        $lang = $this->defaultLang;

        return array('path' => $newpath, 'lang' => $lang, 'defaultUsed' => true);
    }

    public function getSapi() {
        return PHP_SAPI;
    }
}
