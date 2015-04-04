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

        //add_filter('plugin_action_links', array($this,'settings_link'), 10, 4 );    //Add settings link to plugins page
        
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
        
        $settings_link = '<a href="'.admin_url('options-general.php?page=bbpress').'">'.__('Settings','bbpvotes').'</a>';
        array_push($links, $settings_link);

        return $links;
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
