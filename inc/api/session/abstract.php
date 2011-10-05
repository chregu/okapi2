<?php

/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * abstract base session driver that is storage layer agnostic
 */
abstract class api_session_abstract implements api_session_Idriver {
    
    /**
     * 
     * @var api_session_abstract
     */
    protected static $_instance;


    /**
     * current request data
     *
     * @var array
     */
    protected $requestvars;

    /**
     * data that is going to be saved at the end of the request
     *
     * @var array
     */
    protected $store;

    /**
     * session namespace of this instance
     *
     * @var string
     */
    protected $namespace;

    /**
     * request
     *
     * @var api_request
     */
    protected $request;

    /**
     * session id
     *
     * @var string
     */
    protected $sessId;

    /**
     * @param string $namespace to prevent name clashes inside the storage layer
     */
    public function __construct($namespace, $response, $request) {
        $this->namespace = $namespace;
        $this->response = $response;
        $this->response->setSession($this);
        $this->request= $request;

        if ($this->request->getSapi() === 'cli') {
            return;
        }

        $this->start();
        $this->init();
        self::$_instance = $this;
    }

    abstract protected function start();

    protected function init() {
        $this->requestvars = $this->store = $this->getCurrentSession();
        if (!is_array($this->requestvars)) {
            $this->requestvars = $this->store = array('flash' => array(), 'data' => array());
        }

        // clear old flash messages so they don't propagate to the next request
        // they remain readable in $this->requestvars though
        $this->store['flash'] = array();
        if (!isset($this->requestvars['flash'])) {
            $this->requestvars['flash'] = array();
        }
    }

    abstract protected function getCurrentSession();

    /**
     * reads a value from the session
     *
     * @param string $key
     * @param int $mode api_session::FLASH to read from the flash vars,
     *                  anything else reads from standard storage
     * @return mixed value or null if not present
     */
    public function read($key = null, $mode = 0) {
        $target = $mode & api_session::FLASH ? 'flash' : 'data';
        if ($key === null) {
            return $this->requestvars[$target];
        }
        return isset($this->requestvars[$target][$key]) ? $this->requestvars[$target][$key] : null;
    }

    /**
     * reads a value from the session
     *
     * @param string $key
     * @param mixed $value
     * @param int $mode bitmask made of api_session constants to define where to write
     * @return bool success
     */
    public function write($key, $value, $mode = 0) {
        $target = $mode & api_session::FLASH ? 'flash' : 'data';
        if ($mode & api_session::STORE) {
            $this->store[$target][$key] = $value;
        }
        if ($mode & api_session::REQUEST) {
            $this->requestvars[$target][$key] = $value;
        }
        return true;
    }

    /**
     * deletes a value from the session
     *
     * @param string $key
     * @param int $mode bitm
     * @param int $mode bitmask made of api_session constants to define where to delete
     * @return bool success
     */
    public function delete($key, $mode = 0) {
        $target = $mode & api_session::FLASH ? 'flash' : 'data';
        if ($mode & api_session::STORE) {
            unset($this->store[$target][$key]);
        }
        if ($mode & api_session::REQUEST) {
            unset($this->requestvars[$target][$key]);
        }
        return true;
    }

    /**
     * returns the session id
     *
     * @return string
     */
    protected function getSessId() {
        return $this->namespace.$this->sessId;
    }
    
    public static function getInstance() {
        return self::$_instance;            
    }
}