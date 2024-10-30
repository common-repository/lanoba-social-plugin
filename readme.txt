=== Lanoba Social Plugin ===
Contributors: Lanoba
Donate link: http://www.lanoba.com
Tags: wordpress,lanoba, plugin, social, networks, user, authentication, registration, share, facebook, twitter, google, yahoo, msn, widget, login, oauth
Requires at least: 3.1.0
Tested up to: 3.2.1
Stable tag: 1.2

This plugin adds user authentication and content sharing capabilities through social networks like facebook, twitter and more.

== Description ==

**Authentication**
 
Lanoba provides easy registration for users by giving them the option to log into your website via their existing social network accounts like Facebook and Twitter, among others. With fewer passwords and logins to remember, their browsing experience is positive and their registration simple.
 
In turn, Lanoba helps you collect the user data already available via these social networking sites, so that you can build a better relationship with your clients, better target your marketing strategies, and encourage your clients to share information about your business through their networks and friends.
 
**Sharing**
 
With Lanoba, a user can easily share their activity or their thoughts and likes with a simple click of a button, via:
 
Share widgets: 
-Sharing made easy with a simple JavaScript interface
-Bit.ly link shortening: To optimize postings to network sites
 
With Lanoba, you have access to many features, which are always being updated:
-Multiple network sharing: Simultaneous sharing for up to seven different social networks
-Action-based triggering: To spread a targeted message
-Analytic tracking: Measuring traffic referred to your site
-Custom API: personalize these features to fit the look and feel of your website
 
**Analytics**
 
Lanoba helps you organize and decipher a multitude of user statistics so that you can access clear and easy to understand demographic information on all of your clients. 

= More Information: =

http://www.lanoba.com/docs

== Installation ==

Requires PHP 5.2 or newer with JSON support, php cURL and Wordpress 3.1

(This plugin is totally new, please thoroughly test it before deployment on an active site.)

**Note**
This plugin requires an API secret key to function. This key can be obtained by registering to the Lanoba website at http://www.lanoba.com/free-trial. 

For websites with less than 250 unique users per month, the complete service is free. 

After registration, you can set up your keys for each social network you want to use for authentication. The documentation provided on the website shows detailed simple steps on how to do that.

1. Register to the Lanoba website and setup the keys for the social networks to be used. Obtain the API secret key.

2. Copy the lanoba directory and its contents to your `/wp-content/plugins/` directory, or use the Wordpress's plugin installation tool in the Plugins section.

3. Activate the Lanoba Social Plugin through the 'Plugins' menu in WordPress

4. You will need to allow user registrations for the login page to appear. Go to General Settings and check "Membership,  Anyone can register."

5. Go to the Lanoba Social Plugin configuration page at 'Settings' -> 'Lanoba Social Plugin' and enter the required settings (API secret, social domain, email, user auto-registration)

== Frequently Asked Questions ==

= How do I enable social network authentication? =

Please check our complete documentation at http://www.lanoba.com/docs on how to apply for a Lanoba account and set the required access keys for each social network.

== Changelog ==

= Version 1.2 =
* increased robustness to system related failures
* modified the position of the sharing button
* fixed a bug related to sending premature headers

= Version 1.1 =
*  increased security.
*  fixed sharing bug that might affect several themes.
*  optimized code

= Version 1.0 =
*  This is the initial release of the plugin for Wordpress 3.

== Upgrade Notice ==

Release 1.2 adds robustness against system related failures. Please upgrade immediately!

== Screenshots ==

1. The Lanoba Social Plugin widget on the login/registration page.
2. The content sharing button.
3. Settings page.

