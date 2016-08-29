# Smart Podcast Player
Requires at least: 3.9.4
Tested up to: 1.0
Stable tag: 1.3.1
Copyright: SPI Labs, LLC

An audio player built for podcasters by podcasters.  Most web-based podcast players are dumb. Not this one.  The Smart Podcast Player is the best way to engage your listeners and grow your audience.

## Changelog

### v1.4.0
* Added options for episode timer: up, down, or not shown
* Added option to not display episode numbers in SPP playlist
* Added options for STP progress bar color when loading and playing
* Added ability to stick an STP to the top of the page while scrolling
* Added partial support for M4A podcasts
* Added workaround for broken feeds in Wordpress 4.5 update
* Fixed STP width bug when resizing windows
* Fixed STP width bug when using players of multiple widths
* Improved performance of dynamically-generated CSS
* Improved performance of pages with multiple Soundcloud STPs
* Fix for missing artist in track player
* Added option for adding assets directly to HTML
* Fixed text color bug in show notes
* Fixed permalink option for email sharing
* Added workaround for certain Feedpress feeds
* Added workaround for pages that load jQuery more than once

### v1.3.0
* Added ability to feature an episode in Smart Podcast Player
* Enabled scrolling for long title or artist in Smart Track Player
* Added option to limit the number of episodes shown
* Improved performance of latest track player
* Added option to hide the listen counter in SPP
* Mitigated compatibility issues with Thrive Content Builder
* Specified zero padding around images
* Fixed some naming conflicts with Javascript function 'style'
* Fixed bug in latest track player when using feeds.soundcloud.com


### v1.2.0
* Added functionality for Smart Track Player to play the latest track from a feed
* Overhauled the player default settings page
* Improved the shortcode builders
* Added SPP cache busting button to settings page
* Added a selector for where in the RSS feed the show notes come from
* Option to override theme's CSS within player
* Mitigated some themes'/plugins' conflicts with WP Color Picker
* Added workaround for servers without PHP mb_string
* Fixed scope resolution bug affecting some servers
* Fixed SPP cache clearing bug
* Improved visual design when more than four sharing buttons are in SPP

### v1.1.2
* Fixed bug that caused the SPP cache not to clear on a manual clearing operation.

### v1.1.1
* Fixed syntax error which resulted in a variety of failures for some users.

### v1.1.0
* Added option for default image in Smart Track Player.
* Allow for all seven social sharing options at once.
* Fixed download button for web hosts that reject URLs in query string.
* More robust to other plugins/themes that inject Javascript into the page.
* Fixed timeout issue when getting file data on Windows hosts.
* Enlarged text boxes in some settings fields for ease of use.
* Fixed disappearing controls when some shortcode options were utilized.
* Cache busting clears all SPP transients, not just some of them.
* Fixed bug that ignored email social sharing option in STP.

### v1.0.3

* Mobile sharer links revised. FB sharer and Twitter sharer links updated to be in line with API changes.
* Soundcloud API compatibility fix for 200+ track full players - offset deprecated thus pagination per API spec implemented.
* Changing sort order will now reset the page to the one containing the track whose details are being shown.
* Update state variables to currently playing when the title bar is clicked that may prevent pausing.
* Extended fetching timeout for feeds that load slowly which could result in an intermittent full player.
* Speed improvements for fetching remote feeds including better feed change detection to improve performance.
* Fixed edge case that could prevent license check/update request due to web host configuration issue for WP request via SSL.
* Assets directory separator corrected for Windows based wordpress installs.
* Brief full player "green" flash as CSS default background color on load eliminated.
* Phased out need for single track player AJAX CSS custom colors request if default color is utlized.

### v1.0.2

* New UI image set for SPP including pagination arrows, download, subscribe, and sort.
* Featured image along the left side of the Smart Track Player can be set via the shortcode tool.
* Added three additional social sharing sites: LinkedIn, Pinterest, StumbleUpon.
* Social sharing sites can be selected per Smart Track Player/Smart Podcast Player via the shortcode tool.
* Social sharing can now be selectively toggled off by shortcode.
* Speed control can now be selectively toggled off by shortcode.
* Added checks to identify permalink's in feeds that are null.
* Compatability support for the new WP 4.2 "update now" AJAX call from the plugins page.
* More visible WP admin notifications to ensure support for future updates.

### v1.0.1

* UI enhancement to reduce the width of download/subscribe buttons when in responsive mode (also when usable page width < 768px).
* Improved language support by reducing english word footprint for player actions (such as download, subscribe, and sort).
* "Powered by Smart Podcast Player" in full player can now be toggled off.
* Speed Boost: Phased out repetetive AJAX requests for Smart Track Player data.
* Enhanced title and artist fetching for web hosts that may not sufficiently accomodate capturing ID3 tags from remote MP3's.
* Soundcloud artwork is now protocol agnostic to further enhance support for HTTPS sites.
* Fixed issue with embedded ID3 tag artwork clogging up track data.
* Added shortcode tool button for custom WP post types as well.
* Minor tweaks for cases when full show notes should trump abbreviated show notes in the full player.
* "Smarter" title defaults for the Smart Track Player - if ID3 title isn't present, album then artist, can populate player's title.

### v1.0.0 / v0.9.5

* Added speed control functionality for faster or slower playback rates (0.5x, 1x, 1.5x, 2x, 3x).
* Added social sharing capability native to the Smart Track Player.
* Enabled functionality to prevent the default green background from flashing prior to custom colors being loaded.
* Resolved issue where PowerPress users may have abbreviated (truncated ...) show notes.
* Plugin update notification improvements.
* UI responsive enhancements for mobile browsers.
* Fixed minor edge case where ampersands in feed URLs could prevent tracks from playing.
* Updated player controls including a new UI for the Smart Track Player.
* Resolved issue where admin JS file caused a conflict with editing widgets. Plugin now loads admin JS only when required.
* Added a highly visible loading progress to the background alongside the current playing progress.
* General download button improvements to address technical nuances amongst browsers. Enhanced support for HTTPS sites.
* Fixed issue with the legacy cache directory location not exclusively being utilized.
* Tested to support Wordpress 4.0+.

### v0.9.4

* Player will now render larger soundcloud track lists (200+).
* Various performance improvements for soundcloud and feeds.
* Added advanced setting for specifying the number of minutes to cache.
* Added advanced setting to select the podcast download button's PHP method.
* Enabled two alternate download methods to improve support for the download button.
* Fixed issue getting the MP3 file size. Browser can now calculate estimated download time remaining.
* Compatibility bug fix to prevent getID3 redclare warning if its already loaded by another plugin.
* Minor bug fix to support constant usage for legacy PHP version 5.2.

### v0.9.3

* The manual check for updates link on the wordpress plugins page is now operational.
* New message box alert if Smart Track Player is added without URL via the shortcode button.
* Security improvements to safely sanitize URLs.
* License keys with trailing or leading spaces wont prevent update checks.
* Fixed minor issue with v0.9.2 still showing the previous v0.9.1 query string on assets.

### v0.9.2

* <strong>Fixed issue for shared hosting where MP3 downloads and data retrieval were not working.</strong>
* Improved internal and external documentation of the plugin.
* Improved CSS functionality.
* Added subscription button.
* Multiple minor performance bugs.

### v0.9.1

* <strong>Updated custom color handling to be universally applied in themes</strong> (including widgets, archive pages, etc).
* Adjusted Firefox background-position-y bug that caused player controls to be the wrong color.
* Solved character encoding issue where strange characters appeared in place of punctuations.
* Refined player color stylings and calculations based on custom colors.
* Ensured "Dark" styles are applied when set in the Player Defaults.
* Updated positioning of download button in narrow podcast players.

### v0.8.5

* Updated background MP3 file handling.
* Added ability for single track player to play most recent track from a feed.</li>
* Added oldest/newest sorting to podcast player playlist.</li>
* Added Smart Track Player shortcode builder</li>
* Added Smart Podcast Player shortcode builder.</li>
* Added default settings for SPP</li>
* Added ability to select custom colors for podcast and track player.</li>
* Updated settings tab layout to be more simple.</li>
</ol>

### v0.8.4

* Adjusted SPP file cache functionality.</li>
* Added HTML filtering from RSS feeds</li>
* Updated size of paging buttons in playlist for better touch functionality.</li>
* Added ability to disable social buttons via shortcode.</li>
* Added ability to disable download in podcast player.</li>
* Resolved conflict with Q2W3 Fixed Widget jQuery resize event.</li>

### v0.8.3

* Admin file loading bug causing a crash of the WP admin has been resolved.</li>
* Link handling in player has been updated for various feed sources.</li>
* ID3 data retrieval has been updated to handle more server configurations and more intelligent buffer sizes.</li>
* Documentation has been updated to be more copy/paste friendly.</li>
* CSS classes have been namespaced to create more consistent results across themes.</li>
* Updated integration with OptimizePress plugin pages.</li>
* Updated mobile stylings to integrate with WooThemes Canvas.</li>
* Updated remote audio file retrieval to work with a wider variety of server configurations.</li>
* New, more robust RSS retrieval methods.</li>
* Updated social URL sharing for RSS feeds.</li>
* Added method for auto-detecting and scrubbing of some HTML used within shortcodes.</li>
* Updated internal audio and feed data cache handling by SPP</li>
