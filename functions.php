<?php

/*
Plugin Name: Landscape Institute | MyLI WP
Plugin URI: https://www.landscapeinstitute.org
Description: Setup oAuth2 and API access.
Version: 2.18
Author: Louis Varley
Author URI: http://www.landscapeinstitute.org
*/
/*
	Copyright 2017	NPRS	(email : louisvarley@googlemail.com)
	Licensed under the GPLv2 license: http://www.gnu.org/licenses/gpl-2.0.html
*/

require_once('vendor/autoload.php');
/********************************************************************************/
/* Handles Plugin Updates */
/********************************************************************************/

require 'plugin-update-checker/plugin-update-checker.php';
$updater = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/landscapeInstitute/my-landscapeinstitute-wp',
	__FILE__,
	'my-landscapeinstitute-wp'
);

$updater->setAuthentication(' 080fbc77d80856fe7d1d7608ff5dc42c38bf8081 ');
$updater->setBranch('master');
/***************************************/


add_action('init',function(){
    $myli_wp = new myli_wp();
    do_action('myli_wp_loaded');
});

class myli_wp{ 

	public function __construct(){
		
		$this->before_load();

		$this->myli = myLI(array(
			'client_id'=> $this->get_option('client_id'),
			'client_secret'=> $this->get_option('client_secret'),
			'instance_url'=> $this->get_option('instance_url'),
			'debug'=> (defined('WP_DEBUG') ? WP_DEBUG : false),
		));

        $this->after_load();
	}
	
    function before_load(){
		
		add_action('init', array($this,'start_session'), 1);
		
	}   
	
    function after_load(){
		
		add_action('admin_menu', array($this,'my_li_setup_menu'));
		add_action('wp_ajax_myli_oauth',array($this, 'my_li_ajax_oauth'));
		add_action('wp_ajax_nopriv_myli_oauth', array($this,'my_li_ajax_oauth'));

		add_action('wp_ajax_myli_logout',array($this, 'my_li_ajax_logout'));
		add_action('wp_ajax_nopriv_myli_logout', array($this,'my_li_ajax_logout'));
				
		add_action('wp_ajax_myli_profile',array($this, 'my_li_ajax_profile'));
		add_action('wp_ajax_nopriv_myli_profile', array($this,'my_li_ajax_profile'));	
	
		
	}
	
	function start_session() {
		if(!session_id()) {
			session_start();
		}

		add_action('wp_logout', 'end_session');
		add_action('wp_login', 'end_session');
		add_action('end_session_action', 'end_session');

		function end_session() {
			session_destroy ();
		}
	}	
				
	/* Update an Option */		
	public function update_option($key,$value){
		
		$this->$key = $value;
		update_option('myli_' . $key, $value);
	}
	
	/* Get an Option */
	public function get_option($key){
	
		return get_option('myli_' . $key);
	}		
	
	/* Admin Menu HTML */
	function my_li_menu(){
		echo _('<h1>MyLI oAuth2.0 Settings</h1>');
		
		if (isset($_POST['client_id'])) {
			$this->update_option('client_id', $_POST['client_id']);
		}
		
		if (isset($_POST['client_secret'])) {
			$this->update_option('client_secret', $_POST['client_secret']);
		}

		if (isset($_POST['instance_url'])) {
			$this->update_option('instance_url', $_POST['instance_url']);
		}		

		if (isset($_POST['json_file'])) {
			$this->update_option('json_file', $_POST['json_file']);
		}			

		?>
		<form method="POST">
			<table class="widefat">
				<tbody>
					<tr class="importer-item">
						<td class="import-system">
							<span class="importer-title"><?php echo _('Instance URL') ?></span>
							<input type="text" name="instance_url" id="instance_url" value="<?php echo $this->get_option('instance_url'); ?>">
						</td>
						<td class="desc">
							<span class="importer-desc"><?php echo _('Full URL of the site to connect to eg: https://dev-my.landscapeinstitute.org'); ?></span>
						</td>
					</tr>							
					<tr class="importer-item">
						<td class="import-system">
							<span class="importer-title"><?php echo _('Client ID') ?></span>
							<input type="text" name="client_id" id="client_id" value="<?php echo $this->get_option('client_id'); ?>">
						</td>
						<td class="desc">
							<span class="importer-desc"><?php echo _('What is the Client ID set in Dynamics?') ?></span>
						</td>
					</tr>
					<tr class="importer-item">
						<td class="import-system">
							<span class="importer-title"><?php echo _('Client Secret') ?></span>
								<input type="text" name="client_secret" id="client_secret" value="<?php echo $this->get_option('client_secret'); ?>">
						</td>
						<td class="desc">
							<span class="importer-desc"><?php echo _('What is the Client Secret set in Dynamics?') ?></span>
						</td>
					</tr>
					<tr class="importer-item">
						<td class="import-system">
							<span class="importer-title"><?php echo _('Return URL') ?></span>
								<input type="text" readonly="true"  value="<?php echo get_home_url() ?>/wp-admin/admin-ajax.php?action=myli_oauth">
						</td>
						<td class="desc">
							<span class="importer-desc"><?php echo _('Set the return/redirect URL in Dynamics to this value') ?></span>
						</td>
					</tr>	
					<tr class="importer-item">
						<td class="import-system" style="height:20px" colspan="2">
							
						</td>
					</tr>
					<tr class="importer-item">
						<td class="import-system" colspan="2">
							<input type="submit" value="<?php echo _('Save') ?>" class="button button-primary button-large">
						</td>
					</tr>				
				</tbody>
			</table>			
		</form>

		<?php
	}
	
	/* Admin menu Setup */
	function my_li_setup_menu(){
		
            add_menu_page('MyLI WP', 'MyLI', 'manage_options', 'my-li', array($this,'my_li_menu'),'dashicons-admin-network');
            add_submenu_page( 'my-li', 'Settings', 'Settings',
                'manage_options', 'my-li');
                
	}
	 
	/* AJAX oAuth Return URL has been called */
	function my_li_ajax_oauth(){
		
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		
		/* Error Checking */
		if(empty($_GET['code'])) 	      		wp_die('Error: ' . $_GET['error']); 		
		if(empty($this->myli->client_id)) 	  	wp_die('plugin not configured, No Client ID please notify the application owner'); 	
		if(empty($this->myli->client_secret))   wp_die('plugin not configured, No Client Secret please notify the application owner'); 		 	

		/* Uses the provided oAuth Code to get a Token */
		$this->myli->get_access_token();
				
		/* Get the original location when the login was made */
		$redirect = $this->myli->get_origin();
		

        /* Fetch Users Profile */
		$this->myli->get_user_profile();
 
		/* Fetch Users Membership */
		$this->myli->get_user_membership();

		/* Redirect to Origin */
		if($redirect){
	
			header("Location: " . $redirect );
			die();	
			
		}else{
			header("Location: " . '/' );
			die();	
		}

	}

	/* AJAX Logout */
	function my_li_ajax_logout(){
		
		$redirect = $_GET['redirect'];
		$this->logout($redirect);       
		wp_die();
		
	}	
	

	function my_li_ajax_profile(){
		
		$profile = $this->api->app->getprofileurl->query();

		wp_redirect( $profile );
		wp_die();
		
	}	
	
}


?>
