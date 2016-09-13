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

        add_action('bbp_init', array($this, 'handle_post_columns') );
        
        add_filter( 'plugin_action_links_' . bbpvotes()->basename, array($this, 'plugin_bottom_links')); //bottom links

        //add_action( 'admin_enqueue_scripts',  array( $this, 'scripts_styles' ) );

    }
    
    function plugin_bottom_links($links){
        
        $links[] = sprintf('<a target="_blank" href="%s">%s</a>',bbpvotes()->donate_link,__('Donate','bbppu'));//donate

        if (current_user_can('manage_options')) {
            $settings_page_url = add_query_arg(
                array(
                    'page'=>bbP_Votes_Settings::$menu_slug
                ),
                get_admin_url(null, 'options-general.php')
            );
            $links[] = sprintf('<a href="%s">%s</a>',esc_url($settings_page_url),__('Settings'));
        }

        return $links;
    }
    
    //TO FIX NOT WORKING
    function handle_post_columns(){
        
        foreach ( (array)bbpvotes_get_enabled_post_types() as $post_type ){
            add_filter("manage_".$post_type."_posts_columns", array(&$this,'post_column_register'), 5);
            add_action("manage_".$post_type."_posts_custom_column" , array(&$this,'post_column_content'), 10, 2 );
            add_filter("manage_edit-".$post_type."_sortable_columns", array(&$this,'post_column_sortable') );
        }
        
    }

    /*
     * Scripts for backend
     */
    public function scripts_styles($hook) {
        if( ( !in_array(get_post_type(),bbpvotes_get_enabled_post_types()) ) && ($hook != 'playlist_page_bbpvotes-options') ) return;
        wp_enqueue_style( 'bbpvotes-admin', bbpvotes()->plugin_url .'_inc/css/admin.css', array(), bbpvotes()->version );
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
