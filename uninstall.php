<?php
/**
 * Tumblr Crosspostr uninstaller
 *
 * @package WordPress/Plugin/Tumblr_Crosspostr/Uninstaller
 */

// Don't execute any uninstall code unless WordPress core requests it.
if (!defined('WP_UNINSTALL_PLUGIN')) { exit(); }

// Delete all associated post metadata if instructed to.
$options = get_option('tumblr_crosspostr_settings');
if ($options['leave_no_trace']) {
    delete_post_meta_by_key('tumblr_post_id');
    delete_post_meta_by_key('tumblr_base_hostname');
    delete_post_meta_by_key('tumblr_reblog_key');
}

// Delete all temporary post metadata values.
delete_post_meta_by_key('tumblr_crosspostr_crosspost');

// Delete options.
delete_option('tumblr_crosspostr_settings');
delete_option('_tumblr_crosspostr_admin_notices');
delete_option('tumblr_crosspostr_access_token');
delete_option('tumblr_crosspostr_access_token_secret');
