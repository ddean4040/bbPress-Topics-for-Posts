=== bbPress Topics for Posts ===
Contributors: ddean
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=6BKDCMJRYPKNN&lc=US&item_name=bbPress%20Post%20Topics&currency_code=USD
Tags: bbpress, topic, forum, post, page, comments, discussion
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 0.8

Replace the comments on your WordPress blog posts with topics from an integrated bbPress install

== Description ==

Adds an option to the Discussion meta box to use a bbPress topic instead of WordPress comments, and displays that topic beneath the post on your site.

You can let the plugin create a new topic for you in the forum of your choice, or specify an existing topic to attach to the post.
A topic can be attached to as many posts as you'd like, but only one topic can currently be attached to a post.

= Notes =

Please do not use on production sites without testing first.

== Installation ==

1. Extract the plugin archive 
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= I don't see the Discussion meta box. Where is it? =

See <a href="http://www.simplethemes.com/tutorials/help/disable-comments-in-wordpress">this article</a> for a quick tip on how to toggle the Discussion meta box.

== Screenshots ==

1. The post Discussion meta box
1. Default post settings on the Discussion page

== Changelog ==

= 0.8 =
* Changed: topic creation runs only when post is published, not when saving a draft

= 0.7 =
* Added: strip shortcodes from excerpt in topic - thanks, wpforchurch
* Added: `bbppt_topic_content_before_link` filter to change topic content without processing the link
* Changed: stopped using deprecated function for excerpt generation

= 0.6 =
* Added: `bbppt_topic_content` filter to let others change the content of new topics
* Changed: Made error strings more detailed when creating or assigning a topic fails
* Fixed: translation function error (used `_(` instead of `__(` - D'oh!) - thanks, justin-mason and David100351

= 0.5 =
* Added: can set creating a bbPress topic for discussion as default for new posts
* Changed: post meta format

= 0.4 =
* Added: option to show only replies on the post / page
* Fixed: link in forum topic was to nowhere - thanks, justin-mason
* Fixed: could not select child forums when creating a new topic - thanks, justin-mason

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

= 0.7 =
Strip shortcodes from generated excerpts and update excerpting function

= 0.6 =
Fixed a typo that generates a warning. All users should upgrade.

= 0.5 =
Added settings to Discussion page to make bbPress topics the default commenting option

= 0.4 =
Fixed a bug that put an empty link into forum topics. All users should upgrade.

= 0.3 =
Better error handling and improved integration
