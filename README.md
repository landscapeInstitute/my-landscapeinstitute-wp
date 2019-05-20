

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
        
       /* Constructor, dont create a constructor, use before_load and after_load */
        	
                function before_load(){
    	           /* My Hooks and Filters go here */
    	        } 
    	       
                function after_load(){
    		   /* My Hooks and Filters go here */
    	        }
            
            }
        
          $myli_wp_plugin = new myli_wp_plugin ();
    });
    
    

