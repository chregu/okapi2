<?php

// route name then url with :params, * is the catch-all
$routing->route('default', '*', 
    // options
    array(
        'command' => 'default',
        'method' => 'index',
        'view' => array('xsl' => 'default.xsl')
    ),
    // default values for url params
    array(),
    // requirements (regex) for url params
    array(),
);

