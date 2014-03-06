<?php
if (!class_exists('http_class')) { require_once 'httpclient/http.php'; }
if (!class_exists('oauth_client_class')) { require_once 'oauth_api/oauth_client.php'; }
// Wrapper for OAuth support for WordPress plugins.
// Check if class exists in case it was included by another plugin.
if (!class_exists('OAuthWP')) :
class OAuthWP extends oauth_client_class {

    protected function getAppRegistrationUrl ($base_url, $params = array()) {
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
}
endif; // !class_exists('OAuthWP')
