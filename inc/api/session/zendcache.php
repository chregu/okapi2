<?php

/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * extended session driver that uses a zend cache frontend and backend
 * to store session files to any backend zend cache supports
 */
class api_session_zendcache extends api_session_php {

    /**
     * zend cache frontend
     *
     * @var Zend_Cache_Core
     */
    protected $frontend;

    /**
     * zend cache backend
     *
     * @see Zend_Cache_Backend_ExtendedInterface
     * @var Zend_Cache_Backend
     */
    protected $backend;

    public function __construct($cacheFrontend, $cacheBackend, $namespace = 'okapi') {
        $cacheFrontend->setBackend($cacheBackend);
        $this->frontend = $cacheFrontend;
        $this->backend = $cacheBackend;

        parent::__construct($namespace);
    }

    protected function getCurrentSession() {
        $data = $this->frontend->load($this->getSessId());
        return unserialize($data);
    }

    protected function getSessId() {
        return $this->namespace.$this->sessId;
    }

    /**
     * saves the changes from this request into the real session storage
     *
     * @return bool success
     */
    public function commit() {
        return $this->frontend->save(serialize($this->store), $this->getSessId());
    }

    /**
     * regenerates the session id
     *
     * @param bool $deleteOld if true, deletes the old session
     * @return bool success
     */
    public function regenerateId($deleteOld = false) {
        if ($deleteOld) {
            $this->frontend->remove($this->getSessId());
        }
        return parent::regenerateId($deleteOld);
    }
}