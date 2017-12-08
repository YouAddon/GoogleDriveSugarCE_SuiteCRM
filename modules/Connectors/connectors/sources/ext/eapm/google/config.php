<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$config = array(
    'name' => 'Google',
    'eapm' => array(
        'enabled' => true,
        'only' => true
    ),
    'order' => 12,
    'properties' => array(
        'oauth2_client_id' => '',
        'oauth2_client_secret' => '',
        'upload_to_admin' => ''
    ),
);
