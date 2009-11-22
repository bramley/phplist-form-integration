=== WP PHPList ===
Tags: phplist, integration, email newsletter
Author:JesseHeap
Donate link: http://projects.jesseheap.com
Requires at least: 2.0.2
Tested up to: 2.9
Stable tag: trunk
Version:1.7

== Description ==

This lightweight plugin for Wordpress 2.0 or greater gives you the ability to easily allow users to subscribe
to your newsletter or RSS blog feed from any page on your blog. Simply install the plug-in, configure your settings,
and embed the comment `<!--phplist form-->` on any page on your blog. It's easy and fast and does not require any
further template modifications in PHPList or Wordpress.

== Installation ==

*Pre-requisites*
Wordpress 2.x
Phplist 2.10.2 or greater. (Download at www.phplist.com)
PHP 4.x or 5.x with cURL support 

*Quick Start Instructions*
Download plugin installation and upload phplist.php and phplist.css to your plug-ins folder, usually /wp-content/plugins
Login to wordpress administration panel and activate the plug-in (Under plug-ins). Plugin name is phplist.
Navigate to Options -> PHPList and fill in the General Settings configuration as instructed. (Optionally, you can configure the form settings as well - see detailed instructions for information)
The last step is to setup the form. If you are adding the subscriber form directly to any post or wordpress page then just add the following comment while inside the wordpress editor:

`<!--phplist form-->`
For more advanced users, who are comfortable editing template files, the form can also be added to any template file. For example most users like to display the subscriber form in their sidebar.php. To do this open up the template page (i.e. sidebar.php) and add the following code:

`<?php
$content = apply_filters('the_content', '<!--phplist form-->');
echo $content;?> `

Detailed instructions available [here](http://projects.jesseheap.com/all-projects/wordpress-plugin-phplist-form-integration/#3)

== Screenshots ==

1. Default subscriber form for PHPList as seen within wordpress blog
2. Code required to display subsriber form
== Demo ==
See an example of this plugin at [Wedding Cake Newsletter Demo](http://blog.pinkcakebox.com/wordpress-plugin-demo/ "Wedding Gallery")