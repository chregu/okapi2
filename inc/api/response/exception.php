<?php
//FIXME: This is another approach than we took in Okapi1. I'm not sure it's better, but it usess the exceptionhandler of sfRequestHandler
class api_response_exception extends api_response {

    /** Number of code lines included before and after the relevant code line
     * in the back trace.
     */
    const BACKTRACE_CONTEXT = 8;

    protected $debug;

    public function __construct($session, $debug = false) {
        $this->debug = $debug;
        parent::__construct($session);
    }

    public function renderPlainException($e) {

        if (!headers_sent()) {
            header('Content-Type: text/plain');
        }

        if ($this->debug) {
            $this->outputException($this->data);
            echo "\r\n\r\n".'On top of this, the view threw an exception while rendering the original exception:'."\r\n";
            $this->outputException($e);
        } else {
            echo 'The site is experiencing technical difficulties, apologies for the disturbance.';
        }

        $this->session->commit();
        die;
    }

    public function getInputData() {
        global $sc;

        $data = array(
            'exception' => $this->getTrace($this->data),
            'debug' => $this->debug,
            'sc' => $sc,
        );

        return $data;
    }

    protected function outputException($e) {
        $data = $this->getTrace($e);

        echo 'Exception ('.$data['name'].'): '.$data['message'].' (#'.$data['code'].')'."\r\n".
            'thrown in file '.$data['file'].' at line '.$data['line']."\r\n\r\nbacktrace:\r\n";
        foreach ($data['backtrace'] as $i=>$line) {
            echo '#'.($i+1).' '.@$line['class'].@$line['type'].@$line['function'].' called in file '.@$line['file'].' at line '.@$line['line']."\r\n";
        }
    }

    /**
     * Process the exception. Calls the Exception::getTrace() method to
     * get the backtrace. Gets the relevant lines of code for each step
     * in the backtrace.
     */
    public function getTrace($e) {
        $trace = $e->getTrace();
        foreach ($trace as $i => &$entry) {
            if (isset($entry['class'])) {
                try {
                    $refl = new ReflectionMethod($entry['class'], $entry['function']);
                    if (isset($trace[$i - 1]) && isset($trace[$i - 1]['line'])) {
                        $entry['caller'] = (int) $trace[$i - 1]['line'] - 1;
                    } else if ($i === 0) {
                        $entry['caller'] = (int) $e->getLine() - 1;
                    } else {
                        $entry['caller'] = null;
                    }

                    $start = $entry['caller'] - self::BACKTRACE_CONTEXT;
                    if ($start < $refl->getStartLine() || $entry['caller'] === null) {
                        $start = $refl->getStartLine() - 1;
                    }
                    $end = $entry['caller'] + self::BACKTRACE_CONTEXT;
                    if ($end > $refl->getEndLine() || $entry['caller'] === null) {
                        $end = $refl->getEndLine();
                    }
                    $entry['source'] = $this->getSourceFromFile($refl->getFileName(), $start, $end);
                } catch (Exception $e) {
                    $entry['caller'] = null;
                    $entry['source'] = '';
                }
            }

            if (isset($entry['args'])) {
                // Duplicate so we don't overwrite by-reference variables
                $args = array();
                foreach ($entry['args'] as $i => $arg) {
                    $args[$i] = gettype($arg);
                }
                $entry['args'] = $args;
            }
        }

        $exceptionParams = array();
        if (method_exists($e, 'getParams')) {
            $exceptionParams = $e->getParams();
        }

        $d = array(
                'backtrace' => $trace,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'name' => api_helpers_class::getBaseName($e),
                'params' => $exceptionParams
        );

        if (!empty($e->userInfo)) {
            $d['userInfo'] = $e->userInfo;
        }
        return $d;
    }

    /**
     * Extracts source lines from the given files. This is used to extract
     * some context of the exception.
     *
     * @param $file string: Full path to the file to get source lines.
     * @param $start int: Line number of the first line to return.
     * @param $end int: Line number of the last line to return.
     * @return array: Requested lines of the file.
     */
    public function getSourceFromFile($file, $start, $end) {
        if (file_exists($file)) {
            $lines = file($file);
            $source = array_slice($lines, $start, ($end - $start), true);
            if (is_array($source)) {
                return $source;
            }
        }
        return array();
    }
}
