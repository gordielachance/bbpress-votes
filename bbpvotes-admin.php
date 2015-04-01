<?php

class bbP_Votes_Admin {

    function __construct() {

        self::setup_globals();
        self::includes();
        self::setup_actions();

    }

    function setup_globals() {
        $this->column_name_dynamic = 'xspfpl_dynamic';
        $this->column_name_health = 'xspfpl_health';
        $this->column_name_last_track = 'xspfpl_last_track';
        $this->column_name_loads = 'xspfpl_loads';
    }

    function includes(){
        
    }

    function setup_actions(){

        add_filter('plugin_action_links', array($this,'settings_link'), 10, 4 );    //Add settings link to plugins page
        
        add_action( 'admin_enqueue_scripts',  array( $this, 'scripts_styles' ) );
        add_filter('manage_posts_columns', array(&$this,'post_column_register'), 5);
        add_action('manage_posts_custom_column', array(&$this,'post_column_content'), 5, 2);

    }

    /*
     * Scripts for backend
     */
    public function scripts_styles($hook) {
        if( ( !in_array(get_post_type(),bbpvotes()->supported_post_types) ) && ($hook != 'playlist_page_xspfpl-options') ) return;
        wp_enqueue_style( 'bbpvotes-admin', xspfpl()->plugin_url .'_inc/css/admin.css', array(), bbpvotes()->version );
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
        
        $after[$this->column_name_last_track] = __('Last track','xspfpl');
        $after[$this->column_name_health] = __('Live','xspfpl');
        $after[$this->column_name_loads] = __('Requests','xspfpl');
        $after[$this->column_name_dynamic] = '';
        
        $defaults = array_merge($before,$defaults,$after);
        
        return $defaults;
    }
    function post_column_content($column_name, $post_id){
        
        $post_type = get_post_type();

        if ( !in_array($post_type,bbpvotes()->supported_post_types) ) return;
        
        $output = '';
        
        switch ($column_name){
            
            //health
            case $this->column_name_health:
                $percentage = xspfpl_get_health($post_id);
                if ($percentage === false){

                }else{
                    $output = sprintf('%d %%',$percentage);
                }
            break;
            
            //last track
            case $this->column_name_last_track:

                if ($last_track = xspfpl_get_last_cached_track($post_id)){
                    $output = $last_track;
                }
                
            break;
            
            //loaded
            case $this->column_name_loads:
                $output = xspfpl_get_xspf_request_count($post_id);
            break;
        
            //dynamic icon
            case $this->column_name_dynamic:
                $is_static = XSPFPL_Single_Playlist::get_option('is_static');
                if (!$is_static){
                    $output = '<div class="dashicons dashicons-rss"></div>';
                }else{
                    $output = '<div class="dashicons dashicons-rss is-static"></div>';
                }
            break;
        
        
        }
        
        echo $output;
    }

}

new bbP_Votes_Admin();

?>
