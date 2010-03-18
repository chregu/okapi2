<?php
/**
*/
class api_views_plain extends api_views_common {
    public function setHeader() {
        $this->response->setContentType('text/plain');
        $this->response->setCharset('utf-8');
    }

    public function dispatch($data) {
        $this->setHeader();
        $this->response->addContent($data);
    }

    public function dispatchException($data) {
        $this->setHeader();

        if ($data['debug']) {
            $out = 'Exception ('.$data['name'].'): '.$data['message'].' (#'.$data['code'].')'."\r\n".
                'thrown in file '.$data['file'].' at line '.$data['line']."\r\n\r\nbacktrace:\r\n";
            foreach ($data['backtrace'] as $i=>$line) {
                $out .= '#'.($i+1).' '.@$line['class'].@$line['type'].@$line['function'].' called in file '.@$line['file'].' at line '.@$line['line']."\r\n";
            }
        } else {
            echo 'The site is experiencing technical difficulties, apologies for the disturbance.';
        }

        $this->response->addContent($out);
    }
}
