<?php

/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * base session driver that manages the session cookie without ext/session
 */
abstract class api_session_nophp extends api_session_abstract {
    public function __construct($namespace, $response, $request, $storage) {
        $this->request = $request;
        $this->storage = $storage;

        parent::__construct($namespace, $response);
    }

    protected function start() {
        $this->sessId = $this->request->getCookie('PHPSESSID');
        if (!$this->sessId) {
            $this->sessId = $this->generateId();
        }
    }

     protected function generateId() {
        $sessId = api_helpers_hash::generate(32);
        $this->response->setCookie('PHPSESSID', $sessId, 0, API_MOUNTPATH);
        return $sessId;
    }

    /**
     * regenerates the session id
     *
     * @param bool $deleteOld if true, deletes the old session
     * @return bool success
     */
    public function regenerateId($deleteOld = false) {
        $this->sessId = $this->generateId();
        return true;
    }

    public function getStorage() {
        return $this->storage;
    }
}
