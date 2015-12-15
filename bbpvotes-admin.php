<?php

class bbP_Votes_Admin {
    
    private $column_name_score = 'bbpvotes_score';

    function __construct() {

        self::setup_globals();
        self::includes();
        self::setup_actions();

    }

    function setup_globals() {
    }

    function includes(){
        
    }

    function setup_actions(){

        add_filter('plugin_action_links', array($this,'settings_link'), 10, 4 );    //Add settings link to plugins page
        
        //settings section
        //http://www.hudsonatwell.co/tutorials/bbpress-development-add-settings/
        add_filter('bbp_admin_get_settings_sections', array( $this, 'add_settings_section'));
        add_filter( 'bbp_admin_get_settings_fields', array( $this, 'register_settings_fields'));
        add_filter('bbp_map_settings_meta_caps', array( $this, 'settings_map_cap') , 10, 4);
        
        //add_action( 'admin_enqueue_scripts',  array( $this, 'scripts_styles' ) );

        add_filter('manage_posts_columns', array(&$this,'post_column_register'), 5);
        
        foreach ( (array)bbpvotes()->supported_post_types as $post_type ){
            add_action("manage_".$post_type."_posts_custom_column" , array(&$this,'post_column_content'), 10, 2 );
            add_filter( 'manage_edit-'.$post_type.'_sortable_columns', array(&$this,'post_column_sortable') );
        }

    }

    /*
     * Scripts for backend
     */
    public function scripts_styles($hook) {
        if( ( !in_array(get_post_type(),bbpvotes()->supported_post_types) ) && ($hook != 'playlist_page_bbpvotes-options') ) return;
        wp_enqueue_style( 'bbpvotes-admin', bbpvotes()->plugin_url .'_inc/css/admin.css', array(), bbpvotes()->version );
    }
    
    //Add settings link on plugin page
    function settings_link($links, $file) {
        
        //make sure it is our plugin we are modifying
        if ( $file != bbpvotes()->basename ) return $links;

        return array_merge( $links, array(
            'settings' => '<a href="'.admin_url('options-general.php?page=bbpress').'">'.__('Settings').'</a>'
            )
        );

        return $links;
    }
    
    function add_settings_section($sections){
        $sections['bbpvotes_settings'] = array(
            'title'    => __( 'bbPress Votes', 'bbpvotes' ),
            'callback' => array(&$this,'settings_section_header'),
            'page'     => 'discussion'
        );

        return $sections;
    }
    
    function settings_section_header(){
        ?>
        <p><?php esc_html_e( 'Settings for bbPress Votes', 'bbpvotes' ); ?></p>
        <?php
    }
    
    function register_settings_fields($settings){

        $settings['bbpvotes_settings'] = array(
            '_bbpvotes_vote_down_enabled' => array(
                'title'             => __( 'Enable downvoting', 'bbpvotes' ),
                'callback'          => array(&$this,'setting_enable_downvoting'),
                'sanitize_callback' => 'intval',
                'args'              => array()
            ),
            '_bbpvotes_unvote_enabled' => array(
                'title'             => __( 'Enable unvoting', 'bbpvotes' ),
                'callback'          => array(&$this,'setting_enable_unvoting'),
                'sanitize_callback' => 'intval',
                'args'              => array()
            ),
            '_bbpvotes_anonymous_vote' => array(
                'title'             => __( 'Anonymous voting', 'bbptl' ),
                'callback'          => array(&$this,'setting_anonymous_vote'),
                'sanitize_callback' => 'intval',
                'args'              => array()
            ),
        );

        return $settings;
    }
    
    //capability needed to show those settings
    function settings_map_cap( $caps, $cap, $user_id, $args ){
            if ($cap=='bbpvotes_settings')
                    $caps = array( bbpress()->admin->minimum_capability );

            return $caps;
    }

    
    function setting_enable_downvoting(){
        $option = bbpvotes()->options['vote_down_enabled'];
        $checked = checked( (bool)$option, true, false );

        printf(
            '<input type="checkbox" name="%1$s" value="1" %2$s/> <label for="%1$s">%3$s</label>',
            '_bbpvotes_vote_down_enabled',
            $checked,
            __( 'Allow users to vote down', 'bbpvotes' )
        );
    }
    
    function setting_enable_unvoting(){
        $option = bbpvotes()->options['unvote_enabled'];
        $checked = checked( (bool)$option, true, false );

        printf(
            '<input type="checkbox" name="%1$s" value="1" %2$s/> <label for="%1$s">%3$s</label>',
            '_bbpvotes_unvote_enabled',
            $checked,
            __( 'Allow users to cancel their vote', 'bbpvotes' )
        );
    }
    
    function setting_anonymous_vote(){
        $option = bbpvotes()->options['anonymous_vote'];
        $checked = checked( (bool)$option, true, false );

        printf(
            '<input type="checkbox" name="%1$s" value="1" %2$s/> <label for="%1$s">%3$s</label>',
            '_bbpvotes_anonymous_vote',
            $checked,
            __( "Hide voters identity from the vote log", 'bbpvotes' )
        );
    }
    

    function post_column_register($defaults){
        
        $post_type = get_post_type();

        if ( !in_array($post_type,bbpvotes()->supported_post_types) ) return $defaults;
        
        //split at title
        
        $before = array();
        $after = array();
        
        $after[$this->column_name_score] = __('Score','bbpvotes');
        
        $defaults = array_merge($before,$defaults,$after);

        return $defaults;
    }
    function post_column_content($column_name, $post_id){
        
        $post_type = get_post_type();

        if ( !in_array($post_type,bbpvotes()->supported_post_types) ) return;
        
        $output = '';
        
        switch ($column_name){
            
            //score
            case $this->column_name_score:

                if ($score = bbpvotes_get_votes_score_for_post($post_id)){
                    $output = $score;
                }
                
            break;
        
        
        }

        echo $output;
    }
    
    function post_column_sortable( $columns ){
        $columns[ $this->column_name_score ] = __('Score','bbpvotes');
        return $columns;
    }

}

new bbP_Votes_Admin();

?>
