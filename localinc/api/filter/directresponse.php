<?php

class api_filter_directresponse {

    public function __construct($response,$route) {
        $this->response = $response;
    }

    public function request(sfEvent $event) {
        $req = $event['request'];
        if ($dr = $req->getParam('direct')) {

            if ($dr == 'json') {
                $this->response->setContent(json_encode(array(
                        $event['request']->getParameters()
                )));
            } else {
                $this->response->setInputData($event['request']->getParameters());
                $this->response->setXsl($req->getParam('direct'));
                $this->response->runView();
            }
            $event->setReturnValue($this->response);
            return true;
        }

    }
}

