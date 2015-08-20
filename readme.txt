=== bbPress Votes ===
Contributors:grosbouff
Donate link:http://bit.ly/gbreant
Tags: bbPress, vote, votes, rate, rating, ratings, BuddyPress
Requires at least: 4.1.1
Tested up to: 4.3
Stable tag: trunk
License: GPLv2 or later

Allows logged users to vote up or down to topics and replies inside bbPress, just like you can on StackOverflow for example.

== Description ==

Allows logged users to vote up or down to topics and replies inside bbPress, just like you can on StackOverflow for example.

*   Ajaxed
*   Compatible with BuddyPress
*   Hooks and filters to extend the plugin
*   Votes log with users icons
*   Allow to filter a query to sort posts by votes, see FAQ.

= Demo =
See it in action [here](http://www.pencil2d.org/?post_type=forum).

= Contributors =
[Contributors are listed
here](https://github.com/gordielachance/bbpress-votes/contributors)
= Notes =

For feature request and bug reports, [please use the
forums](http://wordpress.org/support/plugin/bbpress-votes#postform).

If you are a plugin developer, [we would like to hear from
you](https://github.com/gordielachance/bbpress-votes). Any contribution would be
very welcome.

== Installation ==

Upload the plugin to your blog and Activate it.

== Frequently Asked Questions ==

= I can’t see the vote links =

Users cannot vote for themselves.  If you are the author of a topic or reply, the vote links won’t be available; the score only will be shown.

= Can I filter the query to sort posts by votes ? =

Yes, you can sort the posts by score or votes count, using the query variable 'vote_sort'.
Allowed values are 'score_desc', 'score_asc', 'count_desc', 'count_asc'.

Example of a [query](https://codex.wordpress.org/Class_Reference/WP_Query) that will fetch the 5 last topics, ordered by score (desc) : 

`<?php
$best_rated_topics_args = array(
  'post_type'       => bbp_topic_post_type(), //or 'topic'
  'posts_per_page'  => 5,
  'vote_sort'       => 'score_desc' //plugin
);

$best_rated_topics = new WP_Query( $best_rated_topics_args );
?>`

See function sort_by_votes() for more details.

= How can I translate this plugin in my language ? =

You can help translate the plugin on [oneskyapp](http://osjxryl.oneskyapp.com/admin/project/dashboard/project/63465) !

= How can I customize the look of the vote links ? =

The best way to customize the links is to setup some CSS rules in your theme.
Check [this example on CodePen](http://codepen.io/anon/pen/KpwrMp) to see how to have images displayed instead of text.

If you need more complex customization, you can filter the links using those hooks :

*   bbpvotes_get_vote_up_link
*   bbpvotes_get_vote_down_link
*   bbpvotes_get_vote_score_link

== Screenshots ==

1. A single reply with score, vote up and vote down links (top) and vote log (after reply content)

== Changelog ==

= 1.0.7 =
* new functions to get votes count by user : bbpvotes_get_votes_down_by_user_count(), bbpvotes_get_votes_up_by_user_count(), bbpvotes_get_votes_total_by_user_count()

= 1.0.6 =
* Added two meta keys : 'bbpvotes_vote_score' (total score) and 'bbpvotes_vote_count' (total votes).
* Filter query to sort items by score or votes count.

= 1.0.5 =
* Fixed crash when BuddyPress is not installed

= 1.0.4 =
* Append votes log with ajax when user has voted
* French translation
* Added pot files for translations

= 1.0.3 =
* Replaced ajaxurl with bbpvotesL10n.ajaxurl in bbpvotes.js

= 1.0.2 =
* Fixed $user_vote_link link in bbpvotes_get_post_votes_log()
* Fixed ‘bbpvotes-post-no-score’ class in bbpvotes_get_score_link()

= 1.0.1 =
* Minor fixes

= 1.0 =
* First release

== Upgrade Notice ==

== Localization ==
