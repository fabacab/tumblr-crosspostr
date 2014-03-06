<?php
require_once 'OAuthWP.php';

class OAuthWP_Tumblr extends OAuthWP {

    // Override so clients can ignore the API base url.
    public function CallAPI ($url, $method, $params, $opts, &$resp) {
        return parent::CallAPI('http://api.tumblr.com/v2' . $url, $method, $params, $opts, $resp);
    }

}

abstract class Tumblr_OAuthWP_Plugin extends Plugin_OAuthWP {

    public function getAppRegistrationUrl ($params = array()) {
        $x = array(
            'oac[title]' => $params['title'],
            'oac[description]' => $params['description'],
            'oac[url]' => $params['url'],
            'oac[admin_contact_email]' => $params['admin_contact_email'],
            'oac[default_callback_url]' => $params['default_callback_url']
        );
        return $this->appRegistrationUrl('http://www.tumblr.com/oauth/register', $x);
    }

}
