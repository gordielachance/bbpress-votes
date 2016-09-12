<?php
class bbP_Votes_Settings {
    
    static $menu_slug = 'bbpvotes';
    
    var $menu_page;

	function __construct() {
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );

        
	}

    function create_admin_menu(){

        $menu_page = add_options_page( 
            __( 'bbPress Votes', 'bbpvotes' ), //page title - I never understood why this parameter is needed for.  Put what you like ?
            __( 'bbPress Votes', 'bbpvotes' ), //menu title
            'manage_options', //cappability
            self::$menu_slug,
            array($this,'settings_page') //this function will output the content of the 'Music' page.
        );
        
        

    }

    function settings_sanitize( $input ){
        $new_input = array();

        if( isset( $input['reset_options'] ) ){
            
            $new_input = bbpvotes()->options_default;
            
        }else{ //sanitize values

            $new_input['vote_down_enabled'] = ( isset($input['vote_down_enabled']) ) ? 'on' : 'off';
            $new_input['unvote_enabled'] = ( isset($input['unvote_enabled']) ) ? 'on' : 'off';
            $new_input['anonymous_vote'] = ( isset($input['anonymous_vote']) ) ? 'on' : 'off';
    
        }
        
        //remove default values
        foreach((array)$input as $slug => $value){
            $default = bbpvotes()->get_default_option($slug);
            if ($value == $default) unset ($input[$slug]);
        }

        //$new_input = array_filter($new_input); //disabled here because this will remove '0' values

        return $new_input;
        
        
    }

    function settings_init(){

        register_setting(
            'bbpvotes_option_group', // Option group
            bbpvotes()->metaname_options, // Option name
            array( $this, 'settings_sanitize' ) // Sanitize
         );
        
        add_settings_section(
            'settings_general', // ID
            __('General','bbpvotes'), // Title
            array( $this, 'bbpvotes_settings_general_desc' ), // Callback
            'bbpvotes-settings-page' // Page
        );

        add_settings_field(
            'enable_downvoting', 
            __('Enable downvoting','bbpvotes'), 
            array( $this, 'enable_downvoting_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_general'//section
        );
        
        add_settings_field(
            'enable_unvoting', 
            __('Enable downvoting','bbpvotes'), 
            array( $this, 'enable_unvoting_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_general'//section
        );
        
        add_settings_field(
            'anonymous_voting', 
            __('Anonymous voting','bbpvotes'), 
            array( $this, 'anonymous_vote_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_general'//section
        );

        
        add_settings_section(
            'settings_system', // ID
            __('System','bbpvotes'), // Title
            array( $this, 'bbpvotes_settings_system_desc' ), // Callback
            'bbpvotes-settings-page' // Page
        );

        add_settings_field(
            'reset_options', 
            __('Reset Options','bbpvotes'), 
            array( $this, 'reset_options_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_system'//section
        );

    }
    
    function bbpvotes_settings_general_desc(){
        
    }
    
    function enable_downvoting_callback(){
        $option = bbpvotes()->get_options('vote_down_enabled');
        
        printf(
            '<input type="checkbox" name="%s[vote_down_enabled]" value="on" %s /> %s',
            bbpvotes()->metaname_options,
            checked( $option, 'on', false ),
            __("Allow users to vote down","bbpvotes")
        );
    }
    
    function enable_unvoting_callback(){
        $option = bbpvotes()->get_options('unvote_enabled');
        
        printf(
            '<input type="checkbox" name="%s[unvote_enabled]" value="on" %s /> %s',
            bbpvotes()->metaname_options,
            checked( $option, 'on', false ),
            __( 'Allow users to cancel their vote', 'bbpvotes' )
        );
    }
    
    function anonymous_vote_callback(){
        $option = bbpvotes()->get_options('anonymous_vote');
        
        printf(
            '<input type="checkbox" name="%s[anonymous_vote]" value="on" %s /> %s',
            bbpvotes()->metaname_options,
            checked( $option, 'on', false ),
            __( 'Allow users to cancel their vote', 'bbpvotes' )
        );
    }

    function bbpvotes_settings_system_desc(){
        
    }
    
    function reset_options_callback(){
        printf(
            '<input type="checkbox" name="%1$s[reset_options]" value="on"/> %2$s',
            bbpvotes()->metaname_options, // Option name
            __("Reset options to their default values.","bbpvotes")
        );
    }

	function  settings_page() {
        ?>
        <div class="wrap">
            <h2><?php _e('bbPress Votes Settings','bbpvotes');?></h2>  
            
            <?php settings_errors('bbpvotes_option_group');?>
            <form method="post" action="options.php">
                <?php

                // This prints out all hidden setting fields
                settings_fields( 'bbpvotes_option_group' );   
                do_settings_sections( 'bbpvotes-settings-page' );
                submit_button();

                ?>
            </form>

        </div>
        <?php
	}
}

new bbP_Votes_Settings;