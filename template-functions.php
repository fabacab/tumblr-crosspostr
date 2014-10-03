<?php
/**
 * Tumblr Crosspostr Template Tags
 *
 * @package Plugin
 */

function tumblr_reblog_key($post_id = false) {
    global $post, $tumblr_crosspostr;
    if (empty($post_id)) {
        $post_id = $post->ID;
    }
    print $tumblr_crosspostr->getReblogKey($post_id);
}
