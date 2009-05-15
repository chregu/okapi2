<?php

$routing->route('*')
    ->config(array(
        'command' => 'default',
        'view' => array ('xsl' => 'default.xsl')
    ));
