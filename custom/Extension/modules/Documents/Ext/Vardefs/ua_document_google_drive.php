<?php
/**
 * Created by Caro Team (info@carocrm.com).
 * User: Jacky (jacky@carocrm.com).
 * Year: 2017
 * File: ua_document_google_drive.php
 */

$dictionary['Document']['fields']['doc_type'] = [
    'name' => 'doc_type',
    'vname' => 'LBL_DOC_TYPE',
    'type' => 'enum',
    'function' => 'getDocumentsExternalApiDropDown',
    'len' => '100',
    'comment' => 'Document type (ex: Google, box.net, IBM SmartCloud)',
    'popupHelp' => 'LBL_DOC_TYPE_POPUP',
    'massupdate' => false,
    'options' => 'eapm_list',
    'default' => 'Sugar',
];