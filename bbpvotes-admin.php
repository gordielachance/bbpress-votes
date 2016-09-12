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
        require( bbpvotes()->plugin_dir . 'bbpvotes-settings.php');
    }

    function setup_actions(){

        add_filter('plugin_action_links', array($this,'settings_link'), 10, 4 );    //Add settings link to plugins page
        
        add_action('bbp_init', array($this, 'handle_post_columns') );

        //add_action( 'admin_enqueue_scripts',  array( $this, 'scripts_styles' ) );

    }
    
    //TO FIX NOT WORKING
    function handle_post_columns(){
        
        foreach ( (array)bbpvotes()->supported_post_types as $post_type ){
            add_filter("manage_".$post_type."_posts_columns", array(&$this,'post_column_register'), 5);
            add_action("manage_".$post_type."_posts_custom_column" , array(&$this,'post_column_content'), 10, 2 );
            add_filter("manage_edit-".$post_type."_sortable_columns", array(&$this,'post_column_sortable') );
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

    function post_column_register($defaults){

        //split at title
        
        $before = array();
        $after = array();
        
        $after[$this->column_name_score] = __('Score','bbpvotes');
        
        $defaults = array_merge($before,$defaults,$after);

        return $defaults;
    }
    function post_column_content($column_name, $post_id){

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
