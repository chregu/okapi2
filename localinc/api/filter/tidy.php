<?php

class api_filter_tidy {

    function __construct() {

    }

    function response(sfEvent $event, api_response $response) {
        $headers = $response->getHeaders();

        if (isset($headers['Content-Type']) && substr($headers['Content-Type'],0,9) == 'text/html') {
            $response->setContent($this->tidyfy($response->getContent()));
        }
        return $response;
    }
    function tidyfy($string) {
        $tidyOptions = array(
                "output-xhtml" => true,
                "clean" => false,
                "wrap" => "0",
                "indent" => false,
                "indent-spaces" => 1,
                "ascii-chars" => false,
                "wrap-attributes" => false,
                "alt-text" => "",
                "doctype" => "loose",
                "numeric-entities" => true,
                "drop-proprietary-attributes" => true);

        if (class_exists("tidy")) {
            $tidy = new tidy();
            if (!$tidy) {
                return $string;
            }
        } else {
            return $string;
        }

        // this preg escapes all not allowed tags...
        $tidy->parseString($string, $tidyOptions, "utf8");
        $tidy->cleanRepair();
        return (string) $tidy;
    }
}

