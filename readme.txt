=== Tumblr Crosspostr ===
Contributors: meitar
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=meitarm%40gmail%2ecom&lc=US&item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&item_number=tumblr%2dcrosspostr&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: tumblr, post, crosspost, publishing, post formats
Requires at least: 3.1
Tested up to: 4.3
Stable tag: 0.8.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Tumblr Crosspostr cross-posts your WordPress entries to Tumblr. Changes to your WordPress posts are reflected in your Tumblr posts.

== Description ==

Tumblr Crosspostr posts to Tumblr whenever you hit the "Publish" (or "Save Draft") button. It can import your reblogs on Tumblr as native WordPress posts. It even downloads the images in your Photo posts and saves them in the WordPress Media Library.

* Transform your WordPress website into a back-end for Tumblr.
* Create original posts using WordPress, but publish them to Tumblr.
* Import your Tumblr reblogs automatically.
* [Always have a portable copy (a running backup) of your entire Tumblr blog](http://maymay.net/blog/2014/02/17/keep-a-running-backup-of-your-tumblr-reblogs-with-tumblr-crosspostr/).

*Donations for this plugin make up a chunk of my income. If you continue to enjoy this plugin, please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=meitarm%40gmail%2ecom&lc=US&item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&item_number=tumblr%2dcrosspostr&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted). :) Thank you for your support!*

Tumblr Crosspostr uses Tumblr's simple API to keep posts in sync; when you edit your WordPress post, it updates your Tumblr post. Private WordPress posts become private Tumblr posts, deleting a post from WordPress that you've previously cross-posted to Tumblr deletes it from Tumblr, too, and so on. Scheduling a WordPress post to be published any time in the future will add it to the Tumblr blog's Queue. (However, *the publishing schedule of your Tumblr queue will take precedence*, so be careful!)

Tumblr Crosspostr is very lightweight. It just requires you to connect to your Tumblr account from the plugin options screen. After that, you're ready to cross-post! See the [Screenshots](https://wordpress.org/plugins/tumblr-crosspostr/screenshots/) for a walk through of this process.

Tumblr Crosspostr uses [post formats](http://codex.wordpress.org/Post_Formats) to set the appropriate Tumblr post type. The first image, video embed, etcetera that Tumblr Crosspostr detects will be used as the primary media for the Tumblr post type. To take full advantage of Tumblr Crosspostr, I suggest you choose a WordPress theme that supports all the post formats that your Tumblr theme supports, but Tumblr Crosspostr will still work even if your theme does not natively support this feature. :)

The WordPress post format to Tumblr post type mapping looks like this:

* WordPress's `Standard`, `Aside`, and `Status` post formats become Tumblr's `Text` post type
* WordPress's `Image` post format becomes Tumblr's `Photo` post type
* WordPress's `Video` post format becomes Tumblr's `Video` post type
* WordPress's `Audio` post format becomes Tumblr's `Audio` post type
* WordPress's `Quote` post format becomes Tumblr's `Quote` post type
* WordPress's `Link` post format becomes Tumblr's `Link` post type
* WordPress's `Chat` post format becomes Tumblr's `Chat` post type
* WordPress's `Gallery` post format becomes Tumblr's `Photoset` post type (sadly this is not yet implemented, but maybe one day soon!!)

Other options enable tweaking additional metadata from your WordPress entry (notably tags and "Content source" attributions) to Tumblr, sending all your post archives to Tumblr in one click, and more.

> Servers no longer serve, they possess. We should call them possessors.

--[Ward Cunningham](https://twitter.com/WardCunningham/status/289875660246220800)

Learn more about how you can use this plugin to own your own data in conjunction with [the "Bring Your Own Content" self-hosted Web publishing virtual appliance](http://maymay.net/blog/2014/03/13/bring-your-own-content-virtual-self-hosting-web-publishing/).


== Installation ==

1. Download the plugin file.
1. Unzip the file into your 'wp-content/plugins/' directory.
1. Go to your WordPress administration panel and activate the plugin.
1. Go to Tumblr Crosspostr Settings (from the Settings menu) and either create or enter your Tumblr OAuth consumer key and consumer secret. Then click "Save Changes."
1. Once you've entered your consumer key and consumer secret, a "Connect to Tumblr" button will appear. Click that to be redirected to Tumblr's authorization page.
1. Click "Allow" to grant access to your blog from Tumblr Crosspostr.
1. Start posting!!!

See also the [Screenshots](https://wordpress.org/plugins/tumblr-crosspostr/screenshots/) section for a visual walk through of this process.

= Installation notes and troubleshooting =

Tumblr Crosspostr makes use of Manuel Lemos's `oauth_client_class` for some core functions. Most systems have the required packages installed already, but if you notice any errors upon plugin activation, first check to ensure your system's [PHP include path](http://php.net/manual/ini.core.php#ini.include-path) is set correctly. The `lib` directory and its required files look like this:

    lib
    ├── OAuthWP.php
    ├── OAuthWP_Tumblr.php
    ├── TumblrCrosspostrAPIClient.php
    ├── httpclient
    │   ├── LICENSE.txt
    │   └── http.php
    └── oauth_api
        ├── LICENSE
        ├── oauth_client.php
        └── oauth_configuration.json

It's also possible that your system administrator will apply updates to one or more of the core system packages this plugin uses without your knowledge. If this happens, and the updated packages contain backward-incompatible changes, the plugin may begin to issue errors. Should this occur, please [file a bug report on the Tumblr Crosspostr project's issue tracker](https://github.com/meitar/tumblr-crosspostr/issues/new).

== Frequently Asked Questions ==

= Can I specify a post's tags? =

Yes. WordPress's tags are also crossposted to Tumblr. If you'd like to keep your WordPress tags separate from your Tumblr tags, be certain you've enabled the "Do not send post tags to Tumblr" setting.

Additionally, the "Automatically add these tags to all crossposts" setting lets you enter a comma-separated list of tags that will always be applied to your Tumblr crossposts.

= Does Tumblr Crosspostr properly attribute content sources? =

Yes. By default, Tumblr Crosspostr will set itself up so that your WordPress blog's posts are attributed as the "Source" for each of your crossposts. Moreover, in each of your posts, you can enter a "Content source" URL in exactly the way Tumblr's own post editor lets you attribute sources, which will be entered as the "Content source" meta field on your Tumblr posts. You can even turn this feature off entirely if you're using Tumblr Crosspostr "secretly," as the back-end to a more elaborate publishing platform might do.

= Can I send older WordPress posts to Tumblr? =

Yes. Go edit the desired post, verify the crosspost option is set to `Yes`, and update the post. Tumblr Crosspostr will keep the original post date. Note that sometimes it seems to take Tumblr a few minutes to reflect many new changes, so you may want to use [Tumblr's "mega editor"](http://staff.tumblr.com/post/746164238/mega-editor) to verify that your post really made it over to Tumblr.

= What if I edit a post that has been cross-posted? =

If you edit or delete a post, changes will appear on or disappear from Tumblr accordingly.

= Can I cross-post Private posts from WordPress to Tumblr? =

Yes. Tumblr Crosspostr respects the WordPress post visibility setting and supports cross-posting private posts to Tumblr. Editing the visibility setting of your WordPress post will update your Tumblr cross-post with the new setting, as well.

= Can I cross-post custom post types? =

Yes. By default, Tumblr Crosspostr only crossposts `post` post types, but you can enable or disable other post types from the plugin's settings screen.

If you're a plugin developer, you can easily make your custom post types work well with Tumblr Crosspostr by implementing the `tumblr_crosspostr_save_post_types`, `tumblr_crosspostr_meta_box_post_types`, and `tumblr_crosspostr_prepared_post` filter hooks. See [Other Notes](https://wordpress.org/plugins/tumblr-crosspostr/other_notes/) for coding details.

= Is Tumblr Crosspostr available in languages other than English? =

This plugin has been translated into the following languages:

* French (`fr_FR`)
    * Thanks, [Julien](http://ijulien.com/)! :D

With your help it can be translated into even more! To contribute a translation of this plugin into your language, please [sign up as a translator on Tumblr Crosspostr's Transifex project page](https://www.transifex.com/projects/p/tumblr-crosspostr/).

= What if my theme doesn't support Post Formats? =

Tumblr Crosspostr will still work even if your theme doesn't support the [Post Formats](http://codex.wordpress.org/Post_Formats) feature. However, consider asking your theme developer to update your theme code so that it supports Post Formats itself for other plugins to use, too.

If you feel comfortable doing this yourself, then in most cases, this is literally a one-line change. Simply use the [add_theme_support()](http://codex.wordpress.org/Function_Reference/add_theme_support) function in your theme's `functions.php` file:

    add_theme_support('post-formats', array('link', 'image', 'quote', 'video', 'audio', 'chat''));

And if you choose to do this yourself, consider getting in touch with your theme's developer to let them know how easy it was! We devs love to hear this kind of stuff. :)

= Why won't my Tumblr post appear on my WordPress blog immediately? =

Unfortunately, Tumblr does not provide a programmatic "export" feature, so there is no way to push posts out from Tumblr. This is known as a "[data silo](https://indiewebcamp.com/silo)" and it's always enforced in the interest of corporate control so that humans are turned into dollars. Think Facebook, for example: you can easily put stuff into Facebook, but it's much harder to get that same stuff out. This is the exact opposite of WordPress in every way, both philosophically and technologically. Tumblr, in this case, is like Facebook. It, too, allows you to easily put stuff into it, but it's very hard to take stuff back out.

Tumblr Crosspostr's Tumblr Sync feature was built to work despite this harsh reality. One of the limitations is that Tumblr Crosspostr Sync can not detect when you have published a new post on Tumblr (because Tumblr never notifies your WordPress blog that this has happened). Instead, it must periodically check your Tumblr blog on its own. It polls your Tumblr blog once every twenty four hours, and then does its best to identify which posts are new. This usually works quite well, but it does mean that it can take up to 24 hours for posts created on Tumblr to show up on your WordPress website. This is another reason why creating posts on WordPress is better than creating posts on Tumblr.

If you'd like to see a world without arbitrary and unnecessary limitations that only serve corporate overseers like this, consider encouraging your friends to join and support free software platforms like WordPress, [Diaspora](https://wordpress.org/plugins/diasposter/), and other systems that let you own your own data.

== Screenshots ==

1. When you first install Tumblr Crosspostr, you'll need to connect it to your Tumblr account before you can start crossposting. This screenshot shows how its options screen first appears after you activate the plugin.

2. Once you create and enter your API key and click "Save Changes," the options screen prompts you to connect to Tumblr with another button. Press the "Click here to connect to Tumblr" button to begin the OAuth connection process.

3. After allowing Tumblr Crosspostr access to your Tumblr account, you'll find you're able to access the remainder of the options page. You must choose at least one default Tumblr blog to send your crossposts to, so this option is highlighted if it is not yet set. Set your cross-posting preferences and click "Save Changes." You're now ready to start crossposting!

4. You can optionally choose not to crosspost individual WordPress posts from the Tumblr Crosspostr custom post editing box. This box also enables you to send a specific post to a Tumblr blog other than the default one you selected in the previous step, send the post's excerpt rather than its main body to Tumblr, and [control the Tumblr auto-tweet](http://www.tumblr.com/docs/twitter) setting (if enabled on your Tumblr blog).

5. If you already have a lot of content that you want to quickly copy to Tumblr, you can use the "Tumblrize Archives" tool to do exactly that.

6. Get help where you need it from WordPress's built-in "Help" system.

== Changelog ==

= Version 0.8.4 =

* [Bugfix](https://wordpress.org/support/topic/warning-invalid-argument-12): Fix "invalid argument" error for some people when they first install the plugin.
* [Bugfix](https://wordpress.org/support/topic/strip-html-in-titles-before-posting-to-tumblr): Strip HTML tags from titles on Tumblr posts.
* Bugfix: Fix "undefined index" error for installations in strict environments when the tweet option is enabled but the tweet text is left blank.
* Bugfix: Fix several other "undefined index" errors at various locations on the plugin's settings screen.
* Tested for compatibility with WordPress 4.3.

= Version 0.8.3 =

* [Feature](https://wordpress.org/support/topic/feature-request-send-excerpt-image?replies=17#post-6665842): When importing a Photo post from Tumblr, the photo in the Tumblr post becomes the Featured Image of the WordPress post. This only happens when a Tumblr post contains a single photo.
* Compatibility with WordPress 4.2.x's new PressThis bookmarklet.

= Version 0.8.2 =

* [Bugfix](https://wordpress.org/support/topic/link-failure): Typo caused "View post on Tumblr" buttons to break. My bad. ^_^;

= Version 0.8.1 =

* Feature: Support [`rel-syndication` IndieWeb pattern](https://indiewebcamp.com/rel-syndication) as implemented by the recommended [Syndication Links](https://indiewebcamp.com/rel-syndication#How_to_link_from_WordPress) plugin.
    * `rel-syndication` is an IndieWeb best practice recommendation that provides a way to automatically link to crossposted copies (called "POSSE'd copies" in the jargon) of your posts to improve the discoverability and usability of your posts. For Tumblr Crosspostr's `rel-syndication` to work, you must also install a compatible WordPress syndication links plugin, such as the [Syndication Links](https://wordpress.org/plugins/syndication-links/) plugin, but the absence of such a plugin will not cause any problems, either.

= Version 0.8 =

* Feature: Option to cross-post any [post type](https://codex.wordpress.org/Post_Type). Not all post types can be crossposted safely, but many can, especially if they use default WordPress features like "title" and "excerpt" and so on. On important websites, don't enable crossposting for post types whose compatibility with Tumblr you are not sure of, or at least make sure you have a backup you can restore from. :)
* Developer: Three new filter hooks allow you to create your own custom post types that will be sent to Tumblr:
    * Use the new `tumblr_crosspostr_save_post_types` filter hook to programmatically add custom post types to be processed by Tumblr Crosspostr during WordPress's `save_post` action.
    * Use the new `tumblr_crosspostr_meta_box_post_types` filter hook to programmatically add or remove the Tumblr Crosspostr post editing meta box from certain post types.
    * Use the new `tumblr_crosspostr_prepared_post` filter hook to programmatically alter the `$prepared_post` object immediately before it is crossposted to Tumblr.
* Bugfix: First-time sync's now import all intended posts even when some posts are not public on Tumblr. Additionally, much-improved debug logging offers an easier way to trace sync problems.
* Bugfix: Repeated sync's no longer cause duplicated posts on PHP less than 5.4.

= Version 0.7.24 =

* [Bugfix](https://wordpress.org/support/topic/photosetmultiple-images-problem): Importing Photosets no longer duplicates the post caption for each image. Additionally, each photo's individual caption is correctly added to the `<img>` element's `alt` attribute.

= Version 0.7.23 =

* [Bugfix](https://wordpress.org/support/topic/crosspostr-ignores-filters): Avoid crossposting loops during Tumblr sync when posts are created through automatic publication.
* Usability: Improve error detail when "detailed debugging" is enabled.

= Version 0.7.22 =

* [Feature](https://wordpress.org/support/topic/sync-from-tumblr-define-category): Automatically assign categories to posts sync'ed from Tumblr. (Reminder: the sync feature is still experimental. Use with some caution.)
* Feature: Set a click-thru link for an Image/Photo post. Whatever you tell WordPress to "Link To" (in its native image editing tool) is the URL Tumblr Crosspostr will use for the click-thru link on Tumblr.

= Version 0.7.21 =

* [Bugfix](https://github.com/meitar/tumblr-crosspostr/issues/6): Correctly process posts published using "Press This" bookmarklet (and numerous other tools).
* Bugfix: Admin notices now output valid HTML.

= Version 0.7.20 =

* [Bugfix](https://wordpress.org/support/topic/featured-image-metabox-hidden-on-page-edit-screens#post-6414152): Fix conflict with theme support for [Post Thumbnails](https://codex.wordpress.org/Post_Thumbnails) on `page` post types.

= Version 0.7.19 =

* [Feature](https://github.com/meitar/tumblr-crosspostr/issues/15): Rudimentary support for Featured Images on posts. When crossposting a Photo post, if no `<img>` is found in the post body, checks to see if a Featured Image is set and uses it instead.
* Officially compatible with [WordPress Version 4.1](https://codex.wordpress.org/Version_4.1).

= Version 0.7.18 =

* [Developer](https://wordpress.org/support/topic/get-reblog-key-from-created-post): New action hook `tumblr_crosspostr_reblog_key` (calling template tag `tumblr_reblog_key`) now enables theme developers to create Tumblr reblog buttons for all crossposted posts. To print a crossposted post's Tumblr reblog key, use the following code inside The Loop:
    * `<?php do_action('tumblr_crosspostr_reblog_key');?>`

= Version 0.7.17 =

* Feature: Show "View post on Tumblr" link in Post Edit screen inside Tumblr Crosspostr Custom Metabox. Useful for reviewing individual crossposted entries and ensuring WordPress and Tumblr are still in sync.
* Bugfix: Clear WP-Cron schedules on plugin deactivation. (This improves performance, security, and prevents errors by ensuring any Tumblr synchronization routines are not invoked if you have deactivated but not deleted Tumblr Crosspostr.)

= Version 0.7.16 =

* [Feature](https://wordpress.org/support/topic/feature-request-tumblr-post-link): Show "View post on Tumblr" link in Posts listing screen.

= Version 0.7.15 =

* [Feature](https://wordpress.org/support/topic/twitter-toggle): Option to set global default for "Send tweet?" Useful for multi-author blogs and customized editorial workflows. (You can still override this on a per-post basis.)

= Version 0.7.14 =

* [Bugfix](https://github.com/meitar/tumblr-crosspostr/issues/7): Actually fix slashes in tweets.
* Tested with version 3.9.1.

= Version 0.7.13 =

* [Bugfix](https://github.com/meitar/tumblr-crosspostr/issues/7): Correctly post tweets that have quotation marks without slashes in them. (Also fixes the case where a tweet would not be posted because the added slashes pushed the tweet contents over Twitter's 140 character length limit.)

= Version 0.7.12 =

* [Bugfix](https://github.com/meitar/tumblr-crosspostr/issues/8): Treat `tumblr_post_id` meta field value as string (not integer) to prevent 32 bit systems from overflowing and attempting to edit posts with a different ID than stored in the database.

= Version 0.7.11 =

* [Bugfix](https://wordpress.org/support/topic/error-400-403): Fix "400 Bad Request" errors on attempts to crosspost large amounts of data.
* [Bugfix](https://wordpress.org/support/topic/invalid-argument-supplied-7): Fix "Invalid Argument Supplied" error when connectivity to Tumblr is flaky.

= Version 0.7.10 =

* Feature: "Sync posts from Tumblr" now imports audio files as attachments and displays them in WordPress's HTML5 player.

= Version 0.7.9 =

* Security: Improved protection for OAuth access tokens.
* Bugfix: Ensure sanitization routines do not corrupt OAuth access tokens.
* Minor code cleanup.

= Version 0.7.8.5 =

* Bugfix: Save value of "Send excerpt instead of main content?" option locally even if not sending a crosspost.
* Bugfix: Correct `title` value in "Send excerpt instead of main content?" option so tooltip help text is actually helpful.
* Minor code cleanup.

= Version 0.7.8.4 =

* Troubleshooting: New "Enable detailed debugging information?" option shows you a lot more information about errors. Use in conjunction with WordPress's built-in [`WP_DEBUG`](https://codex.wordpress.org/Debugging_in_WordPress#WP_DEBUG) and [`WP_DEBUG_LOG`](https://codex.wordpress.org/Debugging_in_WordPress#WP_DEBUG_LOG) to get even more information in your `wp-content/debug.log` file.

= Version 0.7.8.3 =

* Improved error handling:
    * Tumblr Crosspostr will tell you if crossposting fails, and will suggest possible troubleshooting steps to help you resolve the issue.
* Bugfix: Restore "Send this post to Tumblr?" field legend in meta box.

= Version 0.7.8.2 =

* Bugfix: Import media even when response code headers are not strictly numeric.

= Version 0.7.8.1 =

* Bugfix: Fix [fatal error on activation for some PHP installations](https://wordpress.org/support/topic/fatal-error-on-activation-31?replies=1#post-5318136).

= Version 0.7.8 =

* Feature: "Sync posts from Tumblr" now downloads the images in your Photo posts on Tumblr as [WordPress attachments](http://codex.wordpress.org/Attachments) and associates them with the newly-imported post (unless your WordPress uploads directory is not writable).

= Version 0.7.7 =

* Audio posts got better:
    * In addition to `mp3` files, `wav`, `wma`, `aiff`, `ogg`, `ra`, `ram`, `rm`, `mid`, `alac`, and `flac` audio files are now crossposted, too.

= Version 0.7.6.1 =

* Video posts got *even better*:
    * Videos from any source can be crossposted now, too. Just make sure the embed code the video site gives you is using an `<iframe>`. [The Onion](http://TheOnion.com/) fans, this one's for you! ;)

= Version 0.7.6 =

* Video posts got better:
    * Vimeo embeds are now crossposted, too.
    * YouTube's "privacy-enhanced" (`nocookie`) mode is now supported, so help protect your readers' privacy by embedding privacy-enhanced YouTube videos on your blog. [Quoth Teh Googlez](https://support.google.com/youtube/answer/171780?expand=PrivacyEnhancedMode#privacy): "Enabling this option means that YouTube won't store information about visitors on your web page unless they play the video." ([Learn more about why this matters](http://maymay.net/blog/2014/03/01/advertisements-are-malware/).)
* Feature: WordPress post slugs become Tumblr custom post slugs (for Tumblr blogs with that feature enabled).
* Developer: Replace [PEAR's `HTTP_OAuth`](https://pear.php.net/package/HTTP_OAuth) with [Manuel Lemos's `oauth_client_class`](https://freecode.com/projects/php-oauth-api). This is a major under-the-hood update that makes it easier for Tumblr Crosspostr's codebase to be reused with other [OAuth](http://oauth.net/) Web services. It also happens to reduce the plugin's total disk space used by about half. :)
* Bugfix: Ampersands (`&`) in crossposted tags now display correctly on Tumblr.

= Version 0.7.5 =

* Bugfix: Updating a previously published post will keep the publication date set on the original WordPress post when sending to Tumblr.

= Version 0.7.4.1 =

* Bugfix: When making a "Link" post and sending the post excerpt instead of the main content to Tumblr, the link in your excerpt is used as the featured link (if it has one) rather than the link in your main content.

= Version 0.7.4 =

* Feature: "Send excerpt instead of main content?" option lets you crosspost a post's [excerpt](http://codex.wordpress.org/Excerpt) rather than its main content. If you use this option but do not provide an excerpt manually, an automatic one is generated (similar to how the [`the_excerpt()`](http://codex.wordpress.org/Function_Reference/the_excerpt) template tag works).
* Feature: When you publish a post, a "View post on Tumblr" link offers an easy way to see your crossposted entry.

= Version 0.7.3 =

* Feature: Customize the auto-tweet when publishing a new post. This only works if the Tumblr blog you're crossposting to is already connected to a Twitter account. [Tumblr's documentation explains how to connect your Tumblr blog to your Twitter account](http://www.tumblr.com/docs/twitter).

= Version 0.7.2 =

* Feature: Save a given post's reblog key when importing that post with the "Sync posts from Tumblr" feature. Theme authors can then use the `tumblr_reblog_key` [custom field](http://codex.wordpress.org/Custom_Fields) to create a link on the WordPress post that lets a user reblog the original post on Tumblr. For instance:
    * `<a href="http://www.tumblr.com/reblog/<?php echo get_post_meta($post->ID, 'tumblr_post_id', true);?>/<?php echo get_post_meta($post->ID, 'tumblr_reblog_key', true);?>?redirect_to=<?php echo esc_url(get_permalink());?>">`
* Feature: Manual Tumblr disconnection button. If you want to change the Tumblr account or OAuth application credentials used to connect to Tumblr after you made a prior connection, you can now use the "Disconnect" button to disestablish your existing connection and create it anew.

= Version 0.7.1 =

* Bugfix: Use PHP's `__FILE__` constant instead of `__DIR__` to support PHP 5.2.x installations.

= Version 0.7 =

* Feature: "Sync posts from Tumblr" will import posts you create on your Tumblr blog(s) into your WordPress blog, along with their metadata such as tags, post types/formats, and content sources. This is useful for creating an automatic backup of the conversations you have in reblog threads on Tumblr.
    * When first activated, your entire Tumblr archive will be copied (including private posts).
    * Once every 24 hours, Tumblr Crosspostr will fetch up to the most recent 100 posts on your Tumblr blog to see if you have reblogged anything on Tumblr. If you have, Tumblr Crosspostr will import those posts to your WordPress blog.
    * Posts you created on Tumblr using Tumblr Crosspostr will not be duplicated.
    * Once imported to WordPress, edits you make on Tumblr are not retrieved, but edits you make on WordPress are sent back to Tumblr, so prefer using WordPress to edit and update your imported posts.
    * **This feature is experimental.** Please make sure you have a backup of your WordPress website before you enable sync'ing from Tumblr.

= Version 0.6.2 =

* French translation (`fr_FR`). Activate the French version of this plugin by [configuring your WordPress to use that language](http://codex.wordpress.org/WordPress_in_Your_Language). Want Tumblr Crosspostr in your language? [Help us translate](https://www.transifex.com/signup/contributor/?next=/projects/p/tumblr-crosspostr/)!
* Bugfix: Contextual help's support and donation links now open in new tabs or windows so you don't lose your place when writing.

= Version 0.6.1 = 

* Feature: Detailed help is now available from the WordPress post screen's "Help" tab.

= Version 0.6 =

* Feature: "Tumblrize Archives" tool crossposts all post archives to Tumblr. This feature respects individual post settings, if there are any already set. This is especially useful as a one-time operation when you first install Tumblr Crosspostr to quickly crosspost your existing blog over to Tumblr. Note that this is not a "Sync" button in that it cannot delete posts from Tumblr that have already been deleted from WordPress (because those posts no longer exist in WordPress). To delete posts from WordPress and Tumblr in batch, use WordPress's standard "Move to trash" buttons.

= Version 0.5.1 =

* Bugfix: Correctly capture all lines in a multiple-line `<blockquote>`.

= Version 0.5 =

* Feature: Automatically register supported post formats. This means you can now use Tumblr Crosspostr even with themes that do not natively support the post format you want.
* Bugfix: Don't try cross-posting if we don't have an API connection to Tumblr.

= Version 0.4 =

* "Quote" posts got better:
    * Fixes bug where certain `<blockquote>` code caused failure to post to Tumblr.
    * Start your WordPress post with a `<blockquote>` and everything in your post after the first `</blockquote>` will be used for the "Source" field on Tumblr, including HTML.

= Version 0.3 =

* Feature: Implement support for [Tumblr's meta "Content source" field](http://staff.tumblr.com/post/1059624418/content-attribution). Use the `Content source` field in Tumblr Crosspostr's post editing box to set the "Content source" field of your post on Tumblr.
* Security: Harden HTML placeholder replacement subroutine.
* Other minor improvements fix several PHP `E_NOTICE` messages on extremely sensitive systems.

= Version 0.2 =

* Feature: Implement support for `Chat` post format and type. Simply write your WordPress chat post the way Tumblr expects, one remark per line with speaker labels, like this:

        Person A: Some inane observation.
        Person B: Some witty retort.

= Verson 0.1 =

* Initial release.

== Other notes ==

Maintaining this plugin is a labor of love. However, if you like it, please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=meitarm%40gmail%2ecom&lc=US&item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&item_number=tumblr%2dcrosspostr&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted) for your use of the plugin, [purchasing one of Meitar's web development books](http://www.amazon.com/gp/redirect.html?ie=UTF8&location=http%3A%2F%2Fwww.amazon.com%2Fs%3Fie%3DUTF8%26redirect%3Dtrue%26sort%3Drelevancerank%26search-type%3Dss%26index%3Dbooks%26ref%3Dntt%255Fathr%255Fdp%255Fsr%255F2%26field-author%3DMeitar%2520Moscovitz&tag=maymaydotnet-20&linkCode=ur2&camp=1789&creative=390957) or, better yet, contributing directly to [Meitar's Cyberbusking fund](http://Cyberbusking.org/). (Publishing royalties ain't exactly the lucrative income it used to be, y'know?) Your support is appreciated!

= Developer reference =

Tumblr Crosspostr provides the following hooks for plugin and theme authors:

*Filters*

* `tumblr_crosspostr_save_post_types` - Filter an array of custom post type names to process when Tumblr Crosspostr is invoked in the `save_post` WordPress action.
* `tumblr_crosspostr_meta_box_post_types` - Filter an array of custom post type names for which to show the Tumblr Crosspostr post editing meta box.
* `tumblr_crosspostr_prepared_post` - Filter the `$prepared_post` object immediately before it gets crossposted to Tumblr

*Actions*

* `tumblr_crosspostr_reblog_key` - Prints the Tumblr Reblog Key for a given post.
