

### Description

On it's own, when this plugin is installed it merely allows use of the myli_wp class which is an extended class of the myLI PHP class which uses wordpress data to store its settings. You can extend the MyLI Class to provide additional functionality or use it's instance in your own classes or use it's actions.

### Installation

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. When installed on a site, settings for MyLI Will become available in the admin panel. These will request your instance URL, Client ID and Client Secret

### Usage

`add_action('myli_wp_init',function(){
	/* Not Logged In */
	if(!is_user_logged_in()){
		myli_wp()->login();
	}else{
	echo myli_wp()->myli->get_user_profile()->FullName;
    }
});`

#### Hooks and Actions
##### `myli_wp_before_load` :  MyLI WP Is about to load
##### `myli_wp_init` :  MyLI WP has finished loading
##### `myli_wp_before_oauth` :  User has returned from oAuth Response
##### `myli_wp_after_oauth` :  User has completed oAuth Response

### Updates

Updates are handled by our own custom github updater, check the plugs page for notification of available updates. The Plugin can be reinstalled using the "Download" button or "Updated" when the update button is showing.

### Functions

Some basic functions that can be used
##### `myli_wp()->login()` :  Send the user to login and return to the current page
##### `myli_wp()->myli->get_user_profile()` :  Gets the users basic profile information as an object
##### `myli_wp()->myli->get_user_membership` :  Get the users basic membership information as an object

### Accessing Raw API
##### `myli_wp()->myli->api->me->userProfile->query()` :  Returns the same as get_user_profile


