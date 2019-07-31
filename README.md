
~Current Version:1.4~

# MyLI WP Plugin

## Introduction

When installed on a site, settings for MyLI Will become available in the admin panel. These will request your

- instance id
- client id
- client secret

##

On it's own, when this plugin is installed it merely allows use of the myli_wp class which is an extended class of the myLI PHP class which uses wordpress data to store its settings. 

This Plugin should be extended by extensions. 

Example

    add_action('myli_wp_loaded', function(){
    
    	class myli_wp_plugin extends myli_wp { 
        
			function do_a_thing(){
			
				$this->myli->get_access_token();
				
				if($this->myli->has_access_token()){
					wp_die('user is logged in');
				}
			
			}
			
			function after_load(){
			
				$this->do_a_thing();
			
			}
		
		
		}
    });
    
    
