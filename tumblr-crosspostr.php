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
    private $tumblr_post_types = array(
        'text',
        'photo',
        'link',
        'quote',
        'audio',
        'video',
        'chat'
    );

    public function __construct () {
        add_action('plugins_loaded', array($this, 'registerL10n'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_menu', array($this, 'registerAdminMenu'));

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

        // i18n labels for post types
        $this->tumblr_post_types = array(
            'text' => __('Text', 'tumblr-crosspostr'),
            'photo' => __('Photo', 'tumblr-crosspostr'),
            'link' => __('Link', 'tumblr-crosspostr'),
            'quote' => __('Quote', 'tumblr-crosspostr'),
            'audio' => __('Audio', 'tumblr-crosspostr'),
            'video' => __('Video', 'tumblr-crosspostr'),
            'chat' => __('Chat', 'tumblr-crosspostr')
        );
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
                    $safe_input[$k]   = sanitize_text_field($v);
                break;
                case 'access_token':
                case 'access_token_secret':
                case 'default_hostname':
                case 'default_post_type':
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
                    $safe_v = trim($v);
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
                <label for="tumblr_crosspostr_default_post_type"><?php esc_html_e('Default Tumblr post type', 'tumblr-crosspostr');?></label>
            </th>
            <td>
                <select id="tumblr_crosspostr_default_post_type" name="tumblr_crosspostr_settings[default_post_type]">
                    <?php foreach ($this->tumblr_post_types as $k => $v) : ?>
                        <option
                            <?php if (isset($options['default_post_type']) && $options['default_post_type'] === $k) : print 'selected="selected"'; endif;?>
                            value="<?php print esc_attr($k);?>">
                                <?php print esc_html($v);?>
                        </option>
                    <?php endforeach;?>
                </select>
                <p class="description"><?php print sprintf(esc_html__('Select a default type. Useful if you usually publish posts of a specific type. (Defaults to %s.)', 'tumblr-crosspostr'), '<code>' . esc_html__('Text', 'tumblr-crosspostr') . '</code>');?></p>
            </td>
        </tr>
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
            print '<p class="tumblr-crosspostr-linkback"><a href="%permalink%" title="' . esc_html__('Go to the original post.', 'tumblr-crosspostr') . '" rel="bookmark">%posttitle%</a> ' . esc_html__('was originally published on', 'tumblr_crosspostr') . ' <a href="%bloglink%">%blogname%</a></p>';
        }
?></textarea>
                <p class="description"><?php _e('Text or HTML you want to add to each post. Useful for things like a link back to your original post. You can use <code>%permalink%</code>, <code>%posttitle%</code>, <code>%bloglink%</code>, and <code>%blogname%</code> as placeholders for the cross-posted post\'s link, its title, the link to the homepage for this site, and the name of this blog, respectively.', 'tumblr-crosspostr');?></p>
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
                <input id="tumblr_crosspostr_additional_tags" value="<?php if (isset($options['additional_tags'])) : print esc_attr(implode(', ', $options['additional_tags'])); endif;?>" name="tumblr_crosspostr_settings[additional_tags]" placeholder="<?php esc_attr_e('xpost, magic', 'tumblr-crosspostr');?>" />
                <p class="description"><?php print esc_html_e('Comma-separated list of additional tags that will be added to every post sent to Tumblr. Useful if not all your posts are cross-posted and later on you want to know which ones were generated by this plugin.', 'tumblr-crosspostr');?></p>
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
}

$tumblr_crosspostr = new Tumblr_Crosspostr();
