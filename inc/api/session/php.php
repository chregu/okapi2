<?php

/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * base session driver that uses the default php
 * session storage through $_SESSION
 */
class api_session_php extends api_session_abstract {
    protected function start() {
        if (!session_id()) {
            session_start();
        }

        $this->sessId = session_id();
    }

    /**
     * regenerates the session id
     *
     * @param bool $deleteOld if true, deletes the old session
     * @return bool success
     */
    public function regenerateId($deleteOld = false) {
        $res = session_regenerate_id($deleteOld);
        $this->sessId = session_id();
        return $res;
    }

    protected function getCurrentSession() {
        return isset($_SESSION[$this->namespace]) ? $_SESSION[$this->namespace] : false;
    }

    /**
     * saves the changes from this request into the real session storage
     *
     * @return bool success
     */
    public function commit() {
        $_SESSION[$this->namespace] = $this->store;
        return true;
    }
}