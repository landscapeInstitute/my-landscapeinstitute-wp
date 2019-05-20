<?php

/*
Plugin Name: Landscape Institute | MyLI API and oAuth2.0
Plugin URI: https://www.landscapeinstitute.org
Description: Setup oAuth2.0 using MyLI API and oAuth2.0
Version: 2.0
Author: Louis Varley
Author URI: http://www.landscapeinstitute.org
*/
/*
	Copyright 2017	NPRS	(email : louisvarley@googlemail.com)
	Licensed under the GPLv2 license: http://www.gnu.org/licenses/gpl-2.0.html
*/

require_once('/vendor/autoload.php');

add_action('init',function(){
    $myli_wp = new myli_wp();
    do_action('myli_wp_loaded');
});

class myli_wp extends myLI{ 

	/* Constructor , extends default to use Wordpress Settings */
	public function __construct(){

        $this->before_load();
		
		$this->client_id = $this->get_option('client_id');
		$this->client_secret = $this->get_option('client_secret');
		$this->instance_url = $this->get_option('instance_url');
		$this->oAuth_url = $this->instance_url . '/oauth/' . $this->client_id;
        $this->access_token = myLISession::load('access_token');
		
		$this->json_file = $this->instance_url . '/api/swagger.json';
		$this->api = new myLIAPI($this->json_file, $this->access_token, $this->debug);
		
		add_action('admin_menu', array($this,'my_li_setup_menu'));
		add_action('wp_ajax_myli_oauth',array($this, 'my_li_ajax_oauth'));
		add_action('wp_ajax_nopriv_myli_oauth', array($this,'my_li_ajax_oauth'));
        	
		if (defined('WP_DEBUG')) {
		   $this->debug = WP_DEBUG;
		}	
			
        $this->after_load();
	}
    
    function before_load(){}    
    function after_load(){}
			
	/* Update an Option */		
	public function update_option($key,$value){
		
		$this->$key = $value;
		update_option('myli_' . $key, $value);
	}
	
	/* Get an Option */
	public function get_option($key){
		
		return get_option('myli_' . $key, '');
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
			<table class="widefat striped">
				<tbody>
					<tr class="importer-item">
						<td class="import-system">
							<span class="importer-title"><?php echo _('Instance URL') ?></span>
							<input type="text" name="instance_url" id="instance_url" value="<?php echo $this->instance_url; ?>">
						</td>
						<td class="desc">
							<span class="importer-desc"><?php echo _('Full URL of the site to connect to eg: https://dev-my.landscapeinstitute.org'); ?></span>
						</td>
					</tr>							
					<tr class="importer-item">
						<td class="import-system">
							<span class="importer-title"><?php echo _('Client ID') ?></span>
							<input type="text" name="client_id" id="client_id" value="<?php echo $this->client_id; ?>">
						</td>
						<td class="desc">
							<span class="importer-desc"><?php echo _('What is the Client ID set in Dynamics?') ?></span>
						</td>
					</tr>
					<tr class="importer-item">
						<td class="import-system">
							<span class="importer-title"><?php echo _('Client Secret') ?></span>
								<input type="text" name="client_secret" id="client_secret" value="<?php echo $this->client_secret; ?>">
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
		
	
            add_menu_page('My Custom Page', 'MyLI', 'manage_options', 'my-li', array($this,'my_li_menu'),'dashicons-admin-network');
            
            add_submenu_page( 'my-li', 'Settings', 'Settings',
                'manage_options', 'my-li');
                
	}
	 
	/* AJAX oAuth Return URL has been called */
	function my_li_ajax_oauth(){
        
        
  
		/* Error Checking */
		if(!isset($_GET['refreshtoken'])) wp_die('No Refresh Token Provided was provided.');
		if(empty($this->client_id)) 	  wp_die('plugin not configured, please notify the application owner'); 	
		if(empty($this->client_secret))   wp_die('plugin not configured, please notify the application owner'); 		
		if(empty($this->json_file))  	  wp_die('plugin not configured, please notify the application owner'); 	

		/* Set the refresh token */
		$this->set_refresh_token($_GET['refreshtoken']);
		        
        /* Refresh token was invalid */  
  
		if($this->refresh_token_valid() == false){
            wp_die('The provided refresh token was invalid. Please notify the application owner.');
        }
   
		/* Our Origin for next hop */
		$origin = (isset($_GET['origin']) ? $_GET['origin'] : '/');
	        
		/* Fetch Access Token */
		$this->get_access_token();
 
        /* Fetch Users Profile */
		$this->get_user_profile();
		
		/* Fetch Users Membership */
		$this->get_user_membership();

		/* Redirect to Origin */
		wp_redirect( $origin );
		wp_die();
		
	}	
}


?>
