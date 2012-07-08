=== bbPress Topics for Posts ===
Contributors: ddean
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=6BKDCMJRYPKNN&lc=US&item_name=bbPress%20Post%20Topics&currency_code=USD
Tags: bbpress, topic, forum, post, page, comments, discussion
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.3

Replace the comments on your WordPress blog posts with topics from an integrated bbPress install

== Description ==

Adds an option to the Discussion meta box to use a bbPress topic instead of WordPress comments, and displays that topic beneath the post on your site.

You can let the plugin create a new topic for you in the forum of your choice, or specify an existing topic to attach to the post.
A topic can be attached to as many posts as you'd like, but only one topic can currently be attached to a post.

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
1. Comment bubble replaced with the number of forum posts

== Changelog ==

= 1.3 =
* Added: support for copying existing comments to bbPress topic - thanks, javiarques!
* Added: support for loading template files from theme directory
* Changed: cleaned up the Discussion meta box a bit
* Changed: moved away from $bbp since it has been dropped in bbPress 2.1
* Fixed: not all forums appeared in dropdowns in some cases - thanks, javiarques

= 1.2 =
* Added: support for copying tags from post to topic
* Added: support for creating topics for XML-RPC posts (only if creating a topic is set in defaults)
* Added: translation support - thanks, efedoso
* Changed: addslashes to topic body, fixing some topic content issues - thanks, sangil

= 1.1 =
* Added: support for bbPress 2.1
* Fixed: Bug that broke "Link to topic" option until default discussion settings were saved

= 1.0 =
* Changed: default topic text is processed with kses
* Fixed: bug that could prevent going back to custom settings once defaults were selected
* Fixed: short_open_tag issues - thanks, dalekoop and drsim
* Fixed: error when default discussion settings hadn't been saved - thanks, richardjay

= 0.9.5 =
* Added: option to display only link to topic under post
* Added: option to display newest/oldest x replies under post
* Added: change the content of the new topic from the Discussion settings page
* Added: option to "Use default settings" to keep topic display uniform
* Changed: added placeholder text to make existing topic selection more intuitive - thanks, qgil
* Changed: internal settings storage, for easier future changes

= 0.9 =
* Fixed: bug preventing access to edit posts / pages under some conditions - big thanks, KaiSD

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

= 1.3 =
Bug fix for forum selection and: import existing comments, use your own copies of template files

= 1.2 =
New features! Better translation, XML-RPC support, can copy post tags to topics

= 1.1 =
bbPress 2.1 support

= 1.0 =
Bug fixes for all the new features.  All users should upgrade.

= 0.9.5 =
Just new features. Enjoy!

= 0.9 =
Fixed a bug that prevented post / page editing under some circumstances. All users should upgrade.

= 0.8 =
Prevent topic from being created until post is published

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
