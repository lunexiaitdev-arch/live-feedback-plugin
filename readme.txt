=== Lunexia Live Feedback ===
Contributors: lunexiait
Donate link: https://lunexiait.com
Tags: feedback, customer-feedback, visual-feedback, user-feedback, bug-tracker
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Capture customer insights directly on your WordPress site with a sleek live feedback overlay and in-context review panel.

== Description ==

Lunexia Live Feedback enables website visitors and administrators to provide contextual feedback directly on any page. Perfect for collecting user insights, bug reports, and feature requests with visual pinpointing and detailed metadata capture.

= Key Features =

* 💬 Guest feedback submission without login
* 📸 Screenshot capture integration
* 🎯 Visual feedback pinpointing with coordinate tracking
* 💻 Device & browser information logging
* 👥 Admin review panel with comment threads
* 🔧 Developing mode for team collaboration
* 📧 Email notifications for new feedback
* 🌐 Clean, mobile-responsive UI
* ⚙️ Granular permission controls

= Privacy & Security =

This plugin does not collect or transmit personal user data to external services without explicit opt-in. Guest feedback is stored locally in your WordPress database. All inputs are sanitized, nonces verify REST requests, and database queries use prepared statements.

For more information and support, visit [lunexiait.com](https://lunexiait.com).

== Installation ==

1. Upload the `lunexia-live-feedback` folder to the `/wp-content/plugins/` directory, or upload the ZIP file directly through WordPress **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Lunexia Live Feedback** in the WordPress admin menu to configure options.
4. Enable guest feedback in Settings if you wish to allow unauthenticated submissions.

== Frequently Asked Questions ==

= Can guests submit feedback without logging in? =
Yes! Enable "Allow Guest Feedback" in the plugin settings to let anonymous visitors submit feedback.

= Does the plugin track user data? =
The plugin captures browser information, device type, and page coordinates for debugging purposes only. No personal data is collected without explicit consent. Guests can submit feedback anonymously.

= Can I customize the feedback overlay appearance? =
The plugin uses a default theme that doesn't interfere with your site's styling. All plugin styles are scoped to avoid conflicts.

= Is this plugin secure? =
Yes. All inputs are sanitized, nonces verify REST requests, and database queries use prepared statements. Permission checks enforce access controls.

= What permissions do users need to manage feedback? =
Administrators can manage all feedback. Custom roles can be assigned `lunexia_lf_manage`, `lunexia_lf_create`, `lunexia_lf_resolve`, `lunexia_lf_assign`, and `lunexia_lf_upload` capabilities.

== Screenshots ==

1. Frontend feedback overlay with visual pinpointing
2. Admin review panel showing feedback list
3. Detailed feedback view with comments and metadata
4. Plugin settings page

== Changelog ==

= 0.1.0 =
* Initial release
* Guest feedback submission
* Screenshot capture
* Admin review panel
* Email notifications
* Mobile responsive design

== Source Code & Build Instructions ==

The unminified, human-readable source code for the admin dashboard is included directly within the plugin under the `admin/` directory (`admin/src/`, `admin/package.json`, and `admin/vite.config.js`).

To build or modify the admin bundle:
1. Navigate to the admin folder: `cd admin`
2. Install dependencies: `npm install`
3. Compile production assets: `npm run build`

Compiled assets are generated into `assets/dist/`.

== Upgrade Notice ==

= 0.1.0 =
Initial release of Lunexia Live Feedback.
