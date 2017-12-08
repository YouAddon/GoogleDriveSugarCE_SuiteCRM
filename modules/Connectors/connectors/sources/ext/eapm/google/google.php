<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once 'include/connectors/sources/default/source.php';

/**
 * Class ext_eapm_google
 */
class ext_eapm_google extends source
{
    protected $_enable_in_wizard = false;
    protected $_enable_in_hover = false;
    protected $_has_testing_enabled = false;
    protected $_required_config_fields = [
        'oauth2_client_id',
        'oauth2_client_secret'
    ];

    public $field_types = [
        'upload_to_admin' => 'checkbox'
    ];

    const CONTACTS_FEED = 'https://www.google.com/m8/feeds/contacts/default/full';
    const GDATA_VERSION = '3.0';

    /** {@inheritdoc} */
    public function getItem($args = array(), $module = null)
    {
    }

    /** {@inheritdoc} */
    public function getList($args = array(), $module = null)
    {
        /** @var Google_Client $client */
        $client = $this->_eapm->getClient();

        try {
            $http_request = $this->create_http_request($args);
            $request = $client->getAuth()->authenticatedRequest($http_request);
        } catch (Google_Auth_Exception $e) {
            $GLOBALS['log']->fatal('Unable to retrieve item list for google contact connector: ' . $e->getMessage());
            return false;
        }

        if ($request->getResponseHttpCode() != 200) {
            return false;
        }

        $feed = new Zend_Gdata_Contacts_ListFeed();
        list($major, $minor) = explode('.', self::GDATA_VERSION);
        $feed->setMajorProtocolVersion($major);
        $feed->setMinorProtocolVersion($minor);
        $xml = $request->getResponseBody();

        try {
            $feed->transferFromXML($xml);
        } catch (Zend_Gdata_App_Exception $e) {
            $GLOBALS['log']->fatal('Unable to retrieve item list for google contact connector: ' . $e->getMessage());
        }

        $rows = array();
        foreach ($feed->entries as $entry) {
            $rows[] = $entry->toArray();
        }

        return array(
            'totalResults' => $feed->getTotalResults()->getText(),
            'records' => $rows,
        );
    }

    /**
     * @param array $args
     * @return Google_Http_Request
     */
    private function create_http_request(array $args)
    {
        $params = array();

        if (isset($args['maxResults'])) {
            $params['max-results'] = $args['maxResults'];
        }

        if (!empty($args['startIndex'])) {
            $params['start-index'] = $args['startIndex'];
        } else {
            $params['start-index'] = 1;
        }

        return new Google_Http_Request(
            self::CONTACTS_FEED . '?' . http_build_query($params),
            'GET',
            array(
                'GData-Version' => self::GDATA_VERSION,
            )
        );
    }
}
