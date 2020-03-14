=== BuddyPress Registration Options ===
Contributors: webdevstudios, pluginize, tw2113, Messenlehner
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3084056
Tags: buddypress, plugin, admin, moderation, registration, groups, blogs, new members, buddypress private network, buddypress spam
Requires at least: 3.5
Tested up to: 5.4.0
Stable tag: 4.3.9
Requires PHP: 5.2
License: GPLv2

Moderate new BuddyPress members and fight BuddyPress spam.

== Description ==

Prevent users and bots from accessing the BuddyPress or bbPress areas of your website(s) until they are approved.

This BuddyPress extension allows you to enable user moderation for new members, as well as help create a private network for your users. If moderation is enabled, any new members will be denied access to your BuddyPress and bbPress areas on your site, with the exception of their own user profile. They will be allowed to edit and configure that much. They will also not be listed in the members lists on the frontend until approved. Custom messages are available so you can tailor them to the tone of your website and community. When an admin approves or denies a user, email notifications will be sent to let them know of the decision.

Requires BuddyPress version 1.7 or higher and bbPress 2.0 or higher.

=== General Data Protection Regulation ===
BuddyPress Registration Options temporarily stores user IP addresses as user meta to help validate and vet pending users. Saved IP values are deleted upon both approval and denial of pending user. No other personal data is recorded.

=== Development ===

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

= 4.3.9 - 2020-03-14 =
* Fixed: Fatal error from a function typo.

= 4.3.8 - 2020-03-13 =
* Updated: Extra protection for users who may be using bbPress only with no BuddyPress.

= 4.3.7 =
* Fixed: Prevent overwriting of existing user IDs to exclude, if any are already set.
* Fixed: Remove duplicate status setting upon uster registration.
* Fixed: Prevent PHP notices for user notification content on frontend.

= 4.3.6 =
* Fixed: GeoIP lookup resource change.

= 4.3.5 =
* Added: Setting tool to help aid with more GDPR compliance. Setting queries for all previously-approved users that still have IP address user meta data saved, and removes that meta data. Should not need to be used again once all IP meta is removed.
* Fixed: Addressed issue regarding notifications sent out when a new user registers. 4.3.4 introduced a filter to customize who gets notified, but the filter needed to be added in some more places.

= 4.3.4 =
* Fixed: Default message values not persisting across many users when approving or denying in bulk.
* Added: IP Address removal from user meta after approved. Addresses possible issues with GDPR compliance. Denied users do not have saved data after denied.
* Added: Filter for who receives notifications for new users. Props to @cherbst
* Added: Parse "[username]" shortcode for the Activate/Profile message text. Props richardfoley on WordPress.org

= 4.3.3 =
* Fixed: support for [user_email] shortcode parsing in the approved/denied custom messages.

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

== Upgrade Notice ==

= 4.3.9 - 2020-03-14 =
* Fixed: Fatal error from a function typo.

= 4.3.8 - 2020-03-13 =
* Updated: Extra protection for users who may be using bbPress only with no BuddyPress.

= 4.3.7 =
* Fixed: Prevent overwriting of existing user IDs to exclude, if any are already set.
* Fixed: Remove duplicate status setting upon uster registration.
* Fixed: Prevent PHP notices for user notification content on frontend.

= 4.3.6 =
* Fixed: GeoIP lookup resource change.

= 4.3.5 =
* Added: Setting tool to help aid with more GDPR compliance. Setting queries for all previously-approved users that still have IP address user meta data saved, and removes that meta data. Should not need to be used again once all IP meta is removed.
* Fixed: Addressed issue regarding notifications sent out when a new user registers. 4.3.4 introduced a filter to customize who gets notified, but the filter needed to be added in some more places.

= 4.3.4 =
* Fixed: Default message values not persisting across many users when approving or denying in bulk.
* Added: IP Address removal from user meta after approved. Addresses possible issues with GDPR compliance. Denied users do not have saved data after denied.
* Added: Filter for who receives notifications for new users. Props to @cherbst
* Added: Parse "[username]" shortcode for the Activate/Profile message text. Props richardfoley on WordPress.org

= 4.3.3 =
* Fixed: support for [user_email] shortcode parsing in the approved/denied custom messages.

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
