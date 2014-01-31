<?php
/**
 * Plugin Name: Tumblr Crosspostr
 * Plugin URI: https://github.com/meitar/tumblrize/#readme
 * Description: Automatically crossposts to your Tumblr blog when you publish a post on your WordPress blog.
 * Version: 0.1
 * Author: Meitar Moscovitz
 * Author URI: http://Cyberbusking.org/
 * Text Domain: tumblr-crosspostr
 * Domain Path: /languages
 */

class Tumblr_Crosspostr {
    private $tumblr; //< Tumblr API manipulation wrapper.

    public function __construct () {
        add_action('plugins_loaded', array($this, 'registerL10n'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_menu', array($this, 'registerAdminMenu'));
        add_action('save_post', array($this, 'savePost'));
        add_action('before_delete_post', array($this, 'removeFromTumblr'));
        // run late, so themes have a chance to register support
        add_action('after_setup_theme', array($this, 'validateThemeSupport'), 700);

        // Initialize consumer if we can, set up authroization flow if we can't.
        require_once 'lib/TumblrCrosspostrAPIClient.php';
        $options = get_option('tumblr_crosspostr_settings');
        if (isset($options['consumer_key']) && isset($options['consumer_secret'])) {
            $this->tumblr = new Tumblr_Crosspostr_API_Client($options['consumer_key'], $options['consumer_secret']);
            if (isset($options['access_token']) && isset($options['access_token_secret'])) {
                $this->tumblr->setToken($options['access_token']);
                $this->tumblr->setTokenSecret($options['access_token_secret']);
            }
        } else {
            add_action('admin_notices', array($this, 'showMissingConfigNotice'));
        }

        // OAuth connection workflow.
        if (isset($_GET['tumblr_crosspostr_oauth_authorize'])) {
            add_action('init', array($this, 'authorizeTumblrApp'));
        } else if (isset($_GET['tumblr_crosspostr_callback']) && !empty($_GET['oauth_verifier'])) {
            // Unless we're just saving the options, hook the final step in OAuth authorization.
            if (!isset($_GET['settings-updated'])) {
                add_action('init', array($this, 'completeAuthorization'));
            }
        }
    }

    public function showMissingConfigNotice () {
        $screen = get_current_screen();
        if ($screen->base === 'plugins') {
?>
<div class="updated">
    <p><a href="<?php print admin_url('options-general.php?page=tumblr_crosspostr_settings');?>" class="button"><?php esc_html_e('Connect to Tumblr', 'tumblr-crosspostr');?></a> &mdash; <?php esc_html_e('Almost done! Connect your blog to Tumblr to begin crossposting with Tumblr Crosspostr.', 'tumblr-crosspostr');?></p>
</div>
<?php
        }
    }

    public function validateThemeSupport () {
        if (!current_theme_supports('post-formats')) {
            add_action('admin_notices', array($this, 'showLackOfSupportForFormatsNotice'));
        }

    }

    public function showLackOfSupportForFormatsNotice () {
?>
<div class="error">
    <p><?php _e('The current theme does not seem to support WordPress Post Formats. Post Formats is required to make use of Tumblr Crosspostr. Please activate a theme that supports Post Formats before you begin cross-posting!', 'tumblr-crosspostr');?></p>
</div>
<?php
    }

    public function authorizeTumblrApp () {
        check_admin_referer('tumblr-authorize');

        $this->tumblr->getRequestToken(admin_url('options-general.php?page=tumblr_crosspostr_settings&tumblr_crosspostr_callback'));
        wp_redirect($this->tumblr->getAuthorizeUrl());
        exit();
    }

    public function completeAuthorization () {
        $options = get_option('tumblr_crosspostr_settings');
        $this->tumblr = new Tumblr_Crosspostr_API_Client(
            $options['consumer_key'],
            $options['consumer_secret'],
            $_SESSION['tumblr_crosspostr_oauth_token'],
            $_SESSION['tumblr_crosspostr_oauth_token_secret']
        );
        $this->tumblr->getAccessToken($_GET['oauth_verifier']); // Puts new tokens in $_SESSION.
        $options['access_token'] = $_SESSION['tumblr_crosspostr_oauth_token'];
        $options['access_token_secret'] = $_SESSION['tumblr_crosspostr_oauth_token_secret'];
        update_option('tumblr_crosspostr_settings', $options);
    }

    public function registerL10n () {
        load_plugin_textdomain('tumblr-crosspostr', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function registerSettings () {
        register_setting(
            'tumblr_crosspostr_settings',
            'tumblr_crosspostr_settings',
            array($this, 'validateSettings')
        );
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

    public function savePost ($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        $options = get_option('tumblr_crosspostr_settings');

        if (isset($options['exclude_categories']) && in_category($options['exclude_categories'], $post_id)) {
            return;
        }

        $format = get_post_format($post_id);
        $status = get_post_status($post_id);
        if (!$state = $this->WordPressStatus2TumblrState($status)) {
            return; // do not crosspost unsupported post states
        }
        $tags = array();
        if ($t = get_the_tags($post_id)) {
            foreach ($t as $tag) {
                $tags[] = $tag->name;
            }
        }
        $common_params = array(
            'type' => $this->WordPressPostFormat2TumblrPostType($format),
            'state' => $state,
            'tags' => implode(',', $tags),
            'date' => get_post_time('U', true, $post_id),
            'format' => 'html' // Tumblr's "formats" are always either 'html' or 'markdown'
        );

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

        $all_params = array_merge($common_params, $post_params);

        // If there's already a Tumblr post ID for this post, edit that post on Tumblr.
        $editing = get_post_meta($post_id, 'tumblr_post_id', true);
        $tumblr_id = (!empty($editing)) ?
            // TODO: Variablize the default hostname on a per-post basis.
            $this->crosspostToTumblr($options['default_hostname'], $all_params, $editing) :
            $this->crosspostToTumblr($options['default_hostname'], $all_params);
        update_post_meta($post_id, 'tumblr_post_id', $tumblr_id);
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
                if (function_exists($func)) {
                    $str = str_replace($x, call_user_func($func, $post_id), $str);
                }
            }
        }
        return $str;
    }

    private function crosspostToTumblr ($blog, $params, $tumblr_id = false, $deleting = false) {
        // TODO: Smoothen this deleting thing.
        //       Cancel WordPress deletions if Tumblr deletions aren't working?
        if ($deleting === true && $tumblr_id) {
            $params['id'] = $tumblr_id;
            return $this->tumblr->deleteFromTumblrBlog($blog, $params);
        } else if ($tumblr_id) {
            $params['id'] = (int) $tumblr_id;
            $id = $this->tumblr->editOnTumblrBlog($blog, $params);
        } else {
            $id = $this->tumblr->postToTumblrBlog($blog, $params);
        }
        return $id;
    }

    private function prepareParamsByPostType ($post_id, $type) {
        $post_body = get_post_field('post_content', $post_id);
        $r = array();
        switch ($type) {
            case 'photo':
                $r['caption'] = apply_filters('the_content', $this->strip_only($post_body, 'img', true, 1));
                $r['link'] = $this->extractByRegex('/<img.*?src="(.*?)".*?\/?>/', $post_body, 1);
                $r['source'] = $this->extractByRegex('/<img.*?src="(.*?)".*?\/?>/', $post_body, 1);
                break;
            case 'quote':
                // TODO: Buggy. Doesn't always pick up the contents of cite="" attribute.
                $r['quote'] = $this->extractByRegex(
                    '/<blockquote.*?(?:cite="(.*?)")?.*?>(.*?)<\/blockquote>/',
                    $post_body,
                    2
                );
                $r['source'] = $this->extractByRegex(
                    '/<blockquote.*?(?:cite="(.*?)")?.*?>(.*?)<\/blockquote>/',
                    $post_body,
                    1
                );
                break;
            case 'link':
                $r['title'] = get_post_field('post_title', $post_id);
                $r['url'] = $this->extractByRegex('/<a.*?href="(.*?)".*?>/', $post_body, 1);
                $r['description'] = apply_filters('the_content', $post_body);
                break;
            case 'chat':
                // TODO: Not yet implemented.
                break;
            case 'audio':
                $r['caption'] = apply_filters('the_content', $post_body);
                $r['external_url'] = $this->extractByRegex('/<a.*?href="(.*?\.[Mm][Pp]3)".*?>/', $post_body, 1);
                break;
            case 'video':
                $r['caption'] = apply_filters('the_content', $this->strip_only($post_body, 'iframe', true, 1));
                $r['embed'] = 'https://www.youtube.com/watch?v='
                    . $this->extractByRegex('/youtube\.com\/(?:v|embed)\/([\w\-]+)/', $post_body, 1);
                break;
            case 'text':
                $r['title'] = get_post_field('post_title', $post_id);
                // fall through
            case 'aside':
            default:
                $r['body'] = apply_filters('the_content', $post_body);
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
        preg_match($pattern, $str, $matches);
        return $matches[$group];
    }

    public function removeFromTumblr ($post_id) {
        $options = get_option('tumblr_crosspostr_settings');
        $tumblr_id = get_post_meta($post_id, 'tumblr_post_id', true);
        // TODO: Variablize the default hostname on a per-post basis.
        $this->crosspostToTumblr($options['default_hostname'], array(), $tumblr_id, true);
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
                        add_settings_error('tumblr_crosspostr_settings', 'empty-consumer-key', $errmsg);
                    }
                    $safe_input[$k] = sanitize_text_field($v);
                break;
                case 'consumer_secret':
                    if (empty($v)) {
                        $errmsg = __('Consumer secret cannot be empty.', 'tumblr-crosspostr');
                        add_settings_error('tumblr_crosspostr_settings', 'empty-consumer-secret', $errmsg);
                    }
                    $safe_input[$k] = sanitize_text_field($v);
                break;
                case 'access_token':
                case 'access_token_secret':
                case 'default_hostname':
                    $safe_input[$k] = sanitize_text_field($v);
                break;
                case 'exclude_categories':
                    $safe_v = array();
                    foreach ($v as $x) {
                        $safe_v[] = sanitize_text_field($x);
                    }
                    $safe_input[$k] = $safe_v;
                break;
                case 'additional_markup':
                    $safe_input[$k] = trim($v);
                break;
                case 'exclude_tags':
                    $safe_input[$k] = intval($v);
                break;
                case 'additional_tags':
                    $tags = explode(',', $v);
                    $safe_tags = array();
                    foreach ($tags as $t) {
                        $safe_tags[] = sanitize_text_field($t);
                    }
                    $safe_input[$k] = $safe_tags;
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
            'tumblr_crosspostr_settings',
            array($this, 'renderOptionsPage')
        );
    }

    /**
     * Writes the HTML for the options page, and each setting, as needed.
     */
    // TODO: Add contextual help menu to this page.
    public function renderOptionsPage () {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'tumblr-crosspostr'));
        }
        $options = get_option('tumblr_crosspostr_settings');
?>
<h2><?php esc_html_e('Tumblr Crosspostr Settings', 'tumblr-crosspostr');?></h2>
<form method="post" action="options.php">
<?php settings_fields('tumblr_crosspostr_settings');?>
<fieldset><legend><?php esc_html_e('Connection to Tumblr', 'tumblr-crosspostr');?></legend>
<table class="form-table" summary="<?php esc_attr_e('Required settings to connect to Tumblr.', 'tumblr-crosspostr');?>">
    <tbody>
        <tr<?php if (isset($options['access_token'])) : print ' style="display: none;"'; endif;?>>
            <th>
                <label for="tumblr_crosspostr_consumer_key"><?php esc_html_e('Tumblr API key/OAuth consumer key', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <input id="tumblr_crosspostr_consumer_key" name="tumblr_crosspostr_settings[consumer_key]" value="<?php print esc_attr($options['consumer_key']);?>" placeholder="<?php esc_attr_e('Paste your API key here', 'tumblr-crosspostr');?>" />
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
        <tr<?php if (isset($options['access_token'])) : print ' style="display: none;"'; endif;?>>
            <th>
                <label for="tumblr_crosspostr_consumer_secret"><?php esc_html_e('OAuth consumer secret', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <input id="tumblr_crosspostr_consumer_secret" name="tumblr_crosspostr_settings[consumer_secret]" value="<?php print esc_attr($options['consumer_secret']);?>" placeholder="<?php esc_attr_e('Paste your consumer secret here', 'tumblr-crosspostr');?>" />
                <p class="description">
                    <?php esc_html_e('Your consumer secret is like your app password. Never share this with anyone.', 'tumblr-crosspostr');?>
                </p>
            </td>
        </tr>
        <?php if (!isset($options['access_token']) && isset($options['consumer_key']) && isset($options['consumer_secret'])) : ?>
        <tr>
            <th>
                <label for="tumblr_crosspostr_oauth_authorize"><?php esc_html_e('Connect to Tumblr:', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <a href="<?php print wp_nonce_url(admin_url('options-general.php?page=tumblr_crosspostr_settings&tumblr_crosspostr_oauth_authorize'), 'tumblr-authorize');?>" class="button button-primary"><?php esc_html_e('Click here to connect to Tumblr','tumblr-crosspostr');?></a>
            </td>
        </tr>
        <?php elseif (isset($options['access_token'])) : ?>
        <tr>
            <th colspan="2">
                <div class="updated"><p><?php esc_html_e('Connected to Tumblr!', 'tumblr-crosspostr');?></p></div>
                <?php // TODO: Should the access tokens never be revealed to the client? ?>
                <input type="hidden" name="tumblr_crosspostr_settings[access_token]" value="<?php print esc_attr($options['access_token']);?>" />
                <input type="hidden" name="tumblr_crosspostr_settings[access_token_secret]" value="<?php print esc_attr($options['access_token_secret']);?>" />
            </th>
        </tr>
    </tbody>
</table>
</fieldset>
<fieldset><legend><?php esc_html_e('Crossposting Options', 'tumblr-crosspostr');?></legend>
<table class="form-table" summary="<?php esc_attr_e('Options for customizing crossposting behavior.', 'tumblr-crosspostr');?>">
    <tbody>
        <tr>
            <th>
                <label for="tumblr_crosspostr_default_hostname"><?php esc_html_e('Sync posts from this blog to my Tumblr at', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <select id="tumblr_crosspostr_default_hostname" name="tumblr_crosspostr_settings[default_hostname]">
                    <?php foreach ($this->tumblr->getUserBlogs() as $blog) : ?>
                    <option
                        <?php if (isset($options['default_hostname']) && $options['default_hostname'] === parse_url($blog->url, PHP_URL_HOST)) : print 'selected="selected"'; endif;?>
                        value="<?php print esc_attr(parse_url($blog->url, PHP_URL_HOST));?>">
                            <?php print esc_html($blog->title);?>
                    </option>
                    <?php endforeach;?>
                </select>
                <p class="description"><?php esc_html_e('Choose which Tumblr blog you want to send your posts to by default. This can be overriden on a per-post basis, too.', 'tumblr-crosspostr');?></p>
            </td>
        </tr>
        <?php endif;?>
        <tr>
            <th>
                <label for="tumblr_crosspost_exclude_categories"><?php esc_html_e('Do not crosspost entries in these categories:');?></label>
            </th>
            <td>
                <ul id="tumblr_crosspost_exclude_categories">
                <?php foreach (get_categories(array('hide_empty' => 0)) as $cat) : ?>
                    <li>
                        <label>
                            <input
                                type="checkbox"
                                <?php if (isset($options['exclude_categories']) && in_array($cat->slug, $options['exclude_categories'])) : print 'checked="checked"'; endif;?>
                                value="<?php print esc_attr($cat->slug);?>"
                                name="tumblr_crosspostr_settings[exclude_categories][]">
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
                <label for="tumblr_crosspostr_additional_markup"><?php esc_html_e('Add this markup to each crossposted entry.', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <textarea
                    id="tumblr_crosspostr_additional_markup"
                    name="tumblr_crosspostr_settings[additional_markup]"
                    placeholder="<?php esc_attr_e('Anything you type in this box will be added to every crosspost.', 'tumblr-crosspostr');?>"><?php
        if (isset($options['additional_markup'])) {
            print esc_html($options['additional_markup']);
        } else {
            print '<p class="tumblr-crosspostr-linkback"><a href="%permalink%" title="' . esc_html__('Go to the original post.', 'tumblr-crosspostr') . '" rel="bookmark">%the_title%</a> ' . esc_html__('was originally published on', 'tumblr_crosspostr') . ' <a href="%blog_url%">%blog_name%</a></p>';
        }
?></textarea>
                <p class="description"><?php _e('Text or HTML you want to add to each post. Useful for things like a link back to your original post. You can use <code>%permalink%</code>, <code>%the_title%</code>, <code>%blog_url%</code>, and <code>%blog_name%</code> as placeholders for the cross-posted post\'s link, its title, the link to the homepage for this site, and the name of this blog, respectively.', 'tumblr-crosspostr');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="tumblr_crosspostr_exclude_tags"><?php esc_html_e('Do not send post tags to Tumblr', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['exclude_tags'])) : print 'checked="checked"'; endif; ?> value="1" id="tumblr_crosspostr_exclude_tags" name="tumblr_crosspostr_settings[exclude_tags]" />
                <label for="tumblr_crosspostr_exclude_tags"><span class="description"><?php esc_html_e('When enabled, tags on your WordPress posts are not applied to your Tumblr posts. Useful if you maintain different taxonomies on your different sites.', 'tumblr-crosspostr');?></span></label>
            </td>
        </tr>
        <tr>
            <th>
                <label for="tumblr_crosspostr_additional_tags">
                    <?php esc_html_e('Automatically add these tags to all crossposts:', 'tumblr-crosspostr');?>
                </label>
            </th>
            <td>
                <input id="tumblr_crosspostr_additional_tags" value="<?php if (isset($options['additional_tags'])) : print esc_attr(implode(', ', $options['additional_tags'])); endif;?>" name="tumblr_crosspostr_settings[additional_tags]" placeholder="<?php esc_attr_e('crosspost, magic', 'tumblr-crosspostr');?>" />
                <p class="description"><?php print sprintf(esc_html__('Comma-separated list of additional tags that will be added to every post sent to Tumblr. Useful if only some posts on your Tumblr blog are cross-posted and you want to know which of your Tumblr posts were generated by this plugin. (These tags will always be applied regardless of the value of the "%s" option.)', 'tumblr-crosspostr'), esc_html__('Do not send post tags to Tumblr', 'tumblr-crosspostr'));?></p>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<?php submit_button();?>
</form>
<?php
    } // end public function renderOptionsPage

    private function getTumblrAppRegistrationUrl () {
        $url = 'http://www.tumblr.com/oauth/register?';
        $url .= 'oac[title]=' . get_bloginfo('name');
        $url .= '&oac[url]=' . urlencode(home_url());
        // Max 400 chars for Tumblr
        $url .= '&oac[description]=' . mb_substr(get_bloginfo('description'), 0, 400, get_bloginfo('charset'));
        $url .= '&oac[admin_contact_email]=' . get_bloginfo('admin_email');
        $url .= '&oac[default_callback_url]=' . urlencode(plugins_url(basename(__DIR__) . '/oauth-callback.php'));
        return $url;
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
