=== Kiva.org Loans Widget ===
Contributors: road2nowhere
Donate link: http://urpisdream.com/2009/05/kiva-loans-wordpress-widget/
Tags: kiva, widgit
Requires at least: 2.7
Tested up to: 2.7
Stable tag: 2.0

Displays rotating entrepreneurs the blogger has invested in publically. Links back to Kiva.

== Description ==

Kiva Loans sidebar widget allows the user to display their public loans from Kiva.or on their 
blog. The user specifies their Kiva lender name, the number of loans (N) to show, and the 
size of the images that will be displayed. The widget then randomly selects N of the user.s 
loans from the Kiva API, and displays brief information about each of them. Links to the 
Kiva.org website

If you have marked any of your Kiva loans as private, or have lent anonymously, your loans 
will not show up in the Kiva Loans Widget. Only public loans are available to the Kiva.org 
Loans Widget.

Important: This Kiva Widget requires your Kiva Lender Name. This is not your email that you 
use to login to Kiva.org! Your Kiva Lender Name is the name Kiva uses in the URL for your 
Kiva Lender Page. You can access and change your Kiva Lender Name via the last item on .My 
Lender Page. under .My Portfolio. on Kiva.org.

== Installation ==

How to install the Kiva Lendees plugin:

1. Download the zip file from accessible from the Wordpress Plugins space
2. Upload and unzip into the plugins directory ( /wp-content/plugins/ ) on your server
3. Activate the plugin through the .Plugins. menu in WordPress
3. Add the widget to a sidebar via the .Widgets. page under the .Appearance. menu
4. Set the options, and save your settings

= Options: =

Kiva Lender Name: Your Kiva Lender Name, from "My Lender Page URL" on Kiva

Number of loans: The number you Kiva loans you would like to be displayed

Image Size: The maximum width, or height (which ever is larger) of the image in pixels

== Frequently Asked Questions ==

What is Kiva.org?

A micro-lending site applications. http://kiva.org

Will it show private loans?

No, it will only show your public Kiva loans.

How do I change the look of the Kiva Loans Widget?

The style sheet is in the plugin folder at /wp-content/plugins/kiva-widget/style.css

What if I get the json_decode error?

If you get the following error:

Fatal error: Call to undefined function: json_decode() in 
wp-content/plugins/kivaorg-widget/kiva.php

This error means that you do not have PHP JSON.s support installed on your server. JSON 
support became a part of the PHP core in PHP 5.2.0. The best way to handle this error is to 
upgrade your PHP to the newest stable available version. Most Linux distributions will have 
packages for upgrading. On Windows, you can go to the PHP Downloads page. For more information, 
check out the PHP JSON Manual.

What if I get the Permission Denied error

Warning: fopen( wp-content/plugins/kivaorg-widget/cache/kiva_cache_1241553368.txt) 
[function.fopen]: failed to open stream: Permission denied

This error means that improper permissions are set within the Kiva widget directory on your 
server. The directory .cache. within the widget must be writable by your server.s web users. 
You can fix this problem by changing the ownership of the directory .cache. to the web user. 
Most likely one of the following command will work, if performed within the plugin directory 
( wp-content/plugins ):

chown www-data:www-data kivaorg-widget/cache

or

chmod 777 /kivaorg-widget/cache

== Screenshots ==

1. The widget displayed on the side bar

2. The widget's admin control

