=== Tumblr Crosspostr ===
Contributors: maymay
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=TJLPJYXHSRBEE&amp;lc=US&amp;item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&amp;item_number=tumblr-crosspostr&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: tumblr, post, crosspost, publishing, post formats
Requires at least: 4.4
Tested up to: 4.7.1
Stable tag: 0.9.0
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Tumblr Crosspostr cross-posts your WordPress entries to Tumblr. Changes to your WordPress posts are reflected in your Tumblr posts.

== Description ==

Tumblr Crosspostr posts to Tumblr whenever you hit the "Publish" (or "Save Draft") button. It can import your reblogs on Tumblr as native WordPress posts. It even downloads the images in your Photo posts and saves them in the WordPress Media Library.

* Transform your WordPress website into a back-end for Tumblr.
* Create original posts using WordPress, but publish them to Tumblr.
* Import your Tumblr reblogs automatically.
* [Always have a portable copy (a running backup) of your entire Tumblr blog](http://maymay.net/blog/2014/02/17/keep-a-running-backup-of-your-tumblr-reblogs-with-tumblr-crosspostr/).

*Donations for this plugin make up a chunk of my income. If you continue to enjoy this plugin, please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=TJLPJYXHSRBEE&amp;lc=US&amp;item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&amp;item_number=tumblr-crosspostr&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted). :) Thank you for your support!*

Tumblr Crosspostr uses Tumblr's simple API to keep posts in sync; when you edit your WordPress post, it updates your Tumblr post. Private WordPress posts become private Tumblr posts, deleting a post from WordPress that you've previously cross-posted to Tumblr deletes it from Tumblr, too, and so on. The plugin supports both scheduled posts (scheduling a WordPress post schedules it to publish at the same time on Tumblr) as well as the Tumblr Queue feature.

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

It's also possible that your system administrator will apply updates to one or more of the core system packages this plugin uses without your knowledge. If this happens, and the updated packages contain backward-incompatible changes, the plugin may begin to issue errors. Should this occur, please [file a bug report on the Tumblr Crosspostr project's issue tracker](https://github.com/fabacab/tumblr-crosspostr/issues/new).

If images are not posting correctly please check that your WordPress blog is not using HTTPS. The Tumblr API does not support HTTPS image sources via the API.

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

== Upgrade Notice ==

= 0.9.0 =

Scheduled posts are now supported on the Tumblr side. Scheduling a post in WordPress no longer adds it to the Tumblr Queue.

== Changelog ==

= 0.9 =

* [Feature](https://github.com/fabacab/tumblr-crosspostr/issues/25): Scheduled posting support.
* Feature: Option to "leave no trace" when uninstalling the plugin. This is dangerous and off by default. It will permanently erase *all* associations between your WordPress posts and your Tumblr posts, deleting all Tumblr-relatd metadata for your WordPress content.

= 0.8.8 =

Bugfix: Quiet duplicated network requests to Tumblr's `/user/info` API endpoint.

= 0.8.7 =

* Feature: Use Tumblr's `native_inline_images` to avoid the "External image" display on the Tumblr Dash. (Props @jeraimee.)

= 0.8.6 =

* [Bugfix](https://github.com/fabacab/tumblr-crosspostr/issues/26): Correctly translate WordPress `<!-- more -->` link to Tumblr's equivalent.

= 0.8.5 =

* [Bugfix](https://github.com/fabacab/tumblr-crosspostr/issues/24): Recognize audio generated from WordPress shortcodes.
* Usability: Nicer styles for the plugin's settings screen.
* Developer: Update libraries (this fixes several outstanding bugs that were present in the packaged library versions).

= 0.8.4 =

* [Bugfix](https://wordpress.org/support/topic/warning-invalid-argument-12): Fix "invalid argument" error for some people when they first install the plugin.
* [Bugfix](https://wordpress.org/support/topic/strip-html-in-titles-before-posting-to-tumblr): Strip HTML tags from titles on Tumblr posts.
* Bugfix: Fix "undefined index" error for installations in strict environments when the tweet option is enabled but the tweet text is left blank.
* Bugfix: Fix several other "undefined index" errors at various locations on the plugin's settings screen.
* Tested for compatibility with WordPress 4.3.

= 0.8.3 =

* [Feature](https://wordpress.org/support/topic/feature-request-send-excerpt-image?replies=17#post-6665842): When importing a Photo post from Tumblr, the photo in the Tumblr post becomes the Featured Image of the WordPress post. This only happens when a Tumblr post contains a single photo.
* Compatibility with WordPress 4.2.x's new PressThis bookmarklet.

= 0.8.2 =

* [Bugfix](https://wordpress.org/support/topic/link-failure): Typo caused "View post on Tumblr" buttons to break. My bad. ^_^;

= 0.8.1 =

* Feature: Support [`rel-syndication` IndieWeb pattern](https://indiewebcamp.com/rel-syndication) as implemented by the recommended [Syndication Links](https://indiewebcamp.com/rel-syndication#How_to_link_from_WordPress) plugin.
    * `rel-syndication` is an IndieWeb best practice recommendation that provides a way to automatically link to crossposted copies (called "POSSE'd copies" in the jargon) of your posts to improve the discoverability and usability of your posts. For Tumblr Crosspostr's `rel-syndication` to work, you must also install a compatible WordPress syndication links plugin, such as the [Syndication Links](https://wordpress.org/plugins/syndication-links/) plugin, but the absence of such a plugin will not cause any problems, either.

= 0.8 =

* Feature: Option to cross-post any [post type](https://codex.wordpress.org/Post_Type). Not all post types can be crossposted safely, but many can, especially if they use default WordPress features like "title" and "excerpt" and so on. On important websites, don't enable crossposting for post types whose compatibility with Tumblr you are not sure of, or at least make sure you have a backup you can restore from. :)
* Developer: Three new filter hooks allow you to create your own custom post types that will be sent to Tumblr:
    * Use the new `tumblr_crosspostr_save_post_types` filter hook to programmatically add custom post types to be processed by Tumblr Crosspostr during WordPress's `save_post` action.
    * Use the new `tumblr_crosspostr_meta_box_post_types` filter hook to programmatically add or remove the Tumblr Crosspostr post editing meta box from certain post types.
    * Use the new `tumblr_crosspostr_prepared_post` filter hook to programmatically alter the `$prepared_post` object immediately before it is crossposted to Tumblr.
* Bugfix: First-time sync's now import all intended posts even when some posts are not public on Tumblr. Additionally, much-improved debug logging offers an easier way to trace sync problems.
* Bugfix: Repeated sync's no longer cause duplicated posts on PHP less than 5.4.

Version history has been truncated due to [WordPress.org plugin repository `readme.txt` file length limitations](https://wordpress.org/support/topic/wordpress-plugin-repository-readmetxt-length-limit?replies=1). For [historical change log information](https://plugins.trac.wordpress.org/browser/tumblr-crosspostr/tags/0.8/readme.txt#L150), please refer to the plugin source code repository.

== Other notes ==

Maintaining this plugin is a labor of love. However, if you like it, please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=TJLPJYXHSRBEE&amp;lc=US&amp;item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&amp;item_number=tumblr-crosspostr&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted) for your use of the plugin or, better yet, contributing directly to [my's Cyberbusking fund](http://Cyberbusking.org/). Your support is appreciated!

= Developer reference =

Tumblr Crosspostr provides the following hooks for plugin and theme authors:

*Filters*

* `tumblr_crosspostr_save_post_types` - Filter an array of custom post type names to process when Tumblr Crosspostr is invoked in the `save_post` WordPress action.
* `tumblr_crosspostr_meta_box_post_types` - Filter an array of custom post type names for which to show the Tumblr Crosspostr post editing meta box.
* `tumblr_crosspostr_prepared_post` - Filter the `$prepared_post` object immediately before it gets crossposted to Tumblr

*Actions*

* `tumblr_crosspostr_reblog_key` - Prints the Tumblr Reblog Key for a given post.
