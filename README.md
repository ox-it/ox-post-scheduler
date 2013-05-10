# ox-post-scheduler
## Oxford Digital Signage Post Scheduler
Copyright: [University of Oxford IT Services](http://www.it.ox.ac.uk)  
Contributors: [Guido Klingbeil](http://www.gklingbeil.net), [Marko Jung](http://mjung.net)  
Tags: digital signage, automation, expire, expires, expiring, schedule, scheduling  
Requires at least: 3.0  
Tested up to: 3.5.1    
Stable tag: trunk  
License: GPLv3 or later  
License URI: http://www.gnu.org/licenses/gpl-3.0.html  
GitHub URI: https://github.com/ox-it/ox-post-scheduler

## Short Description 
Enable posts to be automatically enabled and expires at defined points in time.

## Description
Enable posts to be automatically scheduled and expired at defined points in time. When a post is scheduled, its status is changed form 'draft' to 'published'. When its expires it is changed form 'published' to 'draft'.


## Installation

This plugin is not published in the official WordPress plugin catalogue yet. If you wish to manually install it:

1. Download the plugin from [GitHub](https://github.com/ox-it/ox-post-scheduler),
1. Upload the entire `ox-post-scheduler` directory to your plugins folder, 
1. Activate the plugin in your WordPress plugin page,


## Frequently Asked Questions
#### What about this plugin?
This plugin is a simple version of the Simple Expires plugin by Andrea Bersi. As extra functionality we implemented a an enable time for posts
making it a simple post scheduling plugin.

#### It is possible to set the state of post/page at expires?
No! If you need more control use the very best plugin ["Content Scheduler"](http://wordpress.org/extend/plugins/content-scheduler/)

## Changelog

* 0.3
  * bugfix: posts without a start date are published immediately
  * default change: default expiration date is +7 days
* 0.2
  * Various bug fixes.
* 0.1
  * initial fork of Andrea Bersi's Simple Expires plugin version 1.3.
