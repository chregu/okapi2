<?php

/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * extended session driver that uses a zend cache frontend
 * to store session files to any backend zend cache supports
 */
class api_session_zendcache extends api_session_nophp {

    /**
     * zend cache frontend
     *
     * @var Zend_Cache_Core
     */
    protected $storage;

    public function __construct($namespace, $response, $request, $storage) {
        $this->storage = $storage;

        parent::__construct($namespace, $response, $request, $storage);
    }

    protected function getCurrentSession() {
        $data = $this->storage->load($this->getSessId());
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
        return $this->storage->save(serialize($this->store), $this->getSessId());
    }

    /**
     * regenerates the session id
     *
     * @param bool $deleteOld if true, deletes the old session
     * @return bool success
     */
    public function regenerateId($deleteOld = false) {
        if ($deleteOld) {
            $this->storage->remove($this->getSessId());
        }
        return parent::regenerateId($deleteOld);
    }
}
