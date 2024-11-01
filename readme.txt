=== WP Property Feed Connector for Residence Theme ===
Contributors: ultimatewebuk
Tags: Vebra, Alto, Vebra, Vebralive, LetMC, Real Estate, Estate Agent, BLM, Residence, WP Residence, Annapx, Property, Properties, Rightmove, Zoopla, Theme
Plugin URI: http://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=Residence
Requires at least: 3.5
Tested up to: 6.3
Requires PHP: 5.4
Stable tag: 1.30
License: GPL2

Automatically feeds Alto, Jupix, Vebra, LetMC or BLM (Rightmove) property details into the popular WP Residence real estate theme. Requires the WP Property Feed plugin.


== Description ==
# WP Property Feed Connector for WP Residence Theme
Automatically feed Alto, Jupix, Vebra, LetMC or BLM (Rightmove) property details into the popular WP Residence real estate theme. This is a zero-maintenance plugin that means estate agents can avoid having to re-enter property details from their back office software into their WordPress website. Requires the [WP Property Feed plugin](https://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=Residence).  If you�re using a different theme to WP Residence, our [WP Property Feed plugin](https://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=Residence) can be customised to automatically feed searchable property details with any WP theme.

## Requirements

This plugin requires;
  - The [WP Property Feed plugin](https://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=Residence)
  - The [Residence Theme](https://themeforest.net/item/wp-residence-real-estate-wordpress-theme/7896392)

== Installation ==
Once you have installed and set up your WP Residence theme and the WP Property Feed Plugin you simply install this connector plugin and the rest is automatic.  You can download and install this plugin using the built in WordPress plugin installer in the wp-admin, just search for \"WP Property Feed Residence\" and install the plugin then \"Activate\" to make it active.  Once active the connector will automatically update the WP Residence properties each time the WP Property Feed plugin updates from the feeds (normally every quarter hour).  In the settings for WP Property Feed you will see a new WP Residence tab.  The tab will show the last 10 automatic updates that were performed and has a checkbox to allow you to run the connector immediately.
It is advised that you set a long time out (max_execution_time) in your php.ini file as feed downloads can take a long time.

== Screenshots ==
1. Settings screen. Shows log of updates

== Changelog ==
* First version released 4th July 2018
* 1.2 Updated description tag to close p correctly
* 1.3 Added default design template
* 1.4 Fixed tab and accordian layout issue
* 1.5 Added in web status and other minor fixes
* 1.6 Fixed update frequency issue
* 1.7 Update to image updating to prevent duplicates
* 1.8 Fixed issue with thumbnails
* 1.9 By default us the address_custom (Vebra) or Area (LetMC) as the Neighborhood
* 1.10 Put in check to make sure images are displayed in correct sort order
* 1.11 Added options for filtering sold and sstc properties
* 1.12 Moved bullets to features and amenities
* 1.13 Updated Features and Amenities matching
* 1.14 Added options to display epcs and floorplans in the gallery
* 1.15 Fixed problem with picking up the property area
* 1.16 Added property data purge action
* 1.17 Fixed the published date to come from feed
* 1.18 Fixed issue with post meta duplications
* 1.19 Updated to capture fetaures and amenities correctly
* 1.21 Updated to user the design defaults and also populate the status correctly
* 1.22 Added property brochure links to the main text since residence has not facility 
* 1.23 Added check to see if schedule is dropped and re-add if it has
* 1.24 Fixed the excerpt to pull across the excerpt rather than full content
* 1.25 Also fixed the full content
* 1.26 Fixed EPCs and Floorplan display
* 1.27 Added Council Tax Band Custom field
* 1.28 Fixed mapping of (Alto) Locality to Neighborhood
* 1.29 Fixed mapping of EPC data
* 1.30 Updated mapping for youtube virtual tours