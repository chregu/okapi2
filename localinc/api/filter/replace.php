<?php

class api_filter_replace {

    function __construct() {

    }

    function response(sfEvent $event, $response) {
            $response->setContent(str_replace("Okapi","Okapi2",$response->getContent()));
         //$response->setContent ("ff");
        return $response;
    }
}

?>