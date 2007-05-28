=== WP PHPList ===
Tags: highslide, image preview, thumbnail, images
Author:JesseHeap
Donate link: http://projects.jesseheap.com
Requires at least: 2.0.2
Tested up to: 2.2
Stable tag: trunk
Version:1.24



This plugin eases insertion of highslide js thumbnail viewer by inserting the required tags automatically

== Description ==

Highslide JS is a brilliant thumbnail viewer written in javascript. WP-Highslide is a wordpress plugin that 
allows you to easily use Highslide by automatically inserting the necessary javascript into your posts or pages. 
The plugin makes uses of a wordpress quicktag to make the insertion process painless.

Also, for users looking to customize highslide, this plugin gives you the flexibility of editing the javascript 
object settings, setting global options through the options page, or overriding the global options for individual images, all without having dig through the plugin code. 

== Installation ==

THIS PLUGIN REQUIRES Highslide JS from Torstein Hons which is YOU MUST [DOWNLOAD](http://vikjavev.no/highslide/ "DOWNLOAD") SEPERATELY.  If you are running a COMMERCIAL site you must pay for Highslide JS. Note: Installation Instructions also at on [Project Page](http://projects.jesseheap.com/all-projects/wordpress-highslide-js-plugin/)

Note, as of version 1.23, WP-Highslide is compatible up to wordpress version 2.2

   1. Unzip contents of zip file to your plugins folder, usually 'wp-content/plugins/'. It should create a highslide folder underneath the plugins folder.
   2. Download Highslide JS and copy highslide.js to
      the ew highslide folder created under your plugins folder.
   3. Activate the plugin in wordpress under plugins. Look for WP-Highslide.
   4. Check 'WP-Highslide' under options to customize the javascript for highslide
   5. Check Highslide JS site for further customization options

Note: **You MUST [download Highslide JS](http://projects.jesseheap.com/all-projects/wordpress-highslide-js-plugin/) script in order for this plug-in to properly work.**

== Frequently Asked Questions ==

= Where is my highslide quicktag? =
Unfortunately quicktags do not show up in Visual Rich Editor mode. You have a few alternatives. If you are using Wordpress 2.1, simply switch over to code view and the Highslide quicktag will be available. If you are using Wordpress 2.0.x then you can disable Visual Rich Editor mode or you can create the tag yourself by using the special `<highslide>` tag. This tag takes 4 attributes:

**image**
This is should be the path to the image you want displayed when the user clicks on the thumbnail

**thumbnail**
This should be the path to the THUMBNAIL image you want displayed initially.

**altdesc**
This is the copy that will appear in the ALT tag description. Filling this in is good practice for search engines.

**captiontext**
The caption displayed directly under the large image when the user clicks on the thumbnail
Example Code
`<highslide image="/path/to/myimage.jpg" thumbnail="/path/to/thumbnail.jpg" altdesc="" captiontext="" />`

Future versions we'll look to embed a button in the visual rich editor similar to how Viper's Video Quicktags did.

= Do I have to pay for Highslide JS? =

Highslide JS is licensed under a Creative Commons Attribution-NonCommercial 2.5 License. If you are a non-commercial site the script is free. It costs $30 for each commercial domain

= How can I tell if it's working? =

Try creating a test post and check to see if the HIGHSLIDE quicktag is available. Click on the tag and follow the prompts to insert your first image.

= When using the HIGHSLIDE quicktag what do all the prompts mean? =

The quicktag will prompt you for the following information:

Prompt 1: Enter path to large Image
This is should be the path to the image you want displayed when the user clicks on the thumbnail

Example: /images/mylargeimage.jpg

Prompt 2: Enter path to thumbnail image
This should be the path to the THUMBNAIL image you want displayed initially.

Example: /images/mythumbnailimage.jpg

Prompt 3: Enter the Alternate Description of the image
This is the copy that will appear in the ALT tag description. Filling this in is good practice for search engines.

Prompt 4: Enter the Caption for the image
The caption displayed directly under the large image when the user clicks on the thumbnail

= When I look at the generated code it is surrounded by a paragraph tag. How can I remove that paragraph tag? =

This is a "feature" of wordpress. Here is the generated HTML required for highslide. Notice the `<p>` tags that surround it:

`<p><a href="http://www.pinkcakebox.com/images/cake200.jpg" class="highslide"  onclick="return hs.expand(this)">`
`               <…snip…>       `
`</p>`
 

To remove the '<p>' tag open your default-filters.php and comment out the following line:

`add_filter('the_content', 'wpautop');`

Alternatively there are plugins you can use to disable this feature.

= What version of Highslide JS does this plugin support? =

As of March 9th this plugin works with version 3.00 or greater.

= Can I still customize Highslide JS using your plugin? =
The plugin was designed to give a high level of flexibility in customizing Highslide. In the WP-HIGHSLIDE options page there are global settings that allow you to toggle off/on the highslide caption box, close link, or next/previous links:

Additionally, you can override the global settings for individual images by using override flags in the `<highslide> ` tag. For example, to override the Show Caption Box:

`<highslide image="http://www.pinkcakebox.com/images/cake205.jpg" thumbnail="http://www.pinkcakebox.com/images/cake205-circle.jpg" altdesc="" captiontext="null" show_caption="y"  />`
 

Tags that are available

<table style="border:solid 1px gray">
<tr>
<th>Flag</th>
<th>Usage</th>
<th>Description</th>
</tr>
<tr>
<td>
show_caption
</td>
<td>
show_caption=&#8221;y&#8221;<br />

show_caption=&#8221;n&#8221;
</td>
<td>
Toggle on/off Highslide Caption
</td>
</tr>
<tr>
<td>show_close</td>
<td>show_close=&#8221;y&#8221;<br />
show_close=&#8221;n&#8221;</td>

<td>Toggle on/off Highslide Close Link</td>
</tr>
<tr>
<td>show_prv_next</td>
<td>show_prv_next=&#8221;y&#8221;<br />
show_prv_next=&#8221;n&#8221;
</td>
<td> Toggle on/off Highslide Next/Previous Links</td>
</tr>

</table>

== Demo ==
See an example of this plugin  in this [Wedding Picture Gallery](http://blog.pinkcakebox.com/category/pastry-images/wedding-cakes/ "Wedding Gallery")
