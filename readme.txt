=== Tumblr Crosspostr ===
Contributors: meitar
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=meitarm%40gmail%2ecom&lc=US&item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&item_number=tumblr%2dcrosspostr&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: tumblr, post, crosspost, publishing, post formats
Requires at least: 3.1
Tested up to: 3.8.1
Stable tag: 0.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Tumblr Crosspostr cross-posts your published WordPress entries to Tumblr. Changes to your WordPress posts are reflected in your Tumblr posts.

== Description ==

Tumblr Crosspostr posts to Tumblr whenever you hit the "Publish" (or "Save Draft") button. It uses Tumblr's simple API to keep posts in sync; when you edit your WordPress post, it updates your Tumblr post. Private WordPress posts become private Tumblr posts, deleting a post from WordPress that you've previously cross-posted to Tumblr deletes it from Tumblr, too, and so on. Scheduling a WordPress post to be published any time in the future will add it to the Tumblr blog's Queue. (However, the publishing schedule of your Tumblr queue will take precedence, so be careful!)

Tumblr Crosspostr is very lightweight. It just requires you to connect to your Tumblr account from the plugin options screen. After that, you're ready to cross-post!

Tumblr Crosspostr uses [post formats](http://codex.wordpress.org/Post_Formats) to automatically choose the appropriate Tumblr post type. The first image, video embed, etcetera that Tumblr Crosspostr detects will be used as the primary media for the Tumblr post type. To take full advantage of Tumblr Crosspostr, I suggest you choose a WordPress theme that supports all the post formats that your Tumblr theme supports. :)

The WordPress post format to Tumblr post type mapping looks like this:

* WordPress's `Standard`, `Aside`, and `Status` post formats become Tumblr's `Text` post type
* WordPress's `Image` post format becomes Tumblr's `Photo` post type
* WordPress's `Video` post format becomes Tumblr's `Video` post type
* WordPress's `Audio` post format becomes Tumblr's `Audio` post type
* WordPress's `Quote` post format becomes Tumblr's `Quote` post type
* WordPress's `Link` post format becomes Tumblr's `Link` post type
* WordPress's `Gallery` post format becomes Tumblr's `Photoset` post type (sadly this is not yet implemented, but maybe one day soon!!)
* WordPress's `Chat` post format becomes Tumblr's `Chat` post type (sadly this is not yet implemented, but maybe one day soon!!)

Other options enable tweaking additional metadata from your WordPress entry (notably tags) to Tumblr, and more.

Tumblr Crosspostr transforms your WordPress website into a back-end for Tumblr. Create your posts using WordPress, but publish to Tumblr. This means you'll always have a portable copy of your entire Tumblr blog.

== Installation ==

1. Download the plugin file.
1. Unzip the file into your 'wp-content/plugins/' directory.
1. Go to your WordPress administration panel and activate the plugin.
1. Go to Tumblr Crosspostr Settings (from the Settings menu) and either create or enter your Tumblr OAuth consumer key and consumer secret. Then click "Save Changes."
1. Once you've entered your consumer key and consumer secret, a "Connect to Tumblr" button will appear. Click that to be redirected to Tumblr's authorization page.
1. Click "Allow" to grant access to your blog from Tumblr Crosspostr.
1. Start posting!!!

See also the [Screenshots](https://wordpress.org/plugins/tumblr-crosspostr/screenshots/) section for a visual walk through of this process.

=== Installation notes and troubleshooting ==

Tumblr Crosspostr makes use of the [PHP Extension and Application Repository (PEAR)](http://pear.php.net/) for some core functions. Most systems have the required PEAR packages installed already and, if so, Tumblr Crosspostr prefers to use those. However, I also ship Tumblr Crosspostr with the required PEAR packages inside the `lib/pear/php` directory of the plugin, as a fallback.

If you notice any errors upon plugin activation, first check to ensure your system's [PHP include path](http://php.net/manual/ini.core.php#ini.include-path) is set correctly. If one of the paths in this variable does not contain the required PEAR components, Tumblr Crosspostr won't work. The `lib` directory and its required files look like this:

    lib
    ├── TumblrCrosspostrAPIClient.php
    └── pear
        └── php
            ├── HTTP
            │   ├── OAuth
            │   │   ├── Consumer
            │   │   │   ├── Exception
            │   │   │   │   └── InvalidResponse.php
            │   │   │   ├── Request.php
            │   │   │   └── Response.php
            │   │   ├── Consumer.php
            │   │   ├── Exception
            │   │   │   └── NotImplemented.php
            │   │   ├── Exception.php
            │   │   ├── Message.php
            │   │   ├── Signature
            │   │   │   ├── Common.php
            │   │   │   └── HMAC
            │   │   │       └── SHA1.php
            │   │   └── Signature.php
            │   ├── OAuth.php
            │   ├── Request2
            │   │   ├── Adapter
            │   │   │   ├── Curl.php
            │   │   │   ├── Mock.php
            │   │   │   └── Socket.php
            │   │   ├── Adapter.php
            │   │   ├── CookieJar.php
            │   │   ├── Exception.php
            │   │   ├── MultipartBody.php
            │   │   ├── Observer
            │   │   │   └── Log.php
            │   │   ├── Response.php
            │   │   └── SocketWrapper.php
            │   └── Request2.php
            ├── Net
            │   └── URL2.php
            ├── PEAR
            │   └── Exception.php
            ├── PEAR.php
            └── PEAR5.php

It's also possible that your system administrator will apply updates to one or more of these PEAR packages without your knowledge. If this happens, and the updated packages contain backward-incompatible changes, Tumblr Crosspostr may begin to issue errors. If this happens, please [file a bug report on the Tumblr Crosspostr project's issue tracker](https://github.com/meitar/tumblr-crosspostr/issues/new).

== Frequently Asked Questions ==

= Can I specify a post's tags? =

Yes. WordPress's tags are also crossposted to Tumblr. If you'd like to keep your WordPress tags separate from your Tumblr tags, be certain you've enabled the "Do not send post tags to Tumblr" setting.

Additionally, the "Automatically add these tags to all crossposts" setting lets you enter a comma-separated list of tags that will always be applied to your Tumblr crossposts.

= Can I send older WordPress posts to Tumblr? =

Yes. Go edit the desired post, verify the crosspost option is set to `Yes`, and update the post. Tumblr Crosspostr will keep the original post date. Note that sometimes it it seems to take Tumblr a few minutes to reflect many new changes, so you may want to use [Tumblr's "mega editor"](http://staff.tumblr.com/post/746164238/mega-editor) to verify that your post really made it over to Tumblr.

= What if I edit a post that has been cross-posted? =

If you edit or delete a post, changes will appear on Tumblr accordingly.

= Can I cross-post Private posts from WordPress to Tumblr? =

Yes. Tumblr Crosspostr respects the WordPress post visibility setting and supports cross-posting private posts to Tumblr. Editing the visibility setting of your WordPress post will update your Tumblr cross-post with the new setting, as well.

= Is Tumblr Crosspostr available in languages other than English? =

Not yet, but with your help it can be. To help translate the plugin into your language, please [sign up as a translator on Tumblr Crosspostr's Transifex project page](https://www.transifex.com/projects/p/tumblr-crosspostr/).

== Screenshots ==

1. When you first install Tumblr Crosspostr, you'll need to connect it to your Tumblr account before you can start crossposting. This screenshot shows how its options screen first appears after you active the plugin.

2. Once you create and enter your API key and click "Save Changes," the options screen prompts you to connect to Tumblr with another button. Press the "Click here to connect to Tumblr" button to begin the OAuth connection process.

3. After allowing Tumblr Crosspostr access to your Tumblr account, you'll find you're able to access the remainder of the options page. You must choose at least one default Tumblr blog to send your crossposts to, so this option is highlighted if it is not yet set. Set your cross-posting preferences and click "Save Changes." You're now ready to start crossposting!

4. You can optionally choose not to crosspost individual WordPress posts from the Tumblr Crosspostr custom post editing box. This box also enables you to send a specific post to a Tumblr blog other than the default one you selected in the previous step.

== Changelog ==

= Verson 0.1 =

* Initial release.

== Other notes ==

Maintaining this plugin is a labor of love. However, if you like it, please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=meitarm%40gmail%2ecom&lc=US&item_name=Tumblr%20Crosspostr%20WordPress%20Plugin&item_number=tumblr%2dcrosspostr&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted) for your use of the plugin, [purchasing one of Meitar's web development books](http://www.amazon.com/gp/redirect.html?ie=UTF8&location=http%3A%2F%2Fwww.amazon.com%2Fs%3Fie%3DUTF8%26redirect%3Dtrue%26sort%3Drelevancerank%26search-type%3Dss%26index%3Dbooks%26ref%3Dntt%255Fathr%255Fdp%255Fsr%255F2%26field-author%3DMeitar%2520Moscovitz&tag=maymaydotnet-20&linkCode=ur2&camp=1789&creative=390957) or, better yet, contributing directly to [Meitar's Cyberbusking fund](http://Cyberbusking.org/). (Publishing royalties ain't exactly the lucrative income it used to be, y'know?) Your support is appreciated!
