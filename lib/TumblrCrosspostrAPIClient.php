<?php
/**
 * Super-skeletal class to interact with Tumblr from Tumblr Crosspostr plugin.
 *
 * Uses PEAR's HTTP_OAuth.
 */

class Tumblr_Crosspostr_API_Client {
    private $request_token_url = 'http://www.tumblr.com/oauth/request_token';
    private $authorize_url = 'http://www.tumblr.com/oauth/authorize';
    private $access_token_url = 'http://www.tumblr.com/oauth/access_token';
    private $api_url = 'http://api.tumblr.com/v2';
    private $base_hostname;

    public $client; //< HTTP_OAuth_Consumer class.

    function __construct ($consumer_key, $consumer_secret, $oauth_token = false, $oauth_token_secret = false) {
        // Preferentially use our own PEAR packages.
        // TODO: Simplify this include?
        $tumblr_crosspostr_old_path = set_include_path(__DIR__ . '/pear/php' . PATH_SEPARATOR . get_include_path());
        require_once 'HTTP/OAuth/Consumer.php';
        // set_include_path($tumblr_crosspostr_old_path);

        // If there's not yet an active session,
        if (session_id() === '' ) { // (this avoids an E_NOTICE error)
            session_start(); // start a session to temporarily store oauth tokens.
        }
        $this->client = ($oauth_token && $oauth_token_secret) ?
            new HTTP_OAuth_Consumer($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret) :
            new HTTP_OAuth_Consumer($consumer_key, $consumer_secret);
        return $this->client;
    }

    public function getRequestToken ($callback_url) {
        $this->client->getRequestToken($this->request_token_url, $callback_url);
        $_SESSION['tumblr_crosspostr_oauth_token'] = $this->client->getToken();
        $_SESSION['tumblr_crosspostr_oauth_token_secret'] = $this->client->getTokenSecret();
    }

    public function getAuthorizeUrl () {
        return $this->client->getAuthorizeUrl($this->authorize_url);
    }

    public function getAccessToken ($oauth_verifier) {
        $this->client->getAccessToken($this->access_token_url, $oauth_verifier);
        $_SESSION['tumblr_crosspostr_oauth_token'] = $this->client->getToken();
        $_SESSION['tumblr_crosspostr_oauth_token_secret'] = $this->client->getTokenSecret();
    }

    public function setToken ($token) {
        $this->client->setToken($token);
    }
    public function setTokenSecret ($token) {
        $this->client->setTokenSecret($token);
    }

    public function getUserBlogs () {
        $data = $this->talkToTumblr('/user/info');
        // TODO: This could use some error handling?
        return $data->response->user->blogs;
    }

    public function postToTumblrBlog ($blog, $params) {
        $api_method = "/blog/$blog/post";
        $data = $this->talkToTumblr($api_method, $params);
        if (empty($data->response->id)) {
            // TODO: Handle error?
        } else {
            return $data->response->id;
        }
    }
    public function editOnTumblrBlog ($blog, $params) {
        $api_method = "/blog/$blog/post/edit";
        $data = $this->talkToTumblr($api_method, $params);
        if (empty($data->response->id)) {
            // TODO: Handle error?
        } else {
            return $data->response->id;
        }
    }
    public function deleteFromTumblrBlog($blog, $params) {
        $api_method = "/blog/$blog/post/delete";
        $data = $this->talkToTumblr($api_method, $params);
        return ($data->meta->status === 200) ? true : false;
    }

    private function talkToTumblr ($path, $params = array(), $method = 'POST') {
        $resp = $this->client->sendRequest("{$this->api_url}$path", $params, $method);
        return json_decode($resp->getBody());
    }
}
