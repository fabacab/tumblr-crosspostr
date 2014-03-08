<?php
/**
 * OAuthWP is a base class useful for creating WordPress plugins that
 * use any version of the OAuth protocol. It has two primary and very
 * simple classes: OAuthWP and Plugin_OAuthWP.
 *
 * OAuthWP extends Manuel Lemos's oauth_client_class to support each
 * version of the OAuth protocol (1, 1.0a, and 2.0).
 *
 * Plugin_OAuthWP is an abstract class that provides a skeleton for
 * common methods needed by WordPress plugins that use OAuth.
 * (For example, its $client property is an instance of OAuthWP.)
 */
if (!class_exists('http_class')) { require_once 'httpclient/http.php'; }
if (!class_exists('oauth_client_class')) { require_once 'oauth_api/oauth_client.php'; }
// Wrapper for OAuth support for WordPress plugins.
// Check if class exists in case it was included by another plugin.
if (!class_exists('OAuthWP')) :
abstract class OAuthWP extends oauth_client_class {

    public function authorize ($redirect_uri) {
        $this->redirect_uri = $redirect_uri;
        $s = $this->Process();
        if ($this->exit) {
            $this->Finalize($s);
            exit();
        }
    }

    public function completeAuthorization ($redirect_uri) {
        $this->redirect_uri = $redirect_uri;
        $this->Process();
        $tokens = array();
        $this->GetAccessToken($tokens);
        return $tokens;
    }

}
endif; // !class_exists('OAuthWP')

// Define a class that uses the above.
if (!class_exists('Plugin_OAuthWP')) :
abstract class Plugin_OAuthWP {
    public $client; //< OAuth consumer, should be a child of OAuthWP.

    abstract public function __construct ($consumer_key = '', $consumer_secret = '');

    abstract public function getAppRegistrationUrl ($params = array());

    protected function appRegistrationUrl ($base_url, $params = array()) {
        if (empty($base_url)) {
            throw new Exception('Empty base_url.');
        }
        $url = $base_url . '?';
        $i = 0;
        foreach ($params as $k => $v) {
            if (0 !== $i) { $url .= '&'; }
            $url .= $k . '=' . urlencode($v);
            $i++;
        }
        return $url;
    }

    public function authorize ($redirect_uri) {
        $this->client->authorize($redirect_uri);
    }

    public function completeAuthorization ($redirect_uri) {
        return $this->client->completeAuthorization($redirect_uri);
    }

    protected function talkToService ($path, $params = array(), $method = 'POST', $opts = array()) {
        $resp = null;
        if ($s = @$this->client->CallAPI($path, $method, $params, $opts, $resp)) {
            $this->client->Finalize($s);
        }
        return $resp;
    }

}
endif; // !class_exists('Plugin_OAuthWP')
