=== HeritageAction Scorecard: Member Score ===
Contributors: heritageaction,
Donate link: https://heritageaction.com/donate
Tags: heritage action, politics, congress
Requires at least: 3.0
Tested up to: 4.3
Stable tag: 1.0.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Highlight Members of Congress with their HeritageAction Scorecard score

== Description ==

The HeritageAction Scorecard enhanced Member of Congress bubble effect is essentially a replacement of the member of congress' name with inline markup that indicates the name can be interacted with, and a hidden bubble of information that is displayed when hovered over by the user.
  
This process is first accomplished by allowing editors of the content on the site to use a shortcode instead of a member's name to easily designate when to use these bubbles and to ensure consistent formatting is observed. A button is added to the WordPress editor to simplify the task of referencing Members of Congress when writing copy for the site.
  
Once content contains the appropriate shortcodes, a content filter is applied that replaces the shortcode with the appropriate markup. The markup rendered is updated when the user hovers over the name to get the current score at the time of the page load.

An API key is required for interaction with the Scorecard. You can obtain one here: http://heritageaction.com/request-score-card-api-key/

== Installation ==

To  Install HeritageAction Scorecard Member Score

1. Download the heritageaction-score-card-member-scores.zip file 
2. Extract the zip file so that you have a folder called heritageaction-score-card-member-scores
3. Upload the 'heritageaction-score-card-member-scores' folder to the `/wp-content/plugins/` directory
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Configure your settings in the Scorecard Settings panel

To Uninstall HeritageAction Scorecard Member Score

1. Deactivate HeritageAction Scorecard Member Score through the 'Plugins' menu in WordPress
2. Click the "delete" link to delete the HeritageAction Scorecard Member Score plugin. This will remove all of the HeritageAction Scorecard Member Score files from your plugins directory.

== Frequently Asked Questions ==

= How can I correct CSS issues? = 

Using the custom CSS field in the settings you can override the default CSS.

= How can I get support? =

We are not able to provide anything other than community based support for HeritageAction Scorecard Member Score.

== Screenshots ==

1. HeritageAction Scorecard Member Score Settings
2. Insert Member of Congress
3. Select Members of Congress
4. HeritageAction Scorecard badge on live content

== Changelog ==

= 1.0.6 =
* Increase z-index for autocomplete results for WordPress 3.9.1 compatibility 

= 1.0.5 =
* Catch failed API calls

= 1.0.4 =
* Bug fixes

= 1.0.3 =
* Added Chamber Party average to bubbles
* Improved API call efficiency 
* Updated bubble CSS to fix padding

= 1.0.2 =
* Improved remote API call with wp_remote_get
* Added more compatible CSS for popup markup
* Added custom CSS setting

= 1.0.1 =
* Added support for Speaker of the House score of "n/a" rather than 0%
* Hide popup bubbles when body is clicked

= 1.0 =
* Initial Deployment

== Upgrade Notice ==

= 1.0 =
No upgrade needed, initial deployment.