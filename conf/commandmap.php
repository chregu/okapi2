<?php

$routing->route('default', '*',
array(
        'command' => 'default',
        'method' => 'index',
        'view' => array ('xsl' => 'default.xsl')
    )
);


