<?php
/**
 * Super-skeletal class to interact with Tumblr from Tumblr Crosspostr plugin.
 */

// Loads OAuth consumer class via OAuthWP class.
require_once 'OAuthWP_Tumblr.php';

class Tumblr_Crosspostr_API_Client extends Tumblr_OAuthWP_Plugin {
    private $api_key; //< Also the "Consumer key" the user entered.

    function __construct ($consumer_key = '', $consumer_secret = '') {
        $this->client = new OAuthWP_Tumblr;
        $this->client->server = 'Tumblr';
        $this->client->client_id = $consumer_key;
        $this->client->client_secret = $consumer_secret;
        $this->client->configuration_file = dirname(__FILE__) . '/oauth_api/oauth_configuration.json';
        $this->client->Initialize();

        return $this;
    }

    // Needed for some GET requests.
    public function setApiKey ($key) {
        $this->api_key = $key;
    }

    public function getUserBlogs () {
        $data = $this->talkToService('/user/info', array(), 'GET');
        // TODO: This could use some error handling?
        if (isset($data)) {
            return $data->response->user->blogs;
        } else {
            return false;
        }
    }

    public function getBlogInfo ($base_hostname) {
        $data = $this->talkToService("/blog/$base_hostname/info?api_key={$this->api_key}", array(), 'GET');
        // TODO: Handle error?
        return $data->response->blog;
    }

    public function getPosts ($base_hostname, $params = array()) {
        $url = "/blog/$base_hostname/posts?api_key={$this->api_key}";
        if (!empty($params)) {
            foreach ($params as $k => $v) {
                $url .= "&$k=$v";
            }
        }
        $data = $this->talkToService($url, array(), 'GET');
        return $data->response;
    }

    public function postToTumblrBlog ($blog, $params) {
        $api_method = "/blog/$blog/post";
        return $this->talkToService($api_method, $params);
    }
    public function editOnTumblrBlog ($blog, $params) {
        $api_method = "/blog/$blog/post/edit";
        return $this->talkToService($api_method, $params);
    }
    public function deleteFromTumblrBlog ($blog, $params) {
        $api_method = "/blog/$blog/post/delete";
        return $this->talkToService($api_method, $params);
    }

}
