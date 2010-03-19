<?php

/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * base session driver that uses the default php
 * session storage through $_SESSION
 */
class api_testing_sessionDriver extends api_session_php {

    public function overwrite($request = null, $store = null) {
        if ($request) {
            $this->request = $request;
        }
        if ($store) {
            $this->store = $store;
        }
    }

    protected function getCurrentSession() {
        return array('flash' => array(), 'data' => array());
    }

    /**
     * saves the changes from this request into the real session storage
     *
     * @return bool success
     */
    public function commit() {
        return true;
    }
}