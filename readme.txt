=== Tumblr Crosspostr ===
Contributors: meitar
Tags: tumblr, post, crosspost, publishing, post formats
Requires at least: 3.1
Tested up to: 3.8.1
Stable tag: 0.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Tumblr Crosspostr cross-posts your published WordPress entries to Tumblr. All you need is a Tumblr account. Changes you make to your WordPress posts are reflected in your Tumblr posts.

== Description ==

Tumblrize posts to Tumblr whenever you hit the "publish" button. It uses Tumblr's simple API to keep posts in sync; when you edit your WordPress post, it updates your Tumblr post.

Tumblrize is very lightweight. It just requires you to connect your WordPress with your Tumblr account. After that, you're ready to cross-post! Tumblr Crosspostr uses [post formats](http://codex.wordpress.org/Post_Formats) to automatically choose a Tumblr post type to match. This means the "Standard" post format on your WordPress blog is treated as a "Text" post on your Tumblr blog, a post with the "Video" post format becomes a "Video" post on Tumblr, and so on. To take full advantage of Tumblr Crosspostr, I suggest you choose a WordPress theme that supports all the post formats that your Tumblr theme supports. :)

The WordPress post format to Tumblr post type mapping looks like this:

* WordPress's `Standard` post format becomes Tumblr's `Text` post type
* WordPress's `Aside` and `Status` post formats become Tumblr's `Text` post type (without a title)
* WordPress's `Image` post format becomes Tumblr's `Photo` post type
* WordPress's `Video` post format becomes Tumblr's `Video` post type
* WordPress's `Audio` post format becomes Tumblr's `Audio` post type
* WordPress's `Quote` post format becomes Tumblr's `Quote` post type
* WordPress's `Link` post format becomes Tumblr's `Link` post type
* WordPress's `Gallery` post format becomes Tumblr's `Photoset` post type
* WordPress's `Chat` post format becomes Tumblr's `Chat` post type

Other options allow sending additional metadata from your WordPress entry (notably tags) to Tumblr, and more.

== Installation ==

1. Download the plugin file.
1. Unzip the file into your 'wp-content/plugins/' directory.
1. Go to your WordPress administration panel and activate the plugin.
1. Go to Tumblrize Options (from the Settings menu) and provide your Tumblr login information.

== Frequently Asked Questions ==

= Can I specify tags? =

Yes. WordPress's tags are also crossposted to Tumblr. Be certain you have enabled the "Add post tags, too?" setting in the plugin's option screen.

= Can I send older WordPress posts to Tumblr? =

Yes. Go edit the desired post, verify the crosspost option is set to `Yes`, and update the post. Tumblr Crosspostr will keep the original post date.

= What if I edit a post that has been cross-posted? =

If you edit or delete a post, changes will appear on Tumblr accordingly.

= Can I cross-post Private posts from WordPress to Tumblr? =

No. Currently Tumblr Crosspostr only supports cross-posting public posts (i.e., posts with the status of `publish`). If you would like support for private posts, please indicate your feature request on [our issue tracker](https://github.com/meitar/wp-seedbank/issues?labels=enhancement).

== Screenshots ==

1. The Tumblr Crosspostr options screen.

2. The Tumblr Crosspostr custom post editing box, allowing you to specify individual Tumblr Crosspostr options on a per-post basis.

== Changelog ==

= Verson 0.1 =

* Initial release.

== Other notes ==

Maintaining this plugin is a labor of love. However, if you like it, please consider [purchasing one of Meitar's web development books](http://www.amazon.com/gp/redirect.html?ie=UTF8&location=http%3A%2F%2Fwww.amazon.com%2Fs%3Fie%3DUTF8%26redirect%3Dtrue%26sort%3Drelevancerank%26search-type%3Dss%26index%3Dbooks%26ref%3Dntt%255Fathr%255Fdp%255Fsr%255F2%26field-author%3DMeitar%2520Moscovitz&tag=maymaydotnet-20&linkCode=ur2&camp=1789&creative=390957) or contributing to [Meitar's Cyberbusking fund](http://Cyberbusking.org/). Your support is appreciated!
