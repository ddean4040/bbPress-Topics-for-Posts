=== bbPress Topics for Posts ===
Contributors: ddean
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=6BKDCMJRYPKNN&lc=US&item_name=bbPress%20Post%20Topics&currency_code=USD
Tags: bbpress, topic, forum, post, page, comments, discussion
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 0.4

Replace the comments on your WordPress blog posts with topics from an integrated bbPress install

== Description ==

Adds an option to the Discussion meta box to use a bbPress topic instead of WordPress comments.

You can let the plugin create a new topic for you in the forum of your choice, or specify an existing topic to attach to the post.
A topic can be attached to as many posts as you'd like, but only one topic can currently be attached to a post.

= Notes =

This is still very new code!  Please do not use on production sites without extensive testing.

== Installation ==

1. Extract the plugin archive 
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

No questions yet, so fire away!

== Screenshots ==

1. The post meta box

== Changelog ==

= 0.3 =
* Added: show reply count instead of comment count for posts with an attached forum topic
* Changed: display an error if bbPress is not detected instead of failing silently
* Changed: how bbPress is detected

= 0.2 =
* Added: can use a topic ID or topic post_name/slug for existing topic
* Added: better handling of bbPress environment
* Fixed: use existing topic now works as expected
* Fixed: finally settled on a name

= 0.1 =
Initial Release

== Upgrade Notice ==

= 0.3 =
Better error handling and improved integration
