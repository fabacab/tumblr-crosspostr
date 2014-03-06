<?php
require_once 'OAuthWP.php';

class OAuthWP_Tumblr extends OAuthWP {

    // Override so clients can ignore the API base url.
    public function CallAPI ($url, $method, $params, $opts, &$resp) {
        return parent::CallAPI('http://api.tumblr.com/v2' . $url, $method, $params, $opts, $resp);
    }

    public function getAppRegistrationUrl ($params = array()) {
        $params = array(
            'oac[title]' => $params['title'],
            'oac[description]' => $params['description'],
            'oac[url]' => $params['url'],
            'oac[admin_contact_email]' => $params['admin_contact_email'],
            'oac[default_callback_url]' => $params['default_callback_url']
        );
        return parent::getAppRegistrationUrl('http://www.tumblr.com/oauth/register', $params);
    }

}
