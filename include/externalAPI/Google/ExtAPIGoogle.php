<?php

require_once 'include/externalAPI/Base/ExternalAPIBase.php';
require_once 'include/externalAPI/Base/WebDocument.php';
require_once 'include/externalAPI/Google/apiclient/src/Google/autoload.php';
require_once 'Zend/Gdata/Contacts.php';

/**
 * ExtAPIGoogle
 */
class ExtAPIGoogle extends ExternalAPIBase implements WebDocument
{
    public $supportedModules = array('Documents', 'Import');
    public $authMethod = 'oauth2';
    public $connector = 'ext_eapm_google';

    public $useAuth = true;
    public $requireAuth = true;

    protected $scopes = array(
        'https://www.googleapis.com/auth/contacts.readonly',
        Google_Service_Drive::DRIVE_READONLY,
        Google_Service_Drive::DRIVE_FILE,
    );

    public $docSearch = true;
    public $needsUrl = false;
    public $sharingOptions = null;

    const APP_STRING_ERROR_PREFIX = 'ERR_GOOGLE_API_';

    public function getClient()
    {
        $client = $this->getGoogleClient();
        $eapm = $this->getLoginInfo(false);
        if ($eapm && !empty($eapm->api_data)) {
            $client->setAccessToken($eapm->api_data);
            if ($client->isAccessTokenExpired()) {
                $this->refreshToken($client);
            }
        }

        return $client;
    }

    protected function refreshToken(Google_Client $client)
    {
        /** @var Google_Auth_OAuth2 $auth */
        $auth = $client->getAuth();
        $refreshToken = $auth->getRefreshToken();
        if ($refreshToken) {
            try {
                $client->refreshToken($refreshToken);
            } catch (Google_Auth_Exception $e) {
                $GLOBALS['log']->error($e->getMessage());

                return;
            }

            $token = $client->getAccessToken();
            $this->saveToken($token);
        }
    }

    protected function saveToken($accessToken)
    {
        global $current_user;
        $bean = $this->getLoginInfo();
        if (!$bean) {
            $bean = BeanFactory::getBean('EAPM');
            $bean->assigned_user_id = $current_user->id;
            $bean->application = 'Google';
            $bean->validated = true;
        }

        $bean->api_data = $accessToken;
        $bean->save();
    }

    public function revokeToken()
    {
        $client = $this->getClient();

        try {
            $client->revokeToken();
        } catch (Google_Auth_Exception $e) {
            return false;
        }

        $eapm = $this->getLoginInfo();
        if ($eapm) {
            $eapm->mark_deleted($eapm->id);
        }

        return true;
    }

    protected function getGoogleClient()
    {
        $config = $this->getGoogleOauth2Config();

        $client = new Google_Client();
        $client->setClientId($config['properties']['oauth2_client_id']);
        $client->setClientSecret($config['properties']['oauth2_client_secret']);
        $client->setRedirectUri($config['redirect_uri']);

        $client->setAccessType('offline');
        $client->setScopes($this->scopes);

        return $client;
    }

    protected function getGoogleOauth2Config()
    {
        $config = array();

        if (is_file('custom/modules/Connectors/connectors/sources/ext/eapm/google/config.php')) {
            require 'custom/modules/Connectors/connectors/sources/ext/eapm/google/config.php';
        } else {
            require 'modules/Connectors/connectors/sources/ext/eapm/google/config.php';
        }

        $config['redirect_uri'] = rtrim(SugarConfig::getInstance()->get('site_url'), '/')
            . '/index.php?module=EAPM&action=GoogleOauth2Redirect';

        return $config;
    }

    public function authenticate($code)
    {
        $client = $this->getClient();
        try {
            $client->authenticate($code);
        } catch (Google_Auth_Exception $e) {
            $GLOBALS['log']->error($e->getMessage());

            return false;
        }

        $token = $client->getAccessToken();
        if ($token) {
            $this->saveToken($token);
        }

        return $token;
    }

    public function uploadDoc($bean, $fileToUpload, $docName, $mimeType)
    {
        $client = $this->getClient();
        $service = new Google_Service_Drive($client);

        $file = new Google_Service_Drive_DriveFile($client);
        $file->setTitle($docName);
        $file->setDescription($bean->description);

        try {
            $createdFile = $service->files->insert($file, array(
                'data' => file_get_contents($fileToUpload),
                'uploadType' => 'multipart'
            ));
        } catch (Google_Exception $e) {
            return array(
                'success' => false,
                'errorMessage' => $GLOBALS['app_strings']['ERR_EXTERNAL_API_SAVE_FAIL'] . '.' . $e->getMessage(),
            );
        }

        $bean->doc_id = $createdFile->id;
        $bean->doc_url = $createdFile->alternateLink;

        return array(
            'success' => true,
        );
    }

    public function downloadDoc($documentId, $documentFormat)
    {
    }

    public function deleteDoc($documentId)
    {
    }

    public function shareDoc($documentId, $emails)
    {
    }

    public function searchDoc($keywords, $flushDocCache = false)
    {
        global $sugar_config;

        $client = $this->getClient();
        $drive = new Google_Service_Drive($client);

        $options = array(
            'maxResults' => $sugar_config['list_max_entries_per_page']
        );

        $queryString = "trashed = false ";
        if (!empty($keywords)) {
            $queryString .= "and title contains '{$keywords}'";
        }
        $options['q'] = $queryString;

        try {
            $files = $drive->files->listFiles($options);
        } catch (Google_Exception $e) {
            $GLOBALS['log']->fatal('Unable to retrieve google drive files:' . $e);
            return false;
        }

        $results = array();
        foreach ($files as $file) {
            $results[] = array(
                'url' => $file->alternateLink,
                'name' => $file->title,
                'date_modified' => $file->modifiedDate,
                'id' => $file->id
            );
        }

        return $results;
    }

    public function getLoginInfo($is_user = true)
    {
        if ($is_user) {
            return EAPM::getLoginInfo('Google');
        }

        $config = $this->getGoogleOauth2Config();

        if (isset($config['properties']['upload_to_admin']) && $config['properties']['upload_to_admin']) {
            $eapmBean = new EAPM();
            $queryArray = array(
                'assigned_user_id' => '1',
                'application' => 'Google',
                'deleted' => 0
            );

            $eapmBean = $eapmBean->retrieve_by_string_fields($queryArray, false);

            return $eapmBean;
        }

        $eapmBean = EAPM::getLoginInfo('Google');

        return $eapmBean;
    }
}
