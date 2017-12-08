<?php

class EAPMViewGoogleOauth2Redirect extends SugarView
{
    /** {@inheritdoc} */
    public function process()
    {
        global $sugar_config;

        $token = $this->authenticate();
        if ($token) {
            $token = json_decode($token, true);
            $response = array(
                'result' => true,
                'hasRefreshToken' => isset($token['refresh_token']),
            );
        } else {
            $response = array(
                'result' => false,
            );
        }

        $this->ss->assign('response', $response);
        $this->ss->assign('siteUrl', $sugar_config['site_url']);
        $this->ss->display('modules/EAPM/tpls/GoogleOauth2Redirect.tpl');
    }

    protected function authenticate()
    {
        if (!isset($_GET['code'])) {
            return false;
        }

        require_once 'include/externalAPI/Google/ExtAPIGoogle.php';
        $api = new ExtAPIGoogle();
        return $api->authenticate($_GET['code']);
    }
}
