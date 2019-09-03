<?php

/*
Plugin Name: Landscape Institute | MyLI WP
Plugin URI: https://github.com/landscapeInstitute/my-landscapeinstitute-wp
Description: Setup oAuth2 and API access.
Version: 2.26
Author: Louis Varley
Author URI: http://www.landscapeinstitute.org
*/
/*
	Copyright 2019	Landscape Institute	(email : louisvarley@googlemail.com)
	Licensed under the GPLv2 license: http://www.gnu.org/licenses/gpl-2.0.html
*/

require_once('vendor/autoload.php');

add_action('admin_init',function(){
	new WP_GitHub_Updater(__FILE__);
});

/***************************************/

function myli_wp(){
	return myli_wp::instance();
}

class myli_wp extends myLI{ 

	public static $instance = null;

	public static function instance() {

        if ( null == self::$instance ) {
            static::$instance = new static();
			static::$instance->init();
        }

        return static::$instance;
    }

	public function __construct(){
		
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		
		do_action("myli_wp_before_load");
		
		$this->before_load();
		
		$arr = array(
			'client_id' =>$this->get_option('client_id'),
			'client_secret' => $this->get_option('client_secret'),
			'instance_url' => $this->get_option('instance_url'),
			'debug' => (defined('WP_DEBUG') ? WP_DEBUG : false),
			'access_token' => (isset($_SESSION['myli_access_token']) ? $_SESSION['myli_access_token'] : null),
		);
		

		$this->setup($arr);
			
        $this->after_load();

	}
	
	private function init(){
		do_action("myli_wp_init");
	}
	
    private function before_load(){
		
		
		
	}    
	
	
	private function after_load(){
		
		add_action('admin_menu', array($this,'my_li_setup_menu'));
		add_action('wp_ajax_myli_oauth',array($this, 'my_li_ajax_oauth'));
		add_action('wp_ajax_nopriv_myli_oauth', array($this,'my_li_ajax_oauth'));

		add_action('wp_ajax_myli_login',array($this, 'my_li_ajax_login'));
		add_action('wp_ajax_nopriv_myli_login', array($this,'my_li_ajax_login'));

		add_action('wp_ajax_myli_logout',array($this, 'my_li_ajax_logout'));
		add_action('wp_ajax_nopriv_myli_logout', array($this,'my_li_ajax_logout'));
				
		add_action('wp_ajax_myli_profile',array($this, 'my_li_ajax_profile'));
		add_action('wp_ajax_nopriv_myli_profile', array($this,'my_li_ajax_profile'));		
		
	}
			
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
		echo _('<h1>MyLI WP Settings</h1><br />');
		
		$admin_settings = null;

		foreach($_POST as $key => $var){
			
			$this->update_option($key, $var);
		}

		?>
		<form method="POST">
			<h2>General Settings</h2>
			<table class="form-table">
				<tbody>			
					<tr>
						<th scope="row"><label for="blogname"><?php echo _('Instance URL') ?></label></th>
						<td>
							<input type="text" name="instance_url" id="instance_url" value="<?php echo(isset($this->instance_url) ? $this->instance_url : null) ?>">
							<p class="description"><?php echo _('Full URL of the site to connect to eg: https://dev-my.landscapeinstitute.org'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname"><?php echo _('Client ID') ?></label></th>
						<td>
							<input type="text" name="client_id" id="client_id" value="<?php echo(isset($this->client_id) ? $this->client_id : null) ?>">
							<p class="description"><?php echo _('What is the Client ID set in Dynamics?') ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname"><?php echo _('Client Secret') ?></label></th>
						<td>
							<input type="text" name="client_secret" id="client_secret" value="<?php echo(isset($this->client_secret) ? $this->client_secret : null) ?>">
							<p class="description"><?php echo _('What is the Client Secret set in Dynamics?') ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname"><?php echo _('Return URL') ?></label></th>
						<td>
							<input type="text" readonly="true"  value="<?php echo get_home_url() ?>/wp-admin/admin-ajax.php?action=myli_oauth">
							<p class="description"><?php echo _('Set the return/redirect URL in Dynamics to this value') ?></p>
						</td>
					</tr>				
				</tbody>
			</table>
			<?php echo apply_filters('myli_wp_admin_settings',$admin_settings); ?>
			<input type="submit" value="<?php echo _('Save') ?>" class="button button-primary button-large">
		</form>

		<?php
	}
	
	/* Admin menu Setup */
	function my_li_setup_menu(){
		
            add_menu_page('MyLI Settings', 'MyLI', 'manage_options', 'my-li', array($this,'my_li_menu'),'dashicons-admin-network');
            add_submenu_page( 'my-li', 'Settings', 'Settings',
                'manage_options', 'my-li');
                
	}
	
	/* Set the access token */
	function set_access_token($access_token){
			
		if (!session_id())
			session_start();
		
		$this->access_token = $access_token;
		$this->api->access_token = $access_token;
		$_SESSION['myli_access_token'] = $access_token;
		


    
	}	
	 
	/* AJAX oAuth Return URL has been called */
	function my_li_ajax_oauth(){

		do_action("myli_wp_before_oauth");
		
		/* Error Checking */
		if(empty($_GET['code'])) 	      		wp_die('Error: ' . $_GET['error']); 		
		if(empty($this->client_id)) 	  		wp_die('plugin not configured, No Client ID please notify the application owner'); 	
		if(empty($this->client_secret))   		wp_die('plugin not configured, No Client Secret please notify the application owner'); 		 	

		/* Uses the provided oAuth Code to get a Token */
		$this->get_access_token();						
				
		/* Get the original location when the login was made */
		$redirect = $this->get_origin();
		
        /* Fetch Users Profile */
		$this->get_user_profile();
 
		/* Fetch Users Membership */
		$this->get_user_membership();
		
		do_action("myli_wp_after_oauth");
		
		if($this->has_access_token()){
			
			do_action("myli_wp_oauth_success");

				/* Redirect to Origin */
				if($redirect){
					header("Location: " . $redirect );
					exit();
				}else{
					header("Location: " . '/' );
					exit();	
				}
			
		}else{
			
			do_action("myli_wp_oauth_failure");
			echo 'An error occurred while logging you in, please close all windows and try again';
			exit();
		}
		


	}

	/* AJAX Logout */
	function my_li_ajax_logout(){
		
		$redirect = $_GET['redirect'];
		$this->logout($redirect);       
		wp_die();
		
	}	
	
	/* AJAX Logout */
	function my_li_ajax_login(){
		
		$redirect = $_GET['redirect'];
		$this->login($redirect);       
		wp_die();
		
	}	
	
	function my_li_ajax_profile(){
		
		$profile = $this->api->app->getprofileurl->query();
		wp_redirect( $profile );
		wp_die();
		
	}	
	
}

add_action('init',function(){
	myli_wp();
});

?>
