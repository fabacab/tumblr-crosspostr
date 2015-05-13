<?php
/**
 * Plugin Name: Tumblr Crosspostr
 * Plugin URI: https://github.com/meitar/tumblr-crosspostr/#readme
 * Description: Automatically crossposts to your Tumblr blog when you publish a post on your WordPress blog.
 * Version: 0.8.3
 * Author: Meitar Moscovitz
 * Author URI: http://Cyberbusking.org/
 * Text Domain: tumblr-crosspostr
 * Domain Path: /languages
 */

class Tumblr_Crosspostr {
    private $tumblr; //< Tumblr API manipulation wrapper.
    private $prefix = 'tumblr_crosspostr'; //< String to prefix plugin options, settings, etc.

    public function __construct () {
        add_action('plugins_loaded', array($this, 'registerL10n'));
        add_action('init', array($this, 'updateChangedSettings'));
        add_action('init', array($this, 'setSyncSchedules'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_menu', array($this, 'registerAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'registerAdminScripts'));
        add_action('admin_head', array($this, 'registerContextualHelp'));
        add_action('admin_notices', array($this, 'showAdminNotices'));
        add_action('add_meta_boxes', array($this, 'addMetaBox'));
        add_action('save_post', array($this, 'savePost'));
        add_action('before_delete_post', array($this, 'removeFromTumblr'));
        // run late, so themes have a chance to register support
        add_action('after_setup_theme', array($this, 'registerThemeSupport'), 700);

        add_action($this->prefix . '_sync_content', array($this, 'syncFromTumblrBlog'));

        // Template tag actions
        add_action($this->prefix . '_reblog_key', 'tumblr_reblog_key');

        add_filter('post_row_actions', array($this, 'addPostRowAction'), 10, 2);
        add_filter('plugin_row_meta', array($this, 'addPluginRowMeta'), 10, 2);
        add_filter('syn_add_links', array($this, 'addSyndicatedLinks'));

        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        $options = get_option($this->prefix . '_settings');
        // Initialize consumer if we can, set up authroization flow if we can't.
        require_once 'lib/TumblrCrosspostrAPIClient.php';
        if (isset($options['consumer_key']) && isset($options['consumer_secret'])) {
            $this->tumblr = new Tumblr_Crosspostr_API_Client($options['consumer_key'], $options['consumer_secret']);
            if (get_option($this->prefix . '_access_token') && get_option($this->prefix . '_access_token_secret')) {
                $this->tumblr->client->access_token = get_option($this->prefix . '_access_token');
                $this->tumblr->client->access_token_secret = get_option($this->prefix . '_access_token_secret');
            }
        } else {
            $this->tumblr = new Tumblr_Crosspostr_API_Client;
            add_action('admin_notices', array($this, 'showMissingConfigNotice'));
        }

        if (isset($options['debug'])) {
            $this->tumblr->client->debug = 1;
            $this->tumblr->client->debug_http = 1;
        }

        // OAuth connection workflow.
        if (isset($_GET[$this->prefix . '_oauth_authorize'])) {
            add_action('init', array($this, 'authorizeApp'));
        } else if (isset($_GET[$this->prefix . '_callback']) && !empty($_GET['oauth_verifier'])) {
            // Unless we're just saving the options, hook the final step in OAuth authorization.
            if (!isset($_GET['settings-updated'])) {
                add_action('init', array($this, 'completeAuthorization'));
            }
        }
    }

    // Detects old options/settings and migrates them to current version.
    public function updateChangedSettings () {
        $options = get_option($this->prefix . '_settings');
        $new_opts = array();
        foreach ($options as $opt_name => $opt_value) {
            switch ($opt_name) {
                // Rename "sync_tumblr" to "sync_content"
                case 'sync_tumblr':
                    foreach ($opt_value as $blog_to_sync) {
                        $new_opts['sync_content'][] = $blog_to_sync;
                        // Remove events with old name.
                        wp_unschedule_event(
                            wp_next_scheduled($this->prefix . '_sync_tumblr', array($blog_to_sync)),
                            $this->prefix . '_sync_tumblr',
                            array($blog_to_sync)
                        );
                    }
                    break;
                // Move the OAuth access tokens to their own options.
                case 'access_token':
                case 'access_token_secret':
                    update_option($this->prefix . '_' . $opt_name, $opt_value);
                    break;
                default:
                    // Do nothing for the rest, they're unchanged.
                    $new_opts[$opt_name] = $opt_value;
                    break;
            }
        }
        update_option($this->prefix . '_settings', $new_opts);
    }

    /**
     * Implements rel-syndication for POSSE as expected by the
     * Syndication Links plugin for WordPress.
     *
     * @see https://wordpress.org/plugins/syndication-links/
     * @see http://indiewebcamp.com/rel-syndication
     */
    public function addSyndicatedLinks ($urls) {
        $urls[] = $this->getSyndicatedAddress(get_the_id());
        return $urls;
    }

    public function showMissingConfigNotice () {
        $screen = get_current_screen();
        if ($screen->base === 'plugins') {
?>
<div class="updated">
    <p><a href="<?php print admin_url('options-general.php?page=' . $this->prefix . '_settings');?>" class="button"><?php esc_html_e('Connect to Tumblr', 'tumblr-crosspostr');?></a> &mdash; <?php esc_html_e('Almost done! Connect your blog to Tumblr to begin crossposting with Tumblr Crosspostr.', 'tumblr-crosspostr');?></p>
</div>
<?php
        }
    }

    private function showError ($msg) {
?>
<div class="error">
    <p><?php print esc_html($msg);?></p>
</div>
<?php
    }

    private function showNotice ($msg) {
?>
<div class="updated">
    <p><?php print $msg; // No escaping because we want links, so be careful. ?></p>
</div>
<?php
    }

    private function showDonationAppeal () {
?>
<div class="donation-appeal">
    <p style="text-align: center; font-size: larger; width: 70%; margin: 0 auto;"><?php print sprintf(
esc_html__('Tumblr Crosspostr is provided as free software, but sadly grocery stores do not offer free food. If you like this plugin, please consider %1$s to its %2$s. &hearts; Thank you!', 'tumblr-crosspostr'),
'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=meitarm%40gmail%2ecom&lc=US&amp;item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&amp;item_number=tumblr%2dcrosspostr&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">' . esc_html__('making a donation', 'tumblr-crosspostr') . '</a>',
'<a href="http://Cyberbusking.org/">' . esc_html__('houseless, jobless, nomadic developer', 'tumblr-crosspostr') . '</a>'
);?></p>
</div>
<?php
    }

    public function registerThemeSupport () {
        add_theme_support(
            'post-formats',
            $this->diffThemeSupport(array(
                'link',
                'image',
                'quote',
                'video',
                'audio',
                'chat'
            ), 'post-formats')
        );
    }

    /**
     * Returns the difference between a requested and existing theme support for a feature.
     *
     * @param array $new_array The options of a feature to query.
     * @param string $feature The feature to query.
     * @return array The difference, each element as an argument to original add_theme_support() call.
     */
    private function diffThemeSupport ($new_array, $feature) {
        $x = get_theme_support($feature);
        if (is_bool($x)) { $x = array(); }
        $y = (empty($x)) ? array() : $x[0];
        return array_merge($y, array_diff($new_array, $y));
    }

    public function authorizeApp () {
        check_admin_referer('tumblr-authorize');
        $this->tumblr->authorize(admin_url('options-general.php?page=' . $this->prefix . '_settings&' . $this->prefix . '_callback'));
    }

    public function completeAuthorization () {
        $tokens = $this->tumblr->completeAuthorization(admin_url('options-general.php?page=' . $this->prefix . '_settings&' . $this->prefix . '_callback'));
        update_option($this->prefix . '_access_token', $tokens['value']);
        update_option($this->prefix . '_access_token_secret', $tokens['secret']);
    }

    public function registerL10n () {
        load_plugin_textdomain('tumblr-crosspostr', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function registerSettings () {
        register_setting(
            $this->prefix . '_settings',
            $this->prefix . '_settings',
            array($this, 'validateSettings')
        );
    }

    public function registerContextualHelp () {
        $screen = get_current_screen();
        if ($screen->id !== 'post') { return; }
        $html = '<p>' . esc_html__('You can automatically copy this post to your Tumblr blog:', 'tumblr-crosspostr') . '</p>'
        . '<ol>'
        . '<li>' . sprintf(
            esc_html__('Compose your post for WordPress as you normally would, with the appropriate %sPost Format%s.', 'tumblr-crosspostr'),
            '<a href="#formatdiv">', '</a>'
            ) . '</li>'
        . '<li>' . sprintf(
            esc_html__('In %sthe Tumblr Crosspostr box%s, ensure the "Send this post to Tumblr?" option is set to "Yes." (You can set it to "No" if you do not want to copy this post to Tumblr.)', 'tumblr-crosspostr'),
            '<a href="#tumblr-crosspostr-meta-box">', '</a>'
            ) . '</li>'
        . '<li>' . esc_html__('If you have more than one Tumblr, choose the one you want to send this post to from the "Send to my Tumblr blog" list.', 'tumblr-crosspostr') . '</li>'
        . '<li>' . esc_html__('Optionally, enter any additional details specifically for Tumblr, such as the "Content source" field.', 'tumblr-crosspostr') . '</li>'
        . '</ol>'
        . '<p>' . esc_html__('When you are done, click "Publish" (or "Save Draft"), and Tumblr Crosspostr will send your post to the Tumblr blog you chose.', 'tumblr-crosspostr') . '</p>'
        . '<p>' . esc_html__('Note that Tumblr does not allow you to change the post format after you have saved a copy of your post, so please be sure you choose the appropriate Post Format before you save your post.', 'tumblr-crosspostr') . '</p>';
        ob_start();
        $this->showDonationAppeal();
        $x = ob_get_contents();
        ob_end_clean();
        $html .= $x;
        $screen->add_help_tab(array(
            'id' => $this->prefix . '-' . $screen->base . '-help',
            'title' => __('Crossposting to Tumblr', 'tumblr-crosspostr'),
            'content' => $html
        ));

        $x = esc_html__('Tumblr Crosspostr:', 'tumblr-crosspostr');
        $y = esc_html__('Tumblr Crosspostr support forum', 'tumblr-crosspostr');
        $z = esc_html__('Donate to Tumblr Crosspostr', 'tumblr-crosspostr');
        $sidebar = <<<END_HTML
<p><strong>$x</strong></p>
<p><a href="https://wordpress.org/support/plugin/tumblr-crosspostr" target="_blank">$y</a></p>
<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=meitarm%40gmail%2ecom&lc=US&item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&item_number=tumblr%2dcrosspostr&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted" target="_blank">&hearts; $z &hearts;</a></p>
END_HTML;
        $screen->set_help_sidebar($screen->get_help_sidebar() . $sidebar);
    }

    private function getSyndicatedAddress ($post_id) {
        $url = '';
        if ($id = get_post_meta($post_id, 'tumblr_post_id', true)) {
            $base_hostname = $this->getTumblrBasename($post_id);
            $url .= "http://{$base_hostname}/post/{$id}";
        }
        return $url;
    }

    public function addPostRowAction ($actions, $post) {
        $id = get_post_meta($post->ID, 'tumblr_post_id', true);
        if ($id) {
            $actions['view_on_tumblr'] = '<a href="' . $this->getSyndicatedAddress($post->ID) . '">' . esc_html__('View post on Tumblr', 'tumblr-crosspostr') . '</a>';
        }
        return $actions;
    }

    public function addPluginRowMeta ($links, $file) {
        if (false !== strpos($file, basename(__FILE__))) {
            $new_links = array(
                '&hearts; <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=meitarm%40gmail%2ecom&lc=US&amp;item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&amp;item_number=tumblr%2dcrosspostr&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">' . esc_html__('Donate to Tumblr Crosspostr', 'tumblr-crosspostr') . '</a> &hearts;',
                '<a href="https://wordpress.org/support/plugin/tumblr-crosspostr/">' . esc_html__('Tumblr Crosspostr support forum', 'tumblr-crosspostr') . '</a>'
            );
            $links = array_merge($links, $new_links);
        }
        return $links;
    }

    private function WordPressPostFormat2TumblrPostType ($format) {
        switch ($format) {
            case 'image':
            case 'gallery':
                $type = 'photo';
                break;
            case 'quote':
                $type = 'quote';
                break;
            case 'link':
                $type = 'link';
                break;
            case 'audio':
                $type = 'audio';
                break;
            case 'video':
                $type = 'video';
                break;
            case 'chat':
                $type = 'chat';
                break;
            case 'aside':
            case false:
            default:
                $type = 'text';
                break;
        }
        return $type;
    }

    private function TumblrPostType2WordPressPostFormat ($type) {
        switch ($type) {
            case 'quote':
            case 'audio':
            case 'video':
            case 'chat':
            case 'link':
                $format = $type;
                break;
            case 'photo':
                $format = 'image';
                break;
            case 'answer':
            case 'text':
            default:
                $format = '';
        }
        return $format;
    }

    /**
     * Translates a WordPress post status to a Tumblr post state.
     *
     * @param string $status The WordPress post status to translate.
     * @return mixed The translates Tumblr post state or false if the WordPress status has no equivalently compatible state on Tumblr.
     */
    private function WordPressStatus2TumblrState ($status) {
        switch ($status) {
            case 'draft':
            case 'private':
                $state = $status;
                break;
            case 'publish':
                $state = 'published';
                break;
            case 'future':
                $state = 'queue';
                break;
            case 'auto-draft':
            case 'inherit':
            case 'pending':
            default:
                $state = false;
        }
        return $state;
    }

    private function TumblrState2WordPressStatus ($state) {
        switch ($state) {
            case 'draft':
            case 'private':
                $status = $state;
                break;
            case 'queue':
                $status = 'future';
            case 'published':
            default:
                $status = 'publish';
        }
        return $status;
    }

    private function isPostCrosspostable ($post_id) {
        $options = get_option($this->prefix . '_settings');
        $crosspostable = true;

        // Do not crosspost if this post is excluded by a certain category.
        if (isset($options['exclude_categories']) && in_category($options['exclude_categories'], $post_id)) {
            $crosspostable = false;
        }

        // Do not crosspost if this specific post was excluded.
        if ('N' === get_post_meta($post_id, $this->prefix . '_crosspost', true)) {
            $crosspostable = false;
        }

        // Do not crosspost unsupported post states.
        if (!$this->WordPressStatus2TumblrState(get_post_status($post_id))) {
            $crosspostable = false;
        }

        return $crosspostable;
    }

    /**
     * Translates a WordPress post data for Tumblr's API.
     *
     * @param int $post_id The ID number of the WordPress post.
     * @return mixed A simple object representing data for Tumblr or FALSE if the given post should not be crossposted.
     */
    private function prepareForTumblr ($post_id) {
        if (!$this->isPostCrosspostable($post_id)) { return false; }

        $options = get_option($this->prefix . '_settings');
        $custom = get_post_custom($post_id);

        $prepared_post = new stdClass();

        // Set the post's Tumblr destination.
        $base_hostname = false;
        if (!empty($_POST[$this->prefix . '_destination'])) {
            $base_hostname = sanitize_text_field($_POST[$this->prefix . '_destination']);
        } else if (!empty($custom['tumblr_base_hostname'][0])) {
            $base_hostname = sanitize_text_field($custom['tumblr_base_hostname'][0]);
        } else {
            $base_hostname = sanitize_text_field($options['default_hostname']);
        }
        if ($base_hostname !== $options['default_hostname']) {
            update_post_meta($post_id, 'tumblr_base_hostname', $base_hostname);
        }
        $prepared_post->base_hostname = $base_hostname;

        // Set "Content source" meta field.
        if (!empty($_POST[$this->prefix . '_meta_source_url'])) {
            $source_url = sanitize_text_field($_POST[$this->prefix . '_meta_source_url']);
            update_post_meta($post_id, 'tumblr_source_url', $source_url);
        } else if (!empty($custom['tumblr_source_url'][0])) {
            $source_url = sanitize_text_field($custom['tumblr_source_url'][0]);
        } else if ('Y' === $options['auto_source']) {
            $source_url = get_permalink($post_id);
            delete_post_meta($post_id, 'tumblr_source_url');
        } else {
            $source_url = false;
        }

        $format = (defined('DOING_AJAX') && DOING_AJAX) ? $_POST['post_format'] : get_post_format($post_id);
        $state = $this->WordPressStatus2TumblrState(get_post_status($post_id));
        $tags = array();
        if ($t = get_the_tags($post_id)) {
            foreach ($t as $tag) {
                // Decode manually so that's the ONLY decoded entity.
                $tags[] = str_replace('&amp;', '&', $tag->name);
            }
        }
        $common_params = array(
            'type' => $this->WordPressPostFormat2TumblrPostType($format),
            'state' => $state,
            'tags' => implode(',', $tags),
            'date' => get_post_time('Y-m-d H:i:s', true, $post_id) . ' GMT',
            'format' => 'html', // Tumblr's "formats" are always either 'html' or 'markdown'
            'slug' => get_post_field('post_name', $post_id)
        );
        if ($source_url) { $common_params['source_url'] = $source_url; }

        if (!empty($options['exclude_tags'])) { unset($common_params['tags']); }

        if (!empty($options['additional_tags'])) {
            if (!isset($common_params['tags'])) {
                $common_params['tags'] = '';
            }
            $common_params['tags'] = implode(',', array_merge(explode(',', $common_params['tags']), $options['additional_tags']));
        }

        $post_params = $this->prepareParamsByPostType($post_id, $common_params['type']);

        if (!empty($options['additional_markup'])) {
            $html = $this->replacePlaceholders($options['additional_markup'], $post_id);
            foreach ($post_params as $k => $v) {
                switch ($k) {
                    case 'body':
                    case 'caption':
                    case 'description':
                        $post_params[$k] = $v . $html; // append
                        break;
                }
            }
        }

        $prepared_post->params = array_merge($common_params, $post_params);

        $tumblr_id = get_post_meta($post_id, 'tumblr_post_id', true); // Will be empty if none exists.
        $prepared_post->tumblr_id = (empty($tumblr_id)) ? false : $tumblr_id;

        return $prepared_post;
    }

    public function savePost ($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        if (!$this->isConnectedToService()) { return; }

        // Only crosspost regular posts unless asked to cross-post other types.
        $options = get_option($this->prefix . '_settings');
        $post_types = array('post');
        if (!empty($options['post_types'])) {
            $post_types = array_merge($post_types, $options['post_types']);
        }
        $post_types = apply_filters($this->prefix . '_save_post_types', $post_types);
        if (!in_array(get_post_type($post_id), $post_types)) {
            return;
        }

        if (isset($_POST[$this->prefix . '_use_excerpt'])) {
            update_post_meta($post_id, $this->prefix . '_use_excerpt', 1);
        } else {
            delete_post_meta($post_id, $this->prefix . '_use_excerpt');
        }

        if (isset($_POST[$this->prefix . '_crosspost']) && 'N' === $_POST[$this->prefix . '_crosspost']) {
            update_post_meta($post_id, $this->prefix . '_crosspost', 'N'); // 'N' means "no"
            return;
        } else {
            delete_post_meta($post_id, $this->prefix . '_crosspost', 'N');
        }

        if ($prepared_post = $this->prepareForTumblr($post_id)) {
            if (isset($_POST[$this->prefix . '_send_tweet'])) {
                if (!empty($_POST[$this->prefix . '_tweet_text'])) {
                    $prepared_post->params['tweet'] = sanitize_text_field($_POST[$this->prefix . '_tweet_text']);
                }
            } else {
                $prepared_post->params['tweet'] = 'off';
            }
//            // NOTE: WordPress intentionally adds slashes. Grr.
//            //       See http://codex.wordpress.org/Function_Reference/stripslashes_deep#Good_Coding_Practice
//            if (get_magic_quotes_gpc()) {
//                $prepared_post->params['tweet'] = stripslashes_deep($prepared_post->params['tweet']);
//            }
            $prepared_post->params['tweet'] = stripslashes_deep($prepared_post->params['tweet']);

            $prepared_post = apply_filters($this->prefix . '_prepared_post', $prepared_post);
            // We still have a post, right? in case someone forgets to return
            if (empty($prepared_post)) { return; }

            $data = $this->crosspostToTumblr($prepared_post->base_hostname, $prepared_post->params, $prepared_post->tumblr_id);
            if (empty($data->response->id)) {
                $msg = esc_html__('Crossposting to Tumblr failed.', 'tumblr-crosspostr');
                if (isset($data->meta)) {
                    $msg .= esc_html__(' Remote service said:', 'tumblr-crosspostr');
                    $msg .= '<blockquote>';
                    $msg .= esc_html__('Response code:', 'tumblr-crosspostr') . " {$data->meta->status}<br />";
                    $msg .= esc_html__('Response message:', 'tumblr-crosspostr') . " {$data->meta->msg}<br />";
                    $msg .= '</blockquote>';
                }
                switch ($data->meta->status) {
                    case 401:
                        $msg .= ' ' . $this->maybeCaptureDebugOf($data);
                        $msg .= sprintf(
                            esc_html__('This might mean your %1$s are invalid or have been revoked by Tumblr. If everything looks fine on your end, you may want to ask %2$s to confirm your app is still allowed to use their API.', 'tumblr-crosspostr'),
                            '<a href="' . admin_url('options-general.php?page=' . $this->prefix . '_settings') . '">' . esc_html__('OAuth credentials', 'tumblr-crosspostr') . '</a>',
                            $this->linkToTumblrSupport()
                        );
                        break;
                    default:
                        $msg .= ' ' . $this->maybeCaptureDebugOf($data);
                        $msg .= sprintf(
                            esc_html__('Unfortunately, I have no idea what Tumblr is talking about. Consider asking %1$s for help. Tell them you are using %2$s, that you got the error shown above, and ask them to please support this tool. \'Cause, y\'know, it\'s not like you don\'t already have a WordPress blog, and don\'t they want you to use Tumblr, too?', 'tumblr-crosspostr'),
                            $this->linkToTumblrSupport(),
                            '<a href="https://wordpress.org/plugins/tumblr-crosspostr/">' . esc_html__('Tumblr Crosspostr', 'tumblr-crosspostr') . '</a>'
                        );
                        break;
                }
                if (!isset($options['debug'])) {
                    $msg .= '<br /><br />' . sprintf(
                        esc_html__('Additionally, you may want to turn on Tumblr Crosspostr\'s "%s" option to get more information about this error the next time it happens.', 'tumblr-crosspostr'),
                        '<a href="' . admin_url('options-general.php?page=' . $this->prefix . '_settings#' . $this->prefix . '_debug') . '">'
                        . esc_html__('Enable detailed debugging information?', 'tumblr-crosspostr') . '</a>'
                    );
                }
                $this->addAdminNotices($msg);
            } else {
                update_post_meta($post_id, 'tumblr_post_id', $data->response->id);
                if ($prepared_post->params['state'] === 'published') {
                    $this->addAdminNotices(
                        esc_html__('Post crossposted.', 'tumblr-crosspostr') . ' <a href="' . $this->getSyndicatedAddress($post_id) . '">' . esc_html__('View post on Tumblr', 'tumblr-crosspostr') . '</a>'
                    );
                    if ($msg = $this->maybeCaptureDebugOf($data)) {
                        $msg = $this->maybeCaptureDebugOf($prepared_post) . '<br /><br />' . $msg;
                        $this->addAdminNotices($msg);
                    }
                }
            }
        }
    }

    private function captureDebugOf ($var) {
        ob_start();
        var_dump($var);
        $str = ob_get_contents();
        ob_end_clean();
        return $str;
    }

    private function maybeCaptureDebugOf ($var) {
        $msg = '';
        $options = get_option($this->prefix . '_settings');
        if (isset($options['debug'])) {
            $msg .= esc_html__('Debug output:', 'tumblr-crosspostr');
            $msg .= '<pre>' . $this->captureDebugOf($var) . '</pre>';
        }
        return $msg;
    }

    private function linkToTumblrSupport () {
        return '<a href="http://www.tumblr.com/help?form">' . esc_html__('Tumblr Support', 'tumblr-crosspostr') . '</a>';
    }

    private function addAdminNotices ($msgs) {
        if (is_string($msgs)) { $msgs = array($msgs); }
        $notices = get_option('_' . $this->prefix . '_admin_notices');
        if (empty($notices)) {
            $notices = array();
        }
        $notices = array_merge($notices, $msgs);
        update_option('_' . $this->prefix . '_admin_notices', $notices);
    }

    public function showAdminNotices () {
        $notices = get_option('_' . $this->prefix . '_admin_notices');
        if ($notices) {
            foreach ($notices as $msg) {
                $this->showNotice($msg);
            }
            delete_option('_' . $this->prefix . '_admin_notices');
        }
    }

    private function replacePlaceholders ($str, $post_id) {
        $placeholders = array(
            '%permalink%',
            '%the_title%',
            '%blog_url%',
            '%blog_name%'
        );
        foreach ($placeholders as $x) {
            if (0 === strpos($x, '%blog_')) {
                $arg = substr($x, 6, -1);
                $str = str_replace($x, get_bloginfo($arg), $str);
            } else {
                $func = 'get_' . substr($x, 1, -1);
                $valid_funcs = array(
                    'get_permalink',
                    'get_the_title'
                );
                if (in_array($func, $valid_funcs, true)) {
                    $str = str_replace($x, call_user_func($func, $post_id), $str);
                }
            }
        }
        return $str;
    }

    /**
     * Issues a Tumblr API call.
     *
     * @param string $blog The Tumblr blog's base hostname.
     * @param array @params Any additional parameters for the request.
     * @param int $tumblr_id The ID of a specific Tumblr post (only needed if editing or deleting this post).
     * @param bool $deleting Whether or not to delete, rather than to edit, a specific Tumblr post.
     * @return array Tumblr's decoded JSON response.
     */
    private function crosspostToTumblr ($blog, $params, $tumblr_id = false, $deleting = false) {
        // TODO: Smoothen this deleting thing.
        //       Cancel WordPress deletions if Tumblr deletions aren't working?
        if ($deleting === true && $tumblr_id) {
            $params['id'] = $tumblr_id;
            return $this->tumblr->deleteFromTumblrBlog($blog, $params);
        } else if ($tumblr_id) {
            $params['id'] = $tumblr_id;
            return $this->tumblr->editOnTumblrBlog($blog, $params);
        } else {
            return $this->tumblr->postToTumblrBlog($blog, $params);
        }
    }

    private function prepareParamsByPostType ($post_id, $type) {
        $post_body = get_post_field('post_content', $post_id);
        $post_excerpt = get_post_field('post_excerpt', $post_id);
        // Mimic wp_trim_excerpt() without The Loop.
        if (empty($post_excerpt)) {
            $text = $post_body;
            $text = strip_shortcodes($text);
            $text = apply_filters('the_content', $text);
            $text = str_replace(']]>', ']]&gt;', $text);
            $text = wp_trim_words($text);
            $post_excerpt = $text;
        }

        $e = $this->getTumblrUseExcerpt($post_id); // Use excerpt?
        $r = array();
        switch ($type) {
            case 'photo':
                $r['caption'] = ($e)
                    ? apply_filters('the_excerpt', $post_excerpt)
                    : apply_filters('the_content', $this->strip_only($post_body, 'img', true, 1));
                $pattern = '/(?:<a.*?href="(.*?)"[^>]*>)?<img.*?src="(.*?)".*?\/?>/';
                $m = array();
                preg_match($pattern, $post_body, $m);
                if (empty($m)) {
                    $r['link'] = $r['source'] = $this->extractByRegex($pattern, get_the_post_thumbnail($post_id, 'full'), 2);
                } else if (empty($m[1])) {
                    $r['link'] = $r['source'] = $m[2];
                } else {
                    $r['link'] = $m[1];
                    $r['source'] = $m[2];
                }
                break;
            case 'quote':
                $pattern = '/<blockquote.*?>(.*?)<\/blockquote>/s';
                $r['quote'] = wpautop($this->extractByRegex($pattern, $post_body, 1));
                $len = strlen($this->extractByRegex($pattern, $post_body, 0));
                $r['source'] = ($e)
                    ? apply_filters('the_excerpt', $post_excerpt)
                    : apply_filters('the_content', substr($post_body, $len));
                break;
            case 'link':
                $r['title'] = get_post_field('post_title', $post_id);
                $r['url'] = ($e && preg_match('/<a.*?href="(.*?)".*?>/', $post_excerpt))
                    ? $this->extractByRegex('/<a.*?href="(.*?)".*?>/', $post_excerpt, 1)
                    : $this->extractByRegex('/<a.*?href="(.*?)".*?>/', $post_body, 1);
                $r['description'] = ($e)
                    ? apply_filters('the_excerpt', $post_excerpt)
                    : apply_filters('the_content', $post_body);
                break;
            case 'chat':
                $r['title'] = get_post_field('post_title', $post_id);
                $r['conversation'] = wp_strip_all_tags($post_body);
                break;
            case 'audio':
                $r['caption'] = ($e)
                    ? apply_filters('the_excerpt', $post_excerpt)
                    : apply_filters('the_content', $post_body);
                // Strip after apply_filters in case shortcode is used to generate <audio> element.
                $r['caption'] = $this->strip_only($r['caption'], 'audio', 1);
                $r['external_url'] = $this->extractByRegex('/(?:href|src)="(.*?\.(?:mp3|wav|wma|aiff|ogg|ra|ram|rm|mid|alac|flac))".*?>/i', $post_body, 1);
                break;
            case 'video':
                $r['caption'] = ($e)
                    ? apply_filters('the_excerpt', $post_excerpt)
                    : apply_filters('the_content', $this->strip_only($post_body, 'iframe', true, 1));

                $pattern_youtube = '/youtube(?:-nocookie)\.com\/(?:v|embed)\/([\w\-]+)/';
                $pattern_vimeo = '/player\.vimeo\.com\/video\/([0-9]+)/';
                if (preg_match($pattern_youtube, $post_body)) {
                    $r['embed'] = 'https://www.youtube.com/watch?v='
                        . $this->extractByRegex($pattern_youtube, $post_body, 1);
                } else if (preg_match($pattern_vimeo, $post_body)) {
                    $r['embed'] = '<iframe src="//' . $this->extractByRegex($pattern_vimeo, $post_body, 0) . '">'
                        . '<a href="//vimeo.com/' . $this->extractByRegex($pattern_vimeo, $post_body, 1). '">'
                        . esc_html__('Watch this video.', 'tumblr-crosspostr') . '</a></iframe>';
                } else {
                    // Pass along the entirety of any unrecognized <iframe>.
                    $r['embed'] = $this->extractByRegex('/<iframe.*?<\/iframe>/', $post_body, 0);
                }
                break;
            case 'text':
                $r['title'] = get_post_field('post_title', $post_id);
                // fall through
            case 'aside':
            default:
                $r['body'] = ($e)
                    ? apply_filters('the_excerpt', $post_excerpt)
                    : apply_filters('the_content', $post_body);
                break;
        }
        return $r;
    }

    // TODO: Add error handling for when the $post_body doesn't give
    //       us what we need to fulfill the Tumblr post type req's.
    /**
     * Extracts a given string from another string according to a regular expression.
     *
     * @param string $pattern The PCRE-compatible regular expression.
     * @param string $str The source from which to extract text matching the $pattern.
     * @param int $group If the regex uses capture groups, the number of the capture group to return.
     * @return string The matched text.
     */
    private function extractByRegex ($pattern, $str, $group = 0) {
        $matches = array();
        $x = preg_match($pattern, $str, $matches);
        return (!empty($matches[$group])) ? $matches[$group] : $x;
    }

    private function getTumblrBasename ($post_id) {
        $d = get_post_meta($post_id, 'tumblr_base_hostname', true);
        if (empty($d)) {
            $options = get_option($this->prefix . '_settings');
            $d = (isset($options['default_hostname'])) ? $options['default_hostname'] : '';
        }
        return $d;
    }

    private function getTumblrUseExcerpt ($post_id) {
        $e = get_post_meta($post_id, $this->prefix . '_use_excerpt', true);
        if (empty($e)) {
            $options = get_option($this->prefix . '_settings');
            $e = (isset($options['use_excerpt'])) ? $options['use_excerpt'] : 0;
        }
        return intval($e);
    }

    public function removeFromTumblr ($post_id) {
        $options = get_option($this->prefix . '_settings');
        $tumblr_id = get_post_meta($post_id, 'tumblr_post_id', true);
        $this->crosspostToTumblr($this->getTumblrBasename($post_id), array(), $tumblr_id, true);
    }

    public function getReblogKey ($post_id) {
        $reblog_key = get_post_meta($post_id, 'tumblr_reblog_key', true);
        if (empty($reblog_key)) {
            $tumblr_id = get_post_meta($post_id, 'tumblr_post_id', true);
            $options = get_option($this->prefix . '_settings');
            $this->tumblr->setApiKey($options['consumer_key']);
            $resp = $this->tumblr->getPosts($this->getTumblrBaseName($post_id), array('id' => $tumblr_id));
            $reblog_key = $resp->posts[0]->reblog_key;
            update_post_meta($post_id, 'tumblr_reblog_key', $reblog_key);
        }
        return $reblog_key;
    }

    /**
     * @param array $input An array of of our unsanitized options.
     * @return array An array of sanitized options.
     */
    public function validateSettings ($input) {
        $safe_input = array();
        foreach ($input as $k => $v) {
            switch ($k) {
                case 'consumer_key':
                    if (empty($v)) {
                        $errmsg = __('Consumer key cannot be empty.', 'tumblr-crosspostr');
                        add_settings_error($this->prefix . '_settings', 'empty-consumer-key', $errmsg);
                    }
                    $safe_input[$k] = sanitize_text_field($v);
                break;
                case 'consumer_secret':
                    if (empty($v)) {
                        $errmsg = __('Consumer secret cannot be empty.', 'tumblr-crosspostr');
                        add_settings_error($this->prefix . '_settings', 'empty-consumer-secret', $errmsg);
                    }
                    $safe_input[$k] = sanitize_text_field($v);
                break;
                case 'default_hostname':
                    $safe_input[$k] = sanitize_text_field($v);
                break;
                case 'sync_content':
                    $safe_input[$k] = array();
                    foreach ($v as $x) {
                        $safe_input[$k][] = sanitize_text_field($x);
                    }
                break;
                case 'exclude_categories':
                case 'post_types':
                case 'import_to_categories':
                    $safe_v = array();
                    foreach ($v as $x) {
                        $safe_v[] = sanitize_text_field($x);
                    }
                    $safe_input[$k] = $safe_v;
                break;
                case 'auto_source':
                    if ('Y' === $v || 'N' === $v) {
                        $safe_input[$k] = $v;
                    }
                break;
                case 'additional_markup':
                    $safe_input[$k] = trim($v);
                break;
                case 'use_excerpt':
                case 'exclude_tags':
                case 'auto_tweet':
                case 'debug':
                    $safe_input[$k] = intval($v);
                break;
                case 'additional_tags':
                    if (is_string($v)) {
                        $tags = explode(',', $v);
                        $safe_tags = array();
                        foreach ($tags as $t) {
                            $safe_tags[] = sanitize_text_field($t);
                        }
                        $safe_input[$k] = $safe_tags;
                    }
                break;
            }
        }
        return $safe_input;
    }

    public function registerAdminMenu () {
        add_options_page(
            __('Tumblr Crosspostr Settings', 'tumblr-crosspostr'),
            __('Tumblr Crosspostr', 'tumblr-crosspostr'),
            'manage_options',
            $this->prefix . '_settings',
            array($this, 'renderOptionsPage')
        );

        add_management_page(
            __('Tumblrize Archives', 'tumblr-crosspostr'),
            __('Tumblrize Archives', 'tumblr-crosspostr'),
            'manage_options',
            $this->prefix . '_crosspost_archives',
            array($this, 'dispatchTumblrizeArchivesPages')
        );
    }

    public function registerAdminScripts () {
        wp_register_style('tumblr-crosspostr', plugins_url('tumblr-crosspostr.css', __FILE__));
        wp_enqueue_style('tumblr-crosspostr');
    }

    public function addMetaBox ($post) {
        $options = get_option($this->prefix . '_settings');
        if (empty($options['post_types'])) { $options['post_types'] = array(); }
        $options['post_types'][] = 'post';
        $options['post_types'] = apply_filters($this->prefix . '_meta_box_post_types', $options['post_types']);
        foreach ($options['post_types'] as $cpt) {
            add_meta_box(
                'tumblr-crosspostr-meta-box',
                __('Tumblr Crosspostr', 'tumblr-crosspostr'),
                array($this, 'renderMetaBox'),
                $cpt,
                'side'
            );
        }
    }

    private function isConnectedToService () {
        $options = get_option($this->prefix . '_settings');
        return isset($this->tumblr) && get_option($this->prefix . '_access_token');
    }

    private function disconnectFromService () {
        @$this->tumblr->client->ResetAccessToken(); // Suppress session_start() warning.
        delete_option($this->prefix . '_access_token');
        delete_option($this->prefix . '_access_token_secret');
    }

    public function renderMetaBox ($post) {
        if (!$this->isConnectedToService()) {
            $this->showError(__('Tumblr Crossposter does not yet have a connection to Tumblr. Are you sure you connected Tumblr Crosspostr to your Tumblr account?', 'tumblr-crosspostr'));
            return;
        }
        $options = get_option($this->prefix . '_settings');

        // Set default crossposting options for this post.
        $x = get_post_meta($post->ID, $this->prefix . '_crosspost', true);
        $d = $this->getTumblrBasename($post->ID);
        $e = $this->getTumblrUseExcerpt($post->ID);
        $s = get_post_meta($post->ID, 'tumblr_source_url', true);

        $tumblr_id = get_post_meta($post->ID, 'tumblr_post_id', true);
        if ('publish' === $post->post_status && $tumblr_id) {
?>
<p>
    <a href="<?php print esc_attr($this->getSyndicatedAddress($post->ID));?>" class="button button-small"><?php esc_html_e('View post on Tumblr', 'tumblr-crosspostr');?></a>
</p>
<?php
        }
?>
<fieldset>
    <legend style="display:block;"><?php esc_html_e('Send this post to Tumblr?', 'tumblr-crosspostr');?></legend>
    <p class="description" style="float: right; width: 75%;"><?php esc_html_e('If this post is in a category that Tumblr Crosspostr excludes, this will be ignored.', 'tumblr-crosspostr');?></p>
    <ul>
        <li><label><input type="radio" name="<?php esc_attr_e($this->prefix);?>_crosspost" value="Y"<?php if ('N' !== $x) { print ' checked="checked"'; }?>> <?php esc_html_e('Yes', 'tumblr-crosspostr');?></label></li>
        <li><label><input type="radio" name="<?php esc_attr_e($this->prefix);?>_crosspost" value="N"<?php if ('N' === $x) { print ' checked="checked"'; }?>> <?php esc_html_e('No', 'tumblr-crosspostr');?></label></li>
    </ul>
</fieldset>
<fieldset>
    <legend><?php esc_html_e('Crossposting options', 'tumblr-crosspostr');?></legend>
    <details open="open">
        <summary><?php esc_html_e('Destination & content', 'tumblr-crosspostr');?></summary>
        <p><label>
            <?php esc_html_e('Send to my Tumblr blog titled', 'tumblr-crosspostr');?>
            <?php print $this->tumblrBlogsSelectField(array('name' => $this->prefix . '_destination'), $d);?>
        </label></p>
        <p><label>
            <input type="checkbox" name="<?php esc_attr_e($this->prefix);?>_use_excerpt" value="1"
                <?php if (1 === $e) { print 'checked="checked"'; } ?>
                title="<?php esc_html_e('Uncheck to send post content as crosspost content.', 'tumblr-crosspostr');?>"
                />
            <?php esc_html_e('Send excerpt instead of main content?', 'tumblr-crosspostr');?>
        </label></p>
        <p>
            <label><?php esc_html_e('Content source:', 'tumblr-crosspostr');?>
                <input id="<?php esc_attr_e($this->prefix);?>_meta_source_url" type="text"
                    name="<?php esc_attr_e($this->prefix);?>_meta_source_url"
                    title="<?php esc_attr_e('Set the content source of this post on Tumblr by pasting the URL where you found the content you are blogging about.', 'tumblr-crosspostr'); if ($options['auto_source'] === 'Y') { print ' ' . esc_attr__('Leave this blank to set the content source URL of your Tumblr post to the permalink of this WordPress post.', 'tumblr-crosspostr'); } ?>"
                    value="<?php esc_attr_e($s);?>"
                    placeholder="<?php esc_attr_e('//original-source.com/', 'tumblr-crosspostr');?>" />
                <span class="description"><?php esc_html_e('Provide source attribution, if relevant.', 'tumblr-crosspostr');?></span>
            </label>
        </p>
    </details>
</fieldset>
    <?php if ($post->post_status !== 'publish') { ?>
<fieldset>
    <legend><?php esc_html_e('Social media broadcasts', 'tumblr-crosspostr');?></legend>
    <details open="open"><!-- Leave open until browsers work out their keyboard accessibility issues with this. -->
        <summary><?php esc_html_e('Twitter', 'tumblr-crosspostr');?></summary>
        <p>
            <label>
                <input type="checkbox" name="<?php esc_attr_e($this->prefix);?>_send_tweet" value="1"
                    <?php if (!empty($options['auto_tweet'])) { ?>checked="checked"<?php } ?>
                    title="<?php esc_html_e('Uncheck to disable the auto-tweet.', 'tumblr-crosspostr');?>"
                    />
                <?php esc_html_e('Send tweet?', 'tumblr-crosspostr');?>
            </label>
            <label>
                <input id="<?php esc_attr_e($this->prefix);?>_tweet_text" type="text"
                    name="<?php esc_attr_e($this->prefix);?>_tweet_text"
                    title="<?php esc_attr_e('If your Tumblr automatically tweets new posts to your Twitter account, you can customize the default tweet text by entering it here.', 'tumblr-crosspostr');?>"
                    placeholder="<?php print sprintf(esc_attr__('New post: %s :)', 'tumblr-crosspostr'), '[URL]');?>"
                    maxlength="140" />
                <span class="description"><?php print sprintf(esc_html__('Use %s where you want the link to your Tumblr post to appear, or leave blank to use the default Tumblr auto-tweet.', 'tumblr-crosspostr'), '<code>[URL]</code>');?></span>
            </label>
        </p>
    </details>
</fieldset>
<?php
        }
    }

    /**
     * Writes the HTML for the options page, and each setting, as needed.
     */
    // TODO: Add contextual help menu to this page.
    public function renderOptionsPage () {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'tumblr-crosspostr'));
        }
        $options = get_option($this->prefix . '_settings');
        if (empty($options['post_types'])) { $options['post_types'] = array(); }
        $options['post_types'][] = 'post';
        if (isset($_GET['disconnect']) && wp_verify_nonce($_GET[$this->prefix . '_nonce'], 'disconnect_from_tumblr')) {
            $this->disconnectFromService();
?>
<div class="updated">
    <p>
        <?php esc_html_e('Disconnected from Tumblr.', 'tumblr-crosspostr');?>
        <span class="description"><?php esc_html_e('The connection to Tumblr was disestablished. You can reconnect using the same credentials, or enter different credentials before reconnecting.', 'tumblr-crosspostr');?></span>
    </p>
</div>
<?php
        }
?>
<h2><?php esc_html_e('Tumblr Crosspostr Settings', 'tumblr-crosspostr');?></h2>
<p class="fieldset-toc"><?php esc_html_e('Jump to options:', 'tumblr-crosspostr');?></p>
<ul class="fieldset-toc">
    <li><a href="#connection-to-tumblr"><?php esc_html_e('Connection to Tumblr', 'tumblr-crosspostr');?></a></li>
    <li><a href="#crossposting-options"><?php esc_html_e('Crossposting options', 'tumblr-crosspostr');?></a></li>
    <li><a href="#sync-options"><?php esc_html_e('Sync options', 'tumblr-crosspostr');?></a></li>
    <li><a href="#plugin-extras"><?php esc_html_e('Plugin extras', 'tumblr-crosspostr');?></a></li>
</ul>
<form method="post" action="options.php">
<?php settings_fields($this->prefix . '_settings');?>
<fieldset id="connection-to-tumblr"><legend><?php esc_html_e('Connection to Tumblr', 'tumblr-crosspostr');?></legend>
<table class="form-table" summary="<?php esc_attr_e('Required settings to connect to Tumblr.', 'tumblr-crosspostr');?>">
    <tbody>
        <tr<?php if (get_option($this->prefix . '_access_token')) : print ' style="display: none;"'; endif;?>>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_consumer_key"><?php esc_html_e('Tumblr API key/OAuth consumer key', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <input id="<?php esc_attr_e($this->prefix);?>_consumer_key" name="<?php esc_attr_e($this->prefix);?>_settings[consumer_key]" value="<?php esc_attr_e($options['consumer_key']);?>" placeholder="<?php esc_attr_e('Paste your API key here', 'tumblr-crosspostr');?>" />
                <p class="description">
                    <?php esc_html_e('Your Tumblr API key is also called your consumer key.', 'tumblr-crosspostr');?>
                    <?php print sprintf(
                        esc_html__('If you need an API key, you can %s.', 'tumblr-crosspostr'),
                        '<a href="' . esc_attr($this->getTumblrAppRegistrationUrl()) . '" target="_blank" ' .
                        'title="' . __('Get an API key from Tumblr by registering your WordPress blog as a new Tumblr app.', 'tumblr-crosspostr') . '">' .
                        __('create one here', 'tumblr-crosspostr') . '</a>'
                    );?>
                </p>
            </td>
        </tr>
        <tr<?php if (get_option($this->prefix . '_access_token')) : print ' style="display: none;"'; endif;?>>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_consumer_secret"><?php esc_html_e('OAuth consumer secret', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <input id="<?php esc_attr_e($this->prefix);?>_consumer_secret" name="<?php esc_attr_e($this->prefix);?>_settings[consumer_secret]" value="<?php esc_attr_e($options['consumer_secret']);?>" placeholder="<?php esc_attr_e('Paste your consumer secret here', 'tumblr-crosspostr');?>" />
                <p class="description">
                    <?php esc_html_e('Your consumer secret is like your app password. Never share this with anyone.', 'tumblr-crosspostr');?>
                </p>
            </td>
        </tr>
        <?php if (!get_option($this->prefix . '_access_token') && isset($options['consumer_key']) && isset($options['consumer_secret'])) { ?>
        <tr>
            <th class="wp-ui-notification" style="border-radius: 5px; padding: 10px;">
                <label for="<?php esc_attr_e($this->prefix);?>_oauth_authorize"><?php esc_html_e('Connect to Tumblr:', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <a href="<?php print wp_nonce_url(admin_url('options-general.php?page=' . $this->prefix . '_settings&' . $this->prefix . '_oauth_authorize'), 'tumblr-authorize');?>" class="button button-primary"><?php esc_html_e('Click here to connect to Tumblr', 'tumblr-crosspostr');?></a>
            </td>
        </tr>
        <?php } else if (get_option($this->prefix . '_access_token')) { ?>
        <tr>
            <th colspan="2">
                <div class="updated">
                    <p>
                        <?php esc_html_e('Connected to Tumblr!', 'tumblr-crosspostr');?>
                        <a href="<?php print wp_nonce_url(admin_url('options-general.php?page=' . $this->prefix . '_settings&disconnect'), 'disconnect_from_tumblr', $this->prefix . '_nonce');?>" class="button"><?php esc_html_e('Disconnect', 'tumblr-crosspostr');?></a>
                        <span class="description"><?php esc_html_e('Disconnecting will stop cross-posts from appearing on or being imported from your Tumblr blog(s), and will reset the options below to their defaults. You can re-connect at any time.', 'tumblr-crosspostr');?></span>
                    </p>
                </div>
            </th>
        </tr>
        <?php } ?>
    </tbody>
</table>
</fieldset>
        <?php if (get_option($this->prefix . '_access_token')) { ?>
<fieldset id="crossposting-options"><legend><?php esc_html_e('Crossposting options', 'tumblr-crosspostr');?></legend>
<table class="form-table" summary="<?php esc_attr_e('Options for customizing crossposting behavior.', 'tumblr-crosspostr');?>">
    <tbody>
        <tr<?php if (!isset($options['default_hostname'])) : print ' class="wp-ui-highlight"'; endif;?>>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_default_hostname"><?php esc_html_e('Default Tumblr blog for crossposts', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <?php print $this->tumblrBlogsSelectField(array('id' => $this->prefix . '_default_hostname', 'name' => $this->prefix . '_settings[default_hostname]'), $this->getTumblrBasename(0));?>
                <p class="description"><?php esc_html_e('Choose which Tumblr blog you want to send your posts to by default. This can be overriden on a per-post basis, too.', 'tumblr-crosspostr');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_exclude_categories"><?php esc_html_e('Do not crosspost entries in these categories:', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <ul id="<?php esc_attr_e($this->prefix);?>_exclude_categories">
                <?php foreach (get_categories(array('hide_empty' => 0)) as $cat) : ?>
                    <li>
                        <label>
                            <input
                                type="checkbox"
                                <?php if (isset($options['exclude_categories']) && in_array($cat->slug, $options['exclude_categories'])) : print 'checked="checked"'; endif;?>
                                value="<?php esc_attr_e($cat->slug);?>"
                                name="<?php esc_attr_e($this->prefix);?>_settings[exclude_categories][]">
                            <?php print esc_html($cat->name);?>
                        </label>
                    </li>
                <?php endforeach;?>
                </ul>
                <p class="description"><?php esc_html_e('Will cause posts in the specificied categories never to be crossposted to Tumblr. This is useful if, for instance, you are creating posts automatically using another plugin and wish to avoid a feedback loop of crossposting back and forth from one service to another.', 'tumblr-crosspostr');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_auto_source_yes"><?php esc_html_e('Use permalinks from this blog as the "Content source" for crossposts on Tumblr?');?></label>
            </th>
            <td>
                <ul style="float: left;">
                    <li>
                        <label>
                            <input type="radio" id="<?php esc_attr_e($this->prefix);?>_auto_source_yes"
                                name="<?php esc_attr_e($this->prefix);?>_settings[auto_source]"
                                <?php if (!isset($options['auto_source']) || $options['auto_source'] === 'Y') { print 'checked="checked"'; } ?>
                                value="Y" />
                            <?php esc_html_e('Yes', 'tumblr-crosspostr');?>
                        </label>
                    </li>
                    <li>
                        <label>
                            <input type="radio" id="<?php esc_attr_e($this->prefix);?>_auto_source_no"
                                name="<?php esc_attr_e($this->prefix);?>_settings[auto_source]"
                                <?php if (isset($options['auto_source']) && $options['auto_source'] === 'N') { print 'checked="checked"'; } ?>
                                value="N" />
                            <?php esc_html_e('No', 'tumblr-crosspostr');?>
                        </label>
                    </li>
                </ul>
                <p class="description" style="padding: 0 5em;"><?php print sprintf(esc_html__('When enabled, leaving the %sContent source%s field blank on a given entry will result in setting %sthe "Content source" field on your Tumblr post%s to the permalink of your WordPress post. Useful for providing automatic back-links to your main blog, but turn this off if you "secretly" use Tumblr Crosspostr as the back-end of a publishing platform.', 'tumblr-crosspostr'), '<code>', '</code>', '<a href="http://staff.tumblr.com/post/1059624418/content-attribution">', '</a>');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_use_excerpt"><?php esc_html_e('Send excerpts instead of main content?', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['use_excerpt'])) : print 'checked="checked"'; endif; ?> value="1" id="<?php esc_attr_e($this->prefix);?>_use_excerpt" name="<?php esc_attr_e($this->prefix);?>_settings[use_excerpt]" />
                <label for="<?php esc_attr_e($this->prefix);?>_use_excerpt"><span class="description"><?php esc_html_e('When enabled, the excerpts (as opposed to the body) of your WordPress posts will be used as the main content of your Tumblr posts. Useful if you prefer to crosspost summaries instead of the full text of your entires to Tumblr by default. This can be overriden on a per-post basis, too.', 'tumblr-crosspostr');?></span></label>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_post_types"><?php esc_html_e('Crosspost the following post types:', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <ul id="<?php esc_attr_e($this->prefix);?>_post_types">
                <?php foreach (get_post_types(array('public' => true)) as $cpt) : ?>
                    <li>
                        <label>
                            <input
                                type="checkbox"
                                <?php if (isset($options['post_types']) && in_array($cpt, $options['post_types'])) : print 'checked="checked"'; endif;?>
                                <?php if ('post' === $cpt) { print 'disabled="disabled"'; } ?>
                                value="<?php esc_attr_e($cpt);?>"
                                name="<?php esc_attr_e($this->prefix);?>_settings[post_types][]">
                            <?php print esc_html($cpt);?>
                        </label>
                    </li>
                <?php endforeach;?>
                </ul>
                <p class="description"><?php print sprintf(esc_html__('Choose which %1$spost types%2$s you want to crosspost. Not all post types can be crossposted safely, but many can. If you are not sure about a post type, leave it disabled. Plugin authors may create post types that are crossposted regardless of the value of this setting. %3$spost%4$s post types are always enabled.', 'tumblr-crosspostr'), '<a href="https://codex.wordpress.org/Post_Types">', '</a>', '<code>', '</code>');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_additional_markup"><?php esc_html_e('Add the following markup to each crossposted entry:', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <textarea
                    id="<?php esc_attr_e($this->prefix);?>_additional_markup"
                    name="<?php esc_attr_e($this->prefix);?>_settings[additional_markup]"
                    placeholder="<?php esc_attr_e('Anything you type in this box will be added to every crosspost.', 'tumblr-crosspostr');?>"><?php
        if (isset($options['additional_markup'])) {
            print esc_textarea($options['additional_markup']);
        } else {
            print '<p class="tumblr-crosspostr-linkback"><a href="%permalink%" title="' . esc_html__('Go to the original post.', 'tumblr-crosspostr') . '" rel="bookmark">%the_title%</a> ' . esc_html__('was originally published on', $this->prefix . '') . ' <a href="%blog_url%">%blog_name%</a></p>';
        }
?></textarea>
                <p class="description"><?php _e('Text or HTML you want to add to each post. Useful for things like a link back to your original post. You can use <code>%permalink%</code>, <code>%the_title%</code>, <code>%blog_url%</code>, and <code>%blog_name%</code> as placeholders for the cross-posted post\'s link, its title, the link to the homepage for this site, and the name of this blog, respectively. Leave this blank or use this field for a different purpose if you prefer to use only the Tumblr "Content source" meta field for links back to your main blog.', 'tumblr-crosspostr');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_exclude_tags"><?php esc_html_e('Do not send post tags to Tumblr', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['exclude_tags'])) : print 'checked="checked"'; endif; ?> value="1" id="<?php esc_attr_e($this->prefix);?>_exclude_tags" name="<?php esc_attr_e($this->prefix);?>_settings[exclude_tags]" />
                <label for="<?php esc_attr_e($this->prefix);?>_exclude_tags"><span class="description"><?php esc_html_e('When enabled, tags on your WordPress posts are not applied to your Tumblr posts. Useful if you maintain different taxonomies on your different sites.', 'tumblr-crosspostr');?></span></label>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_additional_tags">
                    <?php esc_html_e('Automatically add these tags to all crossposts:', 'tumblr-crosspostr');?>
                </label>
            </th>
            <td>
                <input id="<?php esc_attr_e($this->prefix);?>_additional_tags" value="<?php if (isset($options['additional_tags'])) : esc_attr_e(implode(', ', $options['additional_tags'])); endif;?>" name="<?php esc_attr_e($this->prefix);?>_settings[additional_tags]" placeholder="<?php esc_attr_e('crosspost, magic', 'tumblr-crosspostr');?>" />
                <p class="description"><?php print sprintf(esc_html__('Comma-separated list of additional tags that will be added to every post sent to Tumblr. Useful if only some posts on your Tumblr blog are cross-posted and you want to know which of your Tumblr posts were generated by this plugin. (These tags will always be applied regardless of the value of the "%s" option.)', 'tumblr-crosspostr'), esc_html__('Do not send post tags to Tumblr', 'tumblr-crosspostr'));?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_auto_tweet">
                    <?php esc_html_e('Automatically tweet a link to your Tumblr post?', 'tumblr-crosspostr');?>
                </label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['auto_tweet'])) : print 'checked="checked"'; endif; ?> value="1" id="<?php esc_attr_e($this->prefix);?>_auto_tweet" name="<?php esc_attr_e($this->prefix);?>_settings[auto_tweet]" />
                <label for="<?php esc_attr_e($this->prefix);?>_auto_tweet"><span class="description"><?php print sprintf(esc_html__('When checked, new posts you create on WordPress will have their "%s" option enabled by default. You can always override this when editing an individual post.', 'tumblr-crosspostr'), esc_html__('Send tweet?', 'tumblr-crosspostr'));?></span></label>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<fieldset id="sync-options"><legend><?php esc_html_e('Sync options', 'tumblr-crosspostr');?></legend>
<table class="form-table" summary="<?php esc_attr_e('Customize the import behavior.', 'tumblr-crosspostr');?>">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_sync_from_tumblr"><?php esc_html_e('Sync posts from Tumblr', 'tumblr-crosspostr');?></label>
                <p class="description"><?php esc_html_e('(This feature is experimental. Please backup your website before you turn this on.)', 'tumblr-crosspostr');?></p>
            </th>
            <td>
                <ul id="<?php esc_attr_e($this->prefix);?>_sync_content">
                    <?php print $this->tumblrBlogsListCheckboxes(array('id' => $this->prefix . '_sync_content', 'name' => $this->prefix . '_settings[sync_content][]'), $options['sync_content']);?>
                </ul>
                <p class="description"><?php esc_html_e('Content you create on the Tumblr blogs you select will automatically be copied to this blog.', 'tumblr-crosspostr');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_import_to_categories"><?php esc_html_e('Automatically assign synced posts to these categories:');?></label>
            </th>
            <td>
                <ul id="<?php esc_attr_e($this->prefix);?>_import_to_categories">
                <?php foreach (get_categories(array('hide_empty' => 0)) as $cat) : ?>
                    <li>
                        <label>
                            <input
                                type="checkbox"
                                <?php if (isset($options['import_to_categories']) && in_array($cat->slug, $options['import_to_categories'])) : print 'checked="checked"'; endif;?>
                                value="<?php esc_attr_e($cat->slug);?>"
                                name="<?php esc_attr_e($this->prefix);?>_settings[import_to_categories][]">
                            <?php print esc_html($cat->name);?>
                        </label>
                    </li>
                <?php endforeach;?>
                </ul>
                <p class="description"><?php print sprintf(esc_html__('Will cause any posts imported from your Tumblr blog to be assigned the categories that you enable here. It is often a good idea to %screate a new category%s that you use exclusively for this purpose.', 'tumblr-crosspostr'), '<a href="' . admin_url('edit-tags.php?taxonomy=category') . '">', '</a>');?></p>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<fieldset id="plugin-extras"><legend><?php esc_html_e('Plugin extras', 'tumblr-crosspostr');?></legend>
<table class="form-table" summary="<?php esc_attr_e('Additional options to customize plugin behavior.', 'tumblr-crosspostr');?>">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>_debug">
                    <?php esc_html_e('Enable detailed debugging information?', 'tumblr-crosspostr');?>
                </label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['debug'])) : print 'checked="checked"'; endif; ?> value="1" id="<?php esc_attr_e($this->prefix);?>_debug" name="<?php esc_attr_e($this->prefix);?>_settings[debug]" />
                <label for="<?php esc_attr_e($this->prefix);?>_debug"><span class="description"><?php
        print sprintf(
            esc_html__('Turn this on only if you are experiencing problems using this plugin, or if you were told to do so by someone helping you fix a problem (or if you really know what you are doing). When enabled, extremely detailed technical information is displayed as a WordPress admin notice when you take actions like sending a crosspost. If you have also enabled WordPress\'s built-in debugging (%1$s) and debug log (%2$s) feature, additional information will be sent to a log file (%3$s). This file may contain sensitive information, so turn this off and erase the debug log file when you have resolved the issue.', 'tumblr-crosspostr'),
            '<a href="https://codex.wordpress.org/Debugging_in_WordPress#WP_DEBUG"><code>WP_DEBUG</code></a>',
            '<a href="https://codex.wordpress.org/Debugging_in_WordPress#WP_DEBUG_LOG"><code>WP_DEBUG_LOG</code></a>',
            '<code>' . content_url() . '/debug.log' . '</code>'
        );
                ?></span></label>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
        <?php } ?>
<?php submit_button();?>
</form>
<?php
        $this->showDonationAppeal();
    } // end public function renderOptionsPage

    public function dispatchTumblrizeArchivesPages () {
        if (!isset($_GET[$this->prefix . '_nonce']) || !wp_verify_nonce($_GET[$this->prefix . '_nonce'], 'tumblrize_everything')) {
            $this->renderManagementPage();
        } else {
            if (!$this->isConnectedToService()) {
                wp_redirect(admin_url('options-general.php?page=' . $this->prefix . '_settings'));
                exit();
            }
            $posts = get_posts(array(
                'nopaging' => true,
                'order' => 'ASC',
            ));
            $tumblrized = array();
            foreach ($posts as $post) {
                if ($prepared_post = $this->prepareForTumblr($post->ID)) {
                    $data = $this->crosspostToTumblr($prepared_post->base_hostname, $prepared_post->params, $prepared_post->tumblr_id);
                    update_post_meta($post->ID, 'tumblr_post_id', $data->response->id);
                    $tumblrized[] = array('id' => $data->response->id, 'base_hostname' => $prepared_post->base_hostname);
                }
            }
            $blogs = array();
            foreach ($tumblrized as $p) {
                $blogs[] = $p['base_hostname'];
            }
            $blogs_touched = count(array_unique($blogs));
            $posts_touched = count($tumblrized);
            print '<p>' . sprintf(
                _n(
                    'Success! %1$d post has been crossposted.',
                    'Success! %1$d posts have been crossposted to %2$d blogs.',
                    $posts_touched,
                    'tumblr-crosspostr'
                ),
                $posts_touched,
                $blogs_touched
            ) . '</p>';
            print '<p>' . esc_html_e('Blogs touched:', 'tumblr-crosspostr') . '</p>';
            print '<ul>';
            foreach (array_unique($blogs) as $blog) {
                print '<li><a href="' . esc_url("http://$blog/") . '">' . esc_html($blog) . '</a></li>';
            }
            print '</ul>';
            $this->showDonationAppeal();
        }
    }

    private function renderManagementPage () {
        $options = get_option($this->prefix . '_settings');
?>
<h2><?php esc_html_e('Crosspost Archives to Tumblr', 'tumblr-crosspostr');?></h2>
<p><?php esc_html_e('If you have post archives on this website, Tumblr Crosspostr can copy them to your Tumblr blog.', 'tumblr-crosspostr');?></p>
<p><a href="<?php print wp_nonce_url(admin_url('tools.php?page=' . $this->prefix . '_crosspost_archives'), 'tumblrize_everything', $this->prefix . '_nonce');?>" class="button button-primary">Tumblrize Everything!</a></p>
<p class="description"><?php print sprintf(esc_html__('Copies all posts from your archives to your default Tumblr blog (%s). This may take some time if you have a lot of content. If you do not want to crosspost a specific post, set the answer to the "Send this post to Tumblr?" question to "No" when editing those posts before taking this action. If you have previously crossposted some posts, this will update that content on your Tumblr blog(s).', 'tumblr-crosspostr'), '<code>' . esc_html($options['default_hostname']) . '</code>');?></p>
<?php
        $this->showDonationAppeal();
    } // end renderManagementPage ()

    private function getBlogsToSync () {
        $options = get_option($this->prefix . '_settings');
        return (empty($options['sync_content'])) ? array() : $options['sync_content'];
    }

    public function setSyncSchedules () {
        if (!$this->isConnectedToService()) { return; }
        $blogs_to_sync = $this->getBlogsToSync();
        // If we are being asked to sync, set up a daily schedule for that.
        if (!empty($blogs_to_sync)) {
            foreach ($blogs_to_sync as $x) {
                if (!wp_get_schedule($this->prefix . '_sync_content', array($x))) {
                    wp_schedule_event(time(), 'daily', $this->prefix . '_sync_content', array($x));
                }
            }
        }
        // For any blogs we know of but aren't being asked to sync,
        $known_blogs = array();
        $users_blogs = $this->tumblr->getUserBlogs();
        if ($users_blogs) {
            foreach ($users_blogs as $blog) {
                $known_blogs[] = parse_url($blog->url, PHP_URL_HOST);
            }
        }
        $to_unschedule = array_diff($known_blogs, $blogs_to_sync);
        foreach ($to_unschedule as $x) {
            // check to see if there's a scheduled event to sync it, and,
            // if so, unschedule it.
            wp_unschedule_event(
                wp_next_scheduled($this->prefix . '_sync_content', array($x)),
                $this->prefix . '_sync_content',
                array($x)
            );
        }
    }

    public function deactivate () {
        $blogs_to_sync = $this->getBlogsToSync();
        if (!empty($blogs_to_sync)) {
            foreach ($blogs_to_sync as $blog) {
                wp_clear_scheduled_hook($this->prefix . '_sync_content', array($blog));
            }
        }
    }

    /**
     * There's a frustrating bug in PHP? In WordPress? That causes get_posts()
     * to always return an empty array when run in Cron if the PHP version is
     * less than 5.4-ish. Check for that and workaround if necessary.
     *
     * @see https://wordpress.org/support/topic/get_posts-returns-no-results-when-run-via-cron
     */
    private function crosspostExists ($tumblr_id) {
        // If we're running on PHP >= 5.4, use WordPress's built-in function.
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            return get_posts(array(
                'meta_key' => 'tumblr_post_id',
                'meta_value' => $tumblr_id,
                'fields' => 'ids'
            ));
        }
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare(
            "
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key='%s' AND meta_value='%s'
            ",
            'tumblr_post_id',
            $tumblr_id
        ));
    }

    public function syncFromTumblrBlog ($base_hostname) {
        $options = get_option($this->prefix . '_settings');
        if (!empty($options['debug'])) {
            error_log(sprintf(esc_html__('Entering Tumblr Sync routine for %s', 'tumblr-crosspostr'), $base_hostname));
        }
        if (!isset($options['last_synced_ids'])) {
            $options['last_synced_ids'] = array();
        }
        $latest_synced_id = (isset($options['last_synced_ids'][$base_hostname]))
            ? $options['last_synced_ids'][$base_hostname]
            : 0;

        $this->tumblr->setApiKey($options['consumer_key']);

        $ids_synced = array(0); // Init with 0
        $offset = 0;
        $limit = 50;
        // This loop either:
        // * Trawls through the entire blog (if $latest_synced_id is 0), or
        // * only tries to sync the latest two batches of posts
        do {
            $resp = $this->tumblr->getPosts($base_hostname, array('offset' => $offset, 'limit' => $limit));
            $posts = $resp->posts;
            foreach ($posts as $post) {
                $preexisting_posts = $this->crosspostExists($post->id);
                if (!empty($options['debug'])) {
                    error_log(sprintf(
                        _n('Found %s preexisting post for Tumblr ID', 'Found %s preexisting posts for Tumblr ID', count($preexisting_posts), 'tumblr-crosspostr') . ' %s (%s)',
                        count($preexisting_posts),
                        $post->id,
                        implode(',', $preexisting_posts)
                    ));
                }
                if (in_array($post->id, $ids_synced)) {
                    error_log("Haven't we already sync'ed this Tumblr post? {$post->id}");
                } else if (empty($preexisting_posts)) {
                    if ($this->importPostFromTumblr($post)) {
                        $ids_synced[] = $post->id;
                    }
                }
            }
            $offset = ($limit + $offset);
            if (0 !== $latest_synced_id && $offset >= $limit * 2) {
                if (!empty($options['debug'])) {
                    error_log(esc_html__('Previously synced, stopping.', 'tumblr-crosspostr'));
                }
                break;
            }
        } while (!empty($posts));

        // Record the latest Tumblr post ID to be sync'ed on the blog.
        // (Usefully, Tumblr post ID's are sequential.)
        $options['last_synced_ids'][$base_hostname] = ($latest_synced_id > max($ids_synced))
            ? $latest_synced_id
            : max($ids_synced);
        update_option($this->prefix . '_settings', $options);
    }

    private function translateTumblrPostContent ($post) {
        $content = '';
        switch ($post->type) {
            case 'photo':
                foreach ($post->photos as $photo) {
                    $content .= '<img src="' . $photo->original_size->url . '" alt="' . $photo->caption. '" />';
                }
                $content .= $post->caption;
                break;
            case 'quote':
                $content .= '<blockquote>' . $post->text . '</blockquote>';
                $content .= $post->source;
                break;
            case 'link':
                $content .= '<a href="' . $post->url . '">' . $post->title . '</a>';
                $content .= $post->description;
                break;
            case 'audio':
                $content .= $post->player;
                $content .= $post->caption;
                break;
            case 'video':
                $content .= $post->player[0]->embed_code;
                $content .= $post->caption;
                break;
            case 'answer':
                $content .= '<a href="' . $post->asking_url .'" class="tumblr_blog">' . $post->asking_name . '</a>:';
                $content .= '<blockquote cite="' . $post->post_url . '" class="tumblr_ask">' . $post->question . '</blockquote>';
                $content .= $post->answer;
                break;
            case 'chat':
            case 'text':
            default:
                $content .= $post->body;
                break;
        }
        return $content;
    }

    private function importPostFromTumblr ($post) {
        $options = get_option($this->prefix . '_settings');
        if (!empty($options['debug'])) {
            error_log(sprintf('Entering importPostFromTumblr, tumblr_post_id=%s', $post->id));
        }
        $wp_post = array();
        $wp_post['post_content'] = $this->translateTumblrPostContent($post);
        $wp_post['post_title'] = (isset($post->title)) ? $post->title : '';
        $wp_post['post_status'] = $this->TumblrState2WordPressStatus($post->state);
        // TODO: Figure out how to handle multi-author blogs.
        //$wp_post['post_author'] = $post->author;
        $wp_post['post_date'] = date('Y-m-d H:i:s', $post->timestamp);
        $wp_post['post_date_gmt'] = gmdate('Y-m-d H:i:s', $post->timestamp);
        $wp_post['tags_input'] = $post->tags;

        if (!empty($options['import_to_categories'])) {
            $cat_ids = array();
            foreach ($options['import_to_categories'] as $slug) {
                $x = get_category_by_slug($slug);
                $cat_ids[] = $x->term_id;
            }
            $wp_post['post_category'] = $cat_ids;
        }

        // Remove filtering so we retain audio, video, embeds, etc.
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_action('save_post', array($this, 'savePost')); // avoid loops during import
        $wp_id = wp_insert_post($wp_post);
        add_filter('content_save_pre', 'wp_filter_post_kses');
        if ($wp_id) {
            set_post_format($wp_id, $this->TumblrPostType2WordPressPostFormat($post->type));
            update_post_meta($wp_id, 'tumblr_base_hostname', parse_url($post->post_url, PHP_URL_HOST));
            update_post_meta($wp_id, 'tumblr_post_id', $post->id);
            update_post_meta($wp_id, 'tumblr_reblog_key', $post->reblog_key);
            if (isset($post->source_url)) {
                update_post_meta($wp_id, 'tumblr_source_url', $post->source_url);
            }

            // Import media from post types as WordPress attachments.
            if (!empty($post->type)) {
                $wp_subdir_from_post_timestamp = date('Y/m', $post->timestamp);
                $wp_upload_dir = wp_upload_dir($wp_subdir_from_post_timestamp);
                if (!is_writable($wp_upload_dir['path'])) {
                    $msg = sprintf(
                        esc_html__('Your WordPress uploads directory (%s) is not writeable, so Tumblr Crosspostr could not import some media files directly into your Media Library. Media (such as images) will be referenced from their remote source rather than imported and referenced locally.', 'tumblr-crosspostr'),
                        $wp_upload_dir['path']
                    );
                    error_log($msg);
                } else {
                    switch ($post->type) {
                        case 'photo':
                            $this->importPhotosInPost($post, $wp_id);
                            break;
                        case 'audio':
                            $this->importAudioInPost($post, $wp_id);
                            break;
                        default:
                            // TODO: Import media from other post types?
                            break;
                    }
                }
            }
        }
        return $wp_id;
    }

    /**
     * Imports some URL-accessible asset (image, audio file, etc.) as a
     * WordPress attachment and associates it with a given WordPress post.
     *
     * @param string $media_url The URL to the asset.
     * @param object $post Tumblr post object.
     * @param int $wp_id WordPress post ID number.
     * @param string $replace_this Content referencing remote asset to replace with locally imported asset.
     * @param string $replace_with_this_before Content to prepend to locally imported asset URL, such as the start of a shortcode.
     * @param string $replace_with_this_after Content to append to locally imported asset URL, such as the end of a shortcode.
     */
    private function importMedia ($media_url, $post, $wp_id, $replace_this, $replace_with_this_before = '', $replace_with_this_after = '') {
        $data = wp_remote_get($media_url, array('timeout' => 300)); // Download for 5 minutes, tops.
        if (is_wp_error($data)) {
            $msg = sprintf(
                esc_html__('Failed to get Tumblr media (%1$s) from post (%2$s). Server responded: %3$s', 'tumblr-crosspostr'),
                $media_url,
                $post->post_url,
                print_r($data, true)
            );
            error_log($msg);
        } else {
            $f = wp_upload_bits(basename($media_url), null, $data['body'], date('Y/m', $post->timestamp));
            if ($f['error']) {
                $msg = sprintf(
                    esc_html__('Error saving file (%s): ', 'tumblr-crosspostr'),
                    basename($media_url)
                );
                error_log($msg);
            } else {
                $wp_upload_dir = wp_upload_dir(date('Y/m', $post->timestamp));
                $wp_filetype = wp_check_filetype(basename($f['file']));
                $wp_file_id = wp_insert_attachment(array(
                    'post_title' => basename($f['file'], ".{$wp_filetype['ext']}"),
                    'post_content' => '', // Always empty string.
                    'post_status' => 'inherit',
                    'post_mime_type' => $wp_filetype['type'],
                    'guid' => $wp_upload_dir['url'] . '/' . basename($f['file'])
                ), $f['file'], $wp_id);
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $metadata = wp_generate_attachment_metadata($wp_file_id, $f['file']);
                wp_update_attachment_metadata($wp_file_id, $metadata);
                if (1 === count($post->photos)) {
                    set_post_thumbnail($wp_id, $wp_file_id);
                    $photo = array_shift($post->photos);
                    $replace_this = '<img src="' . $photo->original_size->url . '" alt="' . $photo->caption. '" />';
                    $new_content = str_replace($replace_this, '', get_post_field('post_content', $wp_id));
                } else {
                    $new_content = str_replace($replace_this, $replace_with_this_before . $f['url'] . $replace_with_this_after, get_post_field('post_content', $wp_id));
                }
                wp_update_post(array(
                    'ID' => $wp_id,
                    'post_content' => $new_content
                ));
            }
        }
    }

    private function importPhotosInPost ($post, $wp_id) {
        foreach ($post->photos as $photo) {
            $this->importMedia($photo->original_size->url, $post, $wp_id, $photo->original_size->url);
        }
    }

    private function importAudioInPost ($post, $wp_id) {
        $player_atts = wp_kses_hair($post->player, array('http', 'https'));
        $x = parse_url($player_atts['src']['value'], PHP_URL_QUERY);
        $vars = array();
        parse_str($x, $vars);
        $audio_file_url = urldecode($vars['audio_file']);

        $this->importMedia($audio_file_url, $post, $wp_id, $post->player, '[audio src="', '"]');
    }

    private function getTumblrAppRegistrationUrl () {
        $params = array(
            'title' => get_bloginfo('name'),
            // Max 400 chars for Tumblr
            'description' => mb_substr(get_bloginfo('description'), 0, 400, get_bloginfo('charset')),
            'url' => home_url(),
            'admin_contact_email' => get_bloginfo('admin_email'),
            'default_callback_url' => plugins_url('/oauth-callback.php', __FILE__)
        );
        return $this->tumblr->getAppRegistrationUrl($params);
    }

    private function tumblrBlogsSelectField ($attributes = array(), $selected = false) {
        $html = '<select';
        if (!empty($attributes)) {
            foreach ($attributes as $k => $v) {
                $html .=  ' ' . $k . '="' . esc_attr($v) . '"';
            }
        }
        $html .= '>';
        foreach ($this->tumblr->getUserBlogs() as $blog) {
            $html .= '<option value="' . esc_attr(parse_url($blog->url, PHP_URL_HOST)) . '"';
            if ($selected && $selected === parse_url($blog->url, PHP_URL_HOST)) {
                $html .= ' selected="selected"';
            }
            $html .= '>';
            $html .= esc_html($blog->title);
            $html .= '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    private function tumblrBlogsListCheckboxes ($attributes = array(), $selected = false) {
        $html = '';
        foreach ($this->tumblr->getUserBlogs() as $blog) {
            $html .= '<li>';
            $html .= '<label>';
            $x = parse_url($blog->url, PHP_URL_HOST);
            $html .= '<input type="checkbox"';
            if (!empty($attributes)) {
                foreach ($attributes as $k => $v) {
                    $html .= ' ';
                    switch ($k) {
                        case 'id':
                            $html .= $k . '="' . esc_attr($v) . '-' . esc_attr($x) . '"';
                            break;
                        default:
                            $html .= $k . '="' . esc_attr($v) . '"';
                            break;
                    }
                }
            }
            if ($selected && in_array($x, $selected)) {
                $html .= ' checked="checked"';
            }
            $html .= ' value="' . esc_attr($x) . '"';
            $html .= '>';
            $html .= esc_html($blog->title) . '</label>';
            $html .= '</li>';
        }
        return $html;
    }

    // Modified from https://stackoverflow.com/a/4997018/2736587 which claims
    // http://www.php.net/manual/en/function.strip-tags.php#96483
    // as its source. Werksferme.
    private function strip_only($str, $tags, $stripContent = false, $limit = -1) {
        $content = '';
        if(!is_array($tags)) {
            $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
            if(end($tags) == '') array_pop($tags);
        }
        foreach($tags as $tag) {
            if ($stripContent) {
                $content = '(.+</'.$tag.'[^>]*>|)';
            }
            $str = preg_replace('#</?'.$tag.'[^>]*>'.$content.'#is', '', $str, $limit);
        }
        return $str;
    }

}

$tumblr_crosspostr = new Tumblr_Crosspostr();
require_once 'template-functions.php';
