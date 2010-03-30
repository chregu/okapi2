<?php

/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * extended session driver that uses a zend cache frontend and backend
 * to store session files to any backend zend cache supports
 */
class api_session_zendcache extends api_session_nophp {

    /**
     * zend cache frontend
     *
     * @var Zend_Cache_Core
     */
    protected $frontend;

    /**
     * zend cache backend
     *
     * @var Zend_Cache_Backend
     * @see Zend_Cache_Backend_ExtendedInterface
     */
    protected $backend;

    public function __construct($namespace, $response, $request, $cacheBackend, $cacheFrontend) {
        $cacheFrontend->setBackend($cacheBackend);
        $this->frontend = $cacheFrontend;

        parent::__construct($namespace, $response, $request, $cacheBackend);
    }

    protected function getCurrentSession() {
        $data = $this->frontend->load($this->getSessId());
        if (empty($data)) {
            return false;
        }
        return unserialize($data);
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