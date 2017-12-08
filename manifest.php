<?php
/**
 * Created by Caro Team (info@carocrm.com).
 * User: Jacky (jacky@carocrm.com).
 * Year: 2017
 * File: manifest.php
 */

$manifest = array(
    array(
        'acceptable_sugar_versions' => array(),
    ),
    array(
        'acceptable_sugar_flavors' => array(
            'CE',
            'PRO',
            'ENT',
        ),
    ),
    'readme' => '',
    'key' => 'UA',
    'author' => 'youaddon',
    'description' => 'Sync documents file to Google Drive',
    'icon' => '',
    'is_uninstallable' => false,
    'name' => 'UA_DocumentGoogleDrive',
    'published_date' => '2017-01-15 00:00:00',
    'type' => 'module',
    'version' => '1.0',
    'remove_tables' => 'prompt',
);

$installdefs = array(
    'copy' => array(
        array(
            'from' => '<basepath>/include',
            'to' => 'include',
        ),
        array(
            'from' => '<basepath>/modules',
            'to' => 'modules',
        ),
        array(
            'from' => '<basepath>/custom',
            'to' => 'custom',
        ),
    ),
);