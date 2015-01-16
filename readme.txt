=== This Day In History ===
Contributors: BrokenCrust
Tags: history, today in history, on this day, this day in history, widget
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 2.0
License: GPLv2 or later

This is a This Day In History management plugin and widget which you can use to enter and display historic events.


== Description ==

This Plugin allows you to enter and manage different kinds of historical events that you then display in via a This Day In History widget or shortcodes.


== Installation ==

Installing is pretty easy takes only a minute or two.

1. Upload 'this-day-in-history' directory to your '/wp-content/plugins/' directory.

2. Activate the plugin through the 'Plugins' screen in WordPress.

3. On the 'Widgets' sub-menu of 'Appearance' you will find a new widget type called 'This Day In History'.

4. Add this widget, to your themes widget display areas, select a title and click Save.

5. Enter historic events via the new 'Historic Events' admin page.  These will be automatically displayed by the widget on the anniversary of the day that they occurred.


== Changelog ==

= 2.0
* The shortcode table date format is set via the TDIH option (bug fix)
* Updated shortcode help screen to remove commas from the example (bug fix)
* Split the shortcode into two (tdih) like the widget and (tdih_tab) table format
* It is now possible to enter a date without a year (enter 0000 for the year)
* Added option to show events for yesterday or tomorrow instead of today
* Added an option to allow sorting of the administration table of events by day, month or year first

= 1.1 =
* Fixed pagination issues with WordPress 4.0
* Moved number of events shown to a screen option
* Overhaul of admin screens
* Removed custom table migration code for early versions

= 1.0 =
* First production release
* Added show_all to the shortcode
* Added Option for the text displayed when there are no events

= 0.9.3 =
* Fixed Admin Bar bug in 3.6

= 0.9.2 =
* Improved function naming for cross plugin compatibility
* Fixed html5 date input issue with Chrome

= 0.9.1 =
* Fixed bug with search on admin list

= 0.9 =
* tdih shortcode added
* help updated

= 0.8.2 =
* Fix for duplicate events when using post types

= 0.8.1 =
* Fix for activation hook not firing on upgrade

= 0.8 =
* Added event types
* Events now stored as posts

= 0.7 =
* Added widget option to show or not show the year

= 0.6 =
* Added options page
* Added option for events per page and date format
* Removed 255 character limit for event names
* Change event name input to a textarea
* Some minor html bug fixes

= 0.5 =
* Fix for editing entries with double quotes (like some html code) - more magic quotes misery

= 0.4 =
* Fix for local (blog time) rather than server time

= 0.3 =
* Fix for miserable magic quotes

= 0.2 =
* Name changed from Today In History
* CSS layout updated
* Help text updated
* Fixed sorting after edit issue

= 0.1 =

* Initial Release

== Shortcodes ==

For version 2.0 the shortcode has been completely redesigned and split into two new shortcodes with new attributes.

= tdih =

You can add a tdih shortcode to any post or page to display a list of events as per the widget.

There are four optional attributes for this shortcode

* show_type (1 or 0) - 1 shows event types (default) and 0 does not.
* show_year (1 or 0) - 1 shows the year of the event (default) and 0 does not.
* type - enter a type to show only that type. Shows all types by default.
* period (t, m, y) - t shows events for today (default), m for tomorrow and y for yesterday.

Example use:

* [tdih] - This shows year and event types for all event types for todays events.
* [tdih show_type=0 type=birth] - This shows year and event but not type for the event type (slug) of birth.

= tdih_tab =

You can add a tdih_tab shortcode to any post or page to display a table of events.

There are nine optional attributes for this shortcode:

* show_type (1 or 0) - 1 shows event types (default) and 0 does not.
* show_year (1 or 0) - 1 shows the year of the event (default) and 0 does not.
* show_head (1 or 0) - 1 shows the year of the event (default) and 0 does not.
* type enter a type to show only events of that type. Shows all types by default.
* day (1-31) - enter a day to show only events on that day. Shows all days by default.
* month (1-12) - enter a month to show only events in that month. Shows all months by default.
* year (0001-9999) - enter a year to show only events in that year. Shows all years by default.
* period (t, m, y) - t shows events for today, m for tomorrow and y for yesterday. Shows all events by default.
* classes - enter one or more space separated classes which will be added to the table tag.

NB:

* Setting period will override and values for day, month and year.
* day, month and year can be combined.
* year=0 will display events with no year

Example use:

* [tdih_tab] - This shows a full list of events in date order and includes the event type.
* [tdih_tab show_types=0 type=birth classes='content dark'] - This shows events but not type for the event type (slug) of birth. " content dark" will be added to the table's class.
* [tdih_tab day=20 month=8] - This shows events on 20th August in any year.
