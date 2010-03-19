<?php
/**
 * Response class which handles outputting the header and body.
 *
 * Output buffering is used and the buffer is flushed only when calling
 * api_response::send().
 */
class api_testing_response extends api_response {

    /**
     * Constructor. Turns on output buffering.
     */
    public function __construct($session, $buffering = false) {
        parent::__construct($session, $buffering);
    }

    /**
     * Re-implements send of api_response with a no-op.
     */
    public function send() {
        // NOOP
    }

    /**
     * Catch redirects and thrown a testing exception for that.
     */
    public function redirect($to=null, $status=301) {
        throw new api_testing_exception("Redirect $status => $to");
    }
}
