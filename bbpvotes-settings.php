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
        
        if( isset( $input['rebuild_scores'] ) ){
            bbpvotes_rebuild_scores();
        }

        if( isset( $input['reset_options'] ) ){
            
            $new_input = bbpvotes()->options_default;
            
        }else{ //sanitize values
            
            //post types
            $post_types = bbpvotes_get_allowed_post_types();
            if( isset( $input['post_types'] ) ){
                foreach ((array)$post_types as $post_type){
                    if (!array_key_exists($post_type, $input['post_types'])) $new_input['ignored_post_type'][] = $post_type;
                }
            }else{
                $new_input['ignored_post_type'] = $post_types;
            }

            $new_input['vote_down_enabled'] = ( isset($input['vote_down_enabled']) ) ? 'on' : 'off';
            $new_input['unvote_enabled'] = ( isset($input['unvote_enabled']) ) ? 'on' : 'off';
            $new_input['best_reply_enabled'] = ( isset($input['best_reply_enabled']) ) ? 'on' : 'off';
            $new_input['anonymous_vote'] = ( isset($input['anonymous_vote']) ) ? 'on' : 'off';
            $new_input['embed_votes_log'] = ( isset($input['embed_votes_log']) ) ? 'on' : 'off';
            $new_input['karma_cache_minutes'] = (int) $input['karma_cache_minutes'];
            
            //units

            $new_input['unit_singular'] = trim($input['unit_singular']);
            $new_input['unit_plural'] = trim($input['unit_plural']);

            if (!$new_input['unit_singular'] || !$new_input['unit_plural'] ){
                unset($new_input['unit_singular']);
                unset($new_input['unit_plural']);
            }
    
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
            'post_types_enabled', 
            __('Post types enabled','bbpvotes'), 
            array( $this, 'post_types_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_general'//section
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
            __('Enable unvoting','bbpvotes'), 
            array( $this, 'enable_unvoting_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_general'//section
        );
        
        add_settings_field(
            'enable_best_reply', 
            __('Enable best reply','bbpvotes'), 
            array( $this, 'enable_best_reply_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_general'//section
        );
        
        add_settings_section(
            'settings_log', // ID
            __('Vote Log','bbpvotes'), // Title
            array( $this, 'bbpvotes_settings_votelog_desc' ), // Callback
            'bbpvotes-settings-page' // Page
        );
        
        add_settings_field(
            'embed_log', 
            __('Embed vote log','bbpvotes'), 
            array( $this, 'embed_log_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_log'//section
        );
        
        add_settings_field(
            'anonymous_voting', 
            __('Anonymous voting','bbpvotes'), 
            array( $this, 'anonymous_vote_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_log'//section
        );
        
        add_settings_section(
            'settings_display', // ID
            __('Display','bbpvotes'), // Title
            array( $this, 'bbpvotes_settings_display_desc' ), // Callback
            'bbpvotes-settings-page' // Page
        );
        
        add_settings_field(
            'units', 
            __('Units','bbpvotes'), 
            array( $this, 'units_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_display'//section
        );
        
        add_settings_section(
            'settings_system', // ID
            __('System','bbpvotes'), // Title
            array( $this, 'bbpvotes_settings_system_desc' ), // Callback
            'bbpvotes-settings-page' // Page
        );

        add_settings_field(
            'karma_cache_minutes', 
            __('Cache karma','bbpvotes'), 
            array( $this, 'karma_cache_minutes_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_system'//section
        );
        
        add_settings_field(
            'rebuild_scores', 
            __('Rebuild scores','bbpvotes'), 
            array( $this, 'rebuild_scores_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_system'//section
        );
        
        add_settings_field(
            'reset_options', 
            __('Reset options','bbpvotes'), 
            array( $this, 'reset_options_callback' ), 
            'bbpvotes-settings-page', // Page
            'settings_system'//section
        );

    }
    
    function bbpvotes_settings_general_desc(){
        
    }
    
    /** 
     * Get the settings option array and print one of its values
     */
    function post_types_callback(){

        $enabled = bbpvotes_get_enabled_post_types();
        $allowed = bbpvotes_get_allowed_post_types();

        foreach ((array)$allowed as $slug){

            if ( !$post_type_obj = get_post_type_object($slug) ) continue;
            $name = $post_type_obj->name;
            $checked = checked( in_array($slug,$enabled), true, false );
            printf(
                '<input type="checkbox" name="%1$s[post_types][%2$s]" value="on" %3$s/> %4$s ',
                bbpvotes()->metaname_options,
                $slug,
                $checked,
                $name
            );
        }
    }
    
    function enable_best_reply_callback(){
        $option = bbpvotes()->get_options('best_reply_enabled');
        
        printf(
            '<input type="checkbox" name="%s[best_reply_enabled]" value="on" %s /> %s',
            bbpvotes()->metaname_options,
            checked( $option, 'on', false ),
            __("Allow topic authors to mark the best reply","bbpvotes")
        );
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
    
    function bbpvotes_settings_votelog_desc(){
        
    }
    
    function embed_log_callback(){
        $option = bbpvotes()->get_options('embed_votes_log');
        
        printf(
            '<input type="checkbox" name="%s[embed_votes_log]" value="on" %s /> %s',
            bbpvotes()->metaname_options,
            checked( $option, 'on', false ),
            __( "Display the list of voters under the post", 'bbpvotes' )
        );
    }
    
    function anonymous_vote_callback(){
        $option = bbpvotes()->get_options('anonymous_vote');
        
        printf(
            '<input type="checkbox" name="%s[anonymous_vote]" value="on" %s /> %s',
            bbpvotes()->metaname_options,
            checked( $option, 'on', false ),
            __( "Hide voters identity from the vote log", 'bbpvotes' )
        );
    }
    
    function bbpvotes_settings_display_desc(){
        
    }
    
    function units_callback(){
        $singular_value = bbpvotes()->get_options('unit_singular');
        $singular_default = bbpvotes()->get_default_option('unit_singular');
        $plural_value = bbpvotes()->get_options('unit_plural');
        $plural_default = bbpvotes()->get_default_option('unit_plural');
        
        $singular = sprintf(
            '%s <input type="text" name="%s[unit_singular]" value="%s" placeholder="%s"/> %s',
            '<strong>'.__('singular:','bbpvotes').'</strong>',
            bbpvotes()->metaname_options,
            $singular_value,
            $singular_default,
            '<small> — '.sprintf(__( "Where %s will be replaced by a number", 'bbpvotes' ),'<code>%s</code>').'</small>'
        );
        
        $plural = sprintf(
            '%s <input type="text" name="%s[unit_plural]" value="%s" placeholder="%s"/> %s',
            '<strong>'.__('plural:','bbpvotes').'</strong>',
            bbpvotes()->metaname_options,
            $plural_value,
            $plural_default,
            '<small> — '.sprintf(__( "Where %s will be replaced by a number", 'bbpvotes' ),'<code>%s</code>').'</small>'
        );
        
        printf('<p>%s</p><p>%s</p>',$singular,$plural);
        
        
        
    }

    function bbpvotes_settings_system_desc(){
        
    }
    
    function karma_cache_minutes_callback(){
        $option = bbpvotes()->get_options('karma_cache_minutes');
        printf(
            '<input type="number" name="%s[karma_cache_minutes]" value="%s" min="0" /> %s %s',
            bbpvotes()->metaname_options, // Option name
            $option,
            __("minutes","bbpvotes"),
            '<small> — '.__("How long should we cache karma for users ?","bbpvotes").'</small>'
        );
    }
    
    function rebuild_scores_callback(){
        printf(
            '<input type="checkbox" name="%s[rebuild_scores]" value="on"/> %s %s',
            bbpvotes()->metaname_options, // Option name
            __("Rebuild all scores","bbpvotes"),
            '<small> — '.__("This could be slow to compute","bbpvotes").'</small>'
        );
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