<?php

class api_filter_hellocheck {


    public function __construct() {

    }

    public function request(sfEvent $event) {
        $req = $event['request'];
        if ($req->getParam('hello')) {
            throw new Exception("Not allowed to have hello as parameter");

        }

    }
}

