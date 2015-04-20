=== BuddyPress Registration Options ===

Contributors: webdevstudios, Messenlehner, tw2113
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3084056
Tags: buddypress, plugin, admin, moderation, registration, groups, blogs, new members, buddypress private network, buddypress spam
Requires at least: 3.5
Tested up to: 3.9
Stable tag: 4.2.6
License: GPLv2
Moderate new BuddyPress members and fight BuddyPress spam.

== Description ==

Prevent users and bots from accessing your BuddyPress or bbPress sites until they are approved.

This BuddyPress extension allows you to enable user moderation for new members, as well as help create a private network for your users. If moderation is enabled, any new members will be denied access to your BuddyPress and bbPress areas on your site, with the exception of their own user profile. They will be allowed to edit and configure that much. They will also not be listed in the members lists on the frontend until approved. Custom messages are available so you can tailor them to the tone of your website and community. When an admin approves or denies a user, email notifications will be sent to let them know of the decision.

Requires BuddyPress version 1.7 or higher and bbPress 2.0 or higher.

Follow along with development on GitHub at [BuddyPress-Registration-Options](https://github.com/WebDevStudios/BuddyPress-Registration-Options)

== Screenshots ==

1. General Settings page:

2. New member registration:

3. New members can login but can only see/edit their profile and change their avatar. If an unapproved new member tries to go to any other BuddyPress pages they are redirected back to their profile page.

4. Admin receives email notice of new member:

5. Admin Dashboard Alert:

6. Approve, deny or ban new members:

== Changelog ==

= 4.2.6 =
* Preventive measures for potential XSS security issues with add_query_arg()
* Early attempts to prevent ajax refresh appropriately.

= 4.2.5 =
* Added more hooks to remove UI elements for moderated members.
* Fixed logic bug that was returning too early in UI hiding if "private network" wasn't checked.
* Fixed missing message for new users due to changes in BuddyPress registration URLs.
* Added support for BuddyPress Like and BuddyPress Send Invites plugins.
* Added transient clearing for pending member count upon user deletion.
* Known issue: UI elements returning in ajax-refreshed tabs

= 4.2.4 =
* Remove filter on total user count added in 4.2.3.

= 4.2.3 =
* Deny access to Compose submenu item for moderated members.
* Subtract moderated member count from total user count.

= 4.2.2 =
* Changed hooks for when setting "moderated" status. Hoping this one catches all incoming, spam or human.
* Removed some checks that were producing unintended positives for bbPress.

= 4.2.1 =
* Fix with moderation coming through inaccurately. Sorry about that everyone.
* Clear pending count transient on new user registration.
* Attempt to prevent duplicate users from showing in pending list.

= 4.2.0 =
* Rewrote most aspects of plugin, with focus on preventing access to the community areas.
* Added bbPress support so you can deny users from accessing forums.

= 4.1.3 =
* Changed the hook that the load_plugin_textdomain loads on. Courtesy of http://geertdedeckere.be/article/loading-wordpress-language-files-the-right-way

= 4.1.2 =
* properly hook our textdomain method, so that the plugin can be properly translated.
* refreshed the pot file with extra localization functions.

= 4.1.1 =
* fix spacing issues with admin email notice
* rename pot file to hopefully help with translators.

= 4.1 =

* Added hiding of pending members from the members list on the frontend, until approved.
* Updated UI to match latest WordPress visual style
* Fixed issue with where we were trying to grab user data after the user was deleted.
* Now translation ready
* Accessibility updates
* Sanitize inputs to help with security.
* Code cleanup
* Screenshot updates.

= 4.0.1 =

* Fixes for WP 3.5. Thanks jibbius!

= 4.0.0 =

* Revamp of entire plugin. Stripped out features for joining particular groups at registration so you may not want to upgrade if you are dependent on these features.

= 3.0.3 =

* Added country flag and data driven from IP address on moderation admin page to quickly show where a requested member is from. Thanks for the idea Steve Bruner!

= 3.0.2 =

* Any registered members that never activated their account will be deleted after 7 days.

= 3.0.1 =

* When a member is denied and they joined any groups at registration any records relating them to groups will be deleted.

= 3.0 =

* Updated plugin to work with WP 3.0
* Updated plugin to work with WP Multi-Site
* Hide "Person became a registered member" from activity stream if moderation is turned on until person is approved.
* Added ability to make website a private network where only registered members can view any BuddyPress pages. Certain page exceptions can be made. Visitors are redirected to the registration page.

= 1.2 =

* Updated plugin to work with BuddyPress 1.2

= 1.0 =

* First official release


== Upgrade Notice ==

= 4.2.6 =
* Preventive measures for potential XSS security issues with add_query_arg()
* Early attempts to prevent ajax refresh appropriately.

= 4.2.5 =
* Added more hooks to remove UI elements for moderated members.
* Fixed logic bug that was returning too early in UI hiding if "private network" wasn't checked.
* Fixed missing message for new users due to changes in BuddyPress registration URLs.
* Added support for BuddyPress Like and BuddyPress Send Invites plugins.
* Added transient clearing for pending member count upon user deletion.
* Known issue: UI elements returning in ajax-refreshed tabs

= 4.2.4 =
* Remove filter on total user count added in 4.2.3. Filter was only displaying active users, not ALL members.

= 4.2.3 =
* Deny access to Compose submenu item for moderated members.
* Subtract moderated member count from total user count.

= 4.2.2 =
* Changed hooks for when setting "moderated" status. Hoping this one catches all incoming, spam or human.
* Removed some checks that were producing unintended positives for bbPress.

= 4.2.1 =
* Fix with moderation coming through inaccurately. Sorry about that everyone.
* Clear pending count transient on new user registration.
* Attempt to prevent duplicate users from showing in pending list.

= 4.2.0 =
* Rewrote most aspects of plugin, with focus on preventing access to the community areas.
* Added bbPress support so you can deny users from accessing forums.

= 4.1.2 =
* Update if you need to translate the plugin.

= 4.1.1 =
* fix spacing issues with admin email notice
* rename pot file to hopefully help with translators.

= 4.1 =

* Added hiding of pending members from the members list on the frontend, until approved.
* Updated UI to match latest WordPress visual style
* Fixed issue with where we were trying to grab user data after the user was deleted.
* Accessibility updates
* Now translation ready


= 4.0.0 =

* Revamp of entire plugin. Stripped out features for joining particular groups at registration so you may not want to upgrade if you are dependent on these features.


== Installation ==


1. Upload the bp-registration-options folder to the plugins directory in your WPMU installation

2. Activate the plugin

3. Click on the "BP Registation" link in your admin menu.

4. Configure your BuddyPress registration options.

== Frequently Asked Questions ==
