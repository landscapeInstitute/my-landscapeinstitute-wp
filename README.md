
## MyLI WP Plugin


### Description

On it's own, when this plugin is installed it merely allows use of the myli_wp class which is an extended class of the myLI PHP class which uses wordpress data to store its settings. 
You can use the functions, methods, actions and filters provided by this plugin to add additional functionality.

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
	echo myli_wp()->get_user_profile()->FullName;
    }
});`

#### Actions
##### `myli_wp_before_load` :  MyLI WP Is about to load
##### `myli_wp_init` :  MyLI WP has finished loading
##### `myli_wp_before_oauth` :  User has returned from oAuth Response
##### `myli_wp_after_oauth` :  User has completed oAuth Response
##### `myli_wp_oauth_success` :  User has completed oAuth Response has successfully logged in
##### `myli_wp_oauth_failure` :  User has completed oAuth Response but has failed to log in

#### Filters
##### `myli_wp_admin_settings` :  Filter for adding new admin settings, provide additional html, example below



### Updates

Updates are handled by our own custom github updater, check the plugs page for notification of available updates. The Plugin can be reinstalled using the "Download" button or "Updated" when the update button is showing.

### Functions

Some basic functions that can be used
##### `myli_wp()->login()` :  Send the user to login and return to the current page
##### `myli_wp()->myli->get_user_profile()` :  Gets the users basic profile information as an object
##### `myli_wp()->myli->get_user_membership` :  Get the users basic membership information as an object

### Accessing Raw API
##### `myli_wp()->myli->api->me->userProfile->query()` :  Returns the same as get_user_profile

### Ajax Links for buttons etc

##### `admin-ajax.php?action=my_li_ajax_login&redirect=URL` :  Send the user to login and return to the given page
##### `admin-ajax.php?action=my_li_ajax_logout&redirect=URL` :  Send the user to logout and return to the given page
##### `admin-ajax.php?action=my_li_ajax_profile` : Send user their profile page on your given instance

### Add-on Plugins

If you are writing plugins which extend the functionality and wish to save settings you can use a filter to add additional settings. for example below
will add a new section and setting called new_setting which can be retrived using

`myli_wp()->get_option('my_setting')`

the following should be called during admin_init and after myli_wp_loaded action. 

`add_filter('myli_admin_settings',function($admin_settings){
					
	$admin_settings = $admin_settings . '
	
	<h2>New Settings</h2>
	<table class="form-table">
		<tbody>			
			<tr>
				<th scope="row"><label>' .  _('New Setting') . '</label></th>
				<td>
					<input type="text" name="new_setting" id="new_setting" value="' . myli_wp()->get_option('new_setting')  . '">
					<p class="description"' . _('Whats the value of new setting') . '</p>
				</td>
			</tr>
		</tbody>
	</table>
	
	';
	
	return $admin_settings;
					
 });`
