<?php
/**
 * Tumblr Crosspostr uninstaller
 *
 * @package plugin
 */

// Don't execute any uninstall code unless WordPress core requests it.
if (!defined('WP_UNINSTALL_PLUGIN')) { exit(); }

// Delete options.
delete_option('tumblr_crosspostr_settings');

// TODO: Should we really delete post meta? That'll wipe Tumblr post IDs. :\
