=== Plugin Name ===
Contributors: rjksn
Tags: Facebook, gallery, graph, Facebook album, Facebook gallery, Facebook photos, Facebook images
Requires at least: 3.3.2
Tested up to: 3.5.1
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin integrates Facebook albums into WordPress with an easy to use editor popup.

== Description ==

This plugin integrates Facebook albums into WordPress with an easy to use editor popup.

Personally I like to make things easy. Especially things that are boring. So with that in mind I needed an easy way for clients to add their own galleries to WordPress, and I find the Media Library lacking. Since most companies have Facebook these days--and I work at a social media company--I decided to integrate Facebook albums into WordPress. And I wanted a really easy way for clients to be able to do this themselves. So I can continue to build things.

That being said, this is currently a work in progress. It's a work in progress that I'm using on client sites, but its still not perfect. I'll be adding more to make it so as the days/weeks/months/years/worlds go on.

**Current Capabilities:**

* Shortcodes
* Editor popup panel (to make it easier for clients)
* Album caching to improve speed

**Planned Updates**

* Added easy through Facebook user integration. This would allow users to connect their Facebook accounts, and find pages that they currently manage, and the galleries associated with them.
* Options panels to allow for the editing of the presentation and usability of the plugin.

Contact me for other things that would make it better, and maybe I can squeeze some work time into updating the plugin to add those. I really don't want it to be huge and bloated (but it might get there).

== Installation ==

Add this plugin through the built in WordPress plugin interface.

== Frequently Asked Questions ==

= Another one? =

Yes

= Really? =

Yeah, I didn't like the other ones. Is this one perfect? No. But I've tried to keep the markup clean so that others can skin it, and use it for themselves. Well, not really; I've kept it clean and made it so that I can use it on multiple different sites and match it to the themes we've created for clients. Is it ideal? Not yet.

= Shortcodes =

[fbvinyl id=161300770550609 title={nothing | h3 | h2 | h1 | span} desc={nothing | h3 | h2 | h1 | span} limit={numerical}]

[fbvinyl id=161300770550609] (for default titles or descriptions)

[fbvinyl id=161300770550609 title= desc=] (for no titles or descriptions)

== Screenshots ==

1. Look a editor window popup.

== Changelog ==

= 1.0.0 =
* Please don't let this break everything.
* Added some options.
* Migrated to WP_HTTP as opposed to cURL.
* Placed some error checking on the basic requirements for the plugin to work.


= 0.2.3 =
* Fixed "rel" issue.
* Fixed Typos

= 0.2.1 =
* Added a limit option for users who were trying to add larger galleries.

= 0.2.0 =
* Fixing the huge error, and adding caching to the app.

= 0.1.4 =
* Adding 1 HUGE error

= 0.1.3 =
* Bug fixes

= 0.1.2 =
* Bug fixes

= 0.1.1 =
* Removed "Enable Ajax" checkbox… which did nothing.

= 0.1.0 =
* It's new. This is it so far.

== Upgrade Notice ==

= 0.2.0 =
* Well I broke the 0.1.# line completely. So this is a working version with caching. On one of the pages I'm using this on there's a HUGE increase in load time.

= 0.1.0 =
* Well. Otherwise you won't have it installed. 
