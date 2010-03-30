<?php

/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * session storage driver interface
 * @see api_session_php for docs
 */
interface api_session_Idriver {
    public function read($key = null, $mode = 0);
    public function write($key, $value, $mode = 0);
    public function delete($key, $mode = 0);

    /**
     * saves the changes from this request into the real session storage
     *
     * @return bool success
     */
    public function commit();

    /**
     * regenerates the session id
     *
     * @param bool $deleteOld if true, deletes the old session
     * @return bool success
     */
    public function regenerateId($deleteOld = false);
}