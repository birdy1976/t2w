=== T2W ===
Contributors: birdy1976
Tags: automatic, aggregator, import, json, feed, feeds, post, posts, publish, tweet, tweets, twitter
Requires at least: 3.1
Tested up to: 3.5
Stable tag: 0.2

T2W (twitter2wordpress) posts tweets of a public Twitter account automatically to a WordPress blog.

== Description ==

T2W (twitter2wordpress) posts tweets of a public Twitter account automatically to a WordPress blog.

== Installation ==

1. Log in to https://dev.twitter.com/ and create a new application to get your consumer key and secret
2. Get your copy of T2W and modify the file <oauth.php> with the consumer key and secret from Twitter
3. Install T2W by uploading the files to your server
4. After activating, you will have to visit the settings page in order to enter your Twitter username
5. That's it for you! From now on Tweets will be posted (almost) magically every hour to your blog B)

== Frequently Asked Questions ==

= It takes hours for tweets to appear on the blog. =

Some hosters cache feeds. Patience, my young Padawan...

= I've installed the plugin but nothing is posted! =

In short. Add this to wp-config.php: define('ALTERNATE_WP_CRON', true);

[Really long answer](http://wordpress.org/support/topic/scheduled-posts-still-not-working-in-282?replies=13#post-1175405 "Scheduled posts are not now, and have never been, broken"), for masochists, as the author puts it :P

= My language is missing. Can I help? =

Sure, send me the [translation files (*.po/*.mo)](http://alefba.us/how-to/localize-wordpress-themes-plugins-codestyling-localization/ "How to translate WordPress localized themes and plugins with Codestyling Localization")!

== Screenshots ==

1. Install T2W and visit the settings
2. Enter your Twitter username
3. That's how a sample post looks like

== Changelog ==

= 0.1 =
* Initial release: Here we go :)
= 0.2 =
* Change from RSS to JSON: https://dev.twitter.com/blog/api-v1-retirement-final-dates
