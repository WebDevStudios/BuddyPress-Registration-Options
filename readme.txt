=== BuddyPress Registration Options ===

Contributors: webdevstudios, Messenlehner, jibbius, tw2113
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3084056
Tags: buddypress, wpmu, wpms, plugin, admin, moderation, registration, groups, blogs, new members, buddypress private network, buddypress spam
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 4.1
License: GPLv2
Moderate new BuddyPress members and fight BuddyPress spam.

== Description ==

Great plugin for stopping spam bots from registering on your BuddyPress website!


This WordPress BuddyPress plugin that allows for new member moderation, if moderation is turned on from the admin settings page, any new members will be blocked from interacting with any buddypress elements (except editing their own profile and uploading their avatar) and will not be listed in any directory until an administrator approves or denies their account. If moderation is turned on admins can create custom display messages and email alert messages for approved or denied accounts. When admin approves or denies, custom emails get sent out to new members telling them they were approved or denied.

Presently does not block bbPress. Tentatively will be the main enhancement for version 4.2.

== Screenshots ==

1. General Settings page:

2. New member registration:

3. New members can login but can only see/edit their profile and change their avatar. If an unapproved new member tries to go to any other BuddyPress pages they are redirected back to their profile page.

4. Admin receives email notice of new member:

5. Admin Dashboard Alert:

6. Approve, deny or ban new members:



== Changelog ==

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

3. Click on the "BP Registation Options" link under Settings.

4. Configure your BuddyPress registration options.

== Frequently Asked Questions ==


= Does this plugin work with WordPress Multi-Site? =

Absolutely!  This plugin has been tested and verified to work on the most current version of WordPress with Multi-Site or with stand alone WordPress.