=== BuddyPress Registration Options ===

Contributors: webdevstudios, pluginize, tw2113, Messenlehner
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3084056
Tags: buddypress, plugin, admin, moderation, registration, groups, blogs, new members, buddypress private network, buddypress spam
Requires at least: 3.5
Tested up to: 4.7.3
Stable tag: 4.3.2
License: GPLv2
Moderate new BuddyPress members and fight BuddyPress spam.

== Description ==

Prevent users and bots from accessing the BuddyPress or bbPress areas of your website(s) until they are approved.

This BuddyPress extension allows you to enable user moderation for new members, as well as help create a private network for your users. If moderation is enabled, any new members will be denied access to your BuddyPress and bbPress areas on your site, with the exception of their own user profile. They will be allowed to edit and configure that much. They will also not be listed in the members lists on the frontend until approved. Custom messages are available so you can tailor them to the tone of your website and community. When an admin approves or denies a user, email notifications will be sent to let them know of the decision.

Requires BuddyPress version 1.7 or higher and bbPress 2.0 or higher.

Follow along with development on GitHub at [BuddyPress-Registration-Options](https://github.com/WebDevStudios/BuddyPress-Registration-Options)

[Pluginize](https://pluginize.com/?utm_source=buddypress-registration-op&utm_medium=text&utm_campaign=wporg) was launched in 2016 by [WebDevStudios](https://webdevstudios.com/) to promote, support, and house all of their [WordPress products](https://pluginize.com/shop/?utm_source=buddypress-registration-op&utm_medium=text&utm_campaign=wporg). Pluginize is dedicated to [creating products that make your BuddyPress site](https://pluginize.com/product-category/buddypress/) easy to manage, without having to touch a line of code. Pluginize also provides ongoing support and development for WordPress community favorites like [CPTUI](https://wordpress.org/plugins/custom-post-type-ui/), [CMB2](https://wordpress.org/plugins/cmb2/), and more.

== Screenshots ==

1. General Settings page:
2. New member registration:
3. New members can login but can only see/edit their profile and change their avatar. If an unapproved new member tries to go to any other BuddyPress pages they are redirected back to their profile page.
4. Admin receives email notice of new member:
5. Admin Dashboard Alert:
6. Approve, deny or ban new members:

== Changelog ==

= 4.3.2 =
* Fixed: Re-hide unimplemented ban button. Hopefully someday.
* Fixed: Prevent possible fatal errors for non-existant functions at runtime.

= 4.3.1 =
* Fixed: Compatibility issues with bbPress and blank notifications.

= 4.3.0 =
* Added: Support for BuddyPress notifications of new user, checkbox setting to enable or disable it.
* Added: BPRO menu items to BuddyPress Member Admin Bar for administrators.
* Added: Prevention of activity stream posting about new member until member is approved.
* Added: Email notification and default message setting for pending users upon activation.
* Added: Before and after save hooks for general settings.
* Added: Filter in pending member "additional data" section for displaying custom data about user.
* Added: Filter in wp_redirect urls for non-logged in users.
* Added: Filter on IP address before saving to user meta.
* Added: Filter in "allowed areas" functions for custom area setting for 3rd party developers.
* Fixed: Issues with HTML emails and HTML in available custom message fields.
* Fixed: Changed API used for geo lookup in moderated user table.
* Fixed: Hardened user display in pending member list if no Full Name value provided.
* Fixed: Mark user as not spam, upon approval, in BuddyPress core's user management page.
* Fixed: Prevention of working in non-main site sites for Multisite.
* Fixed: Internationalization issues with singular/plural "members" admin notice.
* Updated: Internationalization strings for default messages.

= 4.2.12 =
* Switch Geolocation provider to one still actively maintained.

= 4.2.11 =
* Fix issue with 404 errors in pending user list.

= 4.2.10 =
* WordPress 4.4 compatibility bump. No other code changes made.

= 4.2.9 =
* Add Hebrew translation.

= 4.2.8 =
* Rework loader file to better work with translating the plugin.

= 4.2.7 =
* Fixed issue with widget listing prevention overwriting other potential existing values.
* Update copy in readme and plugin headings.

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

== Upgrade Notice ==

= 4.3.2 =
* Fixed: Re-hide unimplemented ban button. Hopefully someday.
* Fixed: Prevent possible fatal errors for non-existant functions at runtime.

= 4.3.1 =
* Fixed: Compatibility issues with bbPress and blank notifications.

= 4.3.0 =
* Added: Support for BuddyPress notifications of new user, checkbox setting to enable or disable it.
* Added: BPRO menu items to BuddyPress Member Admin Bar for administrators.
* Added: prevention of activity stream posting about new member until member is approved.
* Added: Email notification and default message setting for pending users upon activation.
* Added: Before and after save hooks for general settings.
* Added: Filter in pending member "additional data" section for displaying custom data about user.
* Added: Filter in wp_redirect urls for non-logged in users.
* Added: Filter on IP address before saving to user meta.
* Added: Filter in "allowed areas" functions for custom area setting for 3rd party developers.
* Fixed: Issues with HTML emails and HTML in available custom message fields.
* Fixed: Changed API used for geo lookup in moderated user table.
* Fixed: Hardened user display in pending member list if no Full Name value provided.
* Fixed: Mark user as not spam, upon approval, in BuddyPress core's user management page.
* Fixed: Prevention of working in non-main site sites for Multisite.
* Fixed: Internationalization issues with singular/plural "members" admin notice.
* Updated: Internationalization strings for default messages.

= 4.2.12 =
* Switch Geolocation provider to one still actively maintained.

= 4.2.11 =
* Fix issue with 404 errors in pending user list.

= 4.2.10 =
* WordPress 4.4 compatibility bump. No other code changes made.

= 4.2.9 =
* Add Hebrew translation.

= 4.2.8 =
* Rework loader file to better work with translating the plugin.

= 4.2.7 =
* Fixed issue with widget listing prevention overwriting other potential existing values.
* Update copy in readme and plugin headings.

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

== Installation ==


1. Upload the bp-registration-options folder to the plugins directory in your WPMU installation

2. Activate the plugin

3. Click on the "BP Registation" link in your admin menu.

4. Configure your BuddyPress registration options.

== Frequently Asked Questions ==

1. What does this plugin do?

We do our best to prevent unmoderated users from being able to interact or even access the social network areas of your website until approved. This includes BuddyPress and evolving support for bbPress as well. The plugin has evolved since its original origins, so admittedly the "registration options" part has changed. It focuses on moderation.

2. What does this plugin not do?

We do not prevent overall spam from getting through. We also don't prevent the activation emails from going out for the user. Even on a private network, the user would be able to activate the user account and log in and see at least their own profile. When just "Moderate New Members" is checked, they will be able to browse around and see a general picture, but they won't be able to interact. If you believe you have found a bug or "hole" with this, please start a support thread at https://wordpress.org/support/plugin/bp-registration-options and we will respond there.

3. Does it offer support for bbPress?

bbPress support is a work in progress. As we continue to develop it, we will do what we can to help prevent bbPress forum acces, but at the moment it is not as comprehensively covered as BuddyPress.

4. Does it account for multisite super admins being the only ones able to edit users?

A work in progress. We are aware of the limitation, and are trying to find the best solution around this issue, especially for anyone who isn't a super admin in a Multisite network, but still wants social network functionality for their site within the network.

5. Is this a good plugin to help prevent spam coming into my whole site?

If you are looking for sitewide spam prevention, we will not be the right plugin for you. Our plugin will only cover the BuddyPress and bbPress areas. Regular WordPress pages or the blog would still be able to be accessed.
