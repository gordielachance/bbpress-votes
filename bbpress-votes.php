<?php
/*
Plugin Name: bbPress Votes
Plugin URI: http://wordpress.org/extend/plugins/bbpress-pencil-unread
Description: Allow users to vote up or down to topics and replies inside bbPress, just like you can on StackOverflow for example.
Author: G.Breant
Version: 1.2.0
Author URI: http://sandbox.pencil2d.org/
License: GPL2+
Text Domain: bbpvotes
Domain Path: /languages/
*/

class bbP_Votes {
	/** Version ***************************************************************/
	
        /**
	 * @public string plugin version
	 */
	public $version = '1.2.0';
        
	/**
	 * @public string plugin DB version
	 */
	public $db_version = '103';
	
	/** Paths *****************************************************************/
	
        public $file = '';
	
	/**
	 * @public string Basename of the plugin directory
	 */
	public $basename = '';
	/**
	 * @public string Absolute path to the plugin directory
	 */
	public $plugin_dir = '';
        
	/**
	 * @public meta name for post votes
	 */
    public $metaname_options = 'bbpvotes_options'; // plugin's options (stored in wp_options) 
    public $metaname_post_vote_score = 'bbpvotes_vote_score';
    public $metaname_post_vote_count = 'bbpvotes_vote_count';
    public $metaname_post_vote_up = 'bbpvotes_vote_up';
    public $metaname_post_vote_down = 'bbpvotes_vote_down';

    public $var_sort_by_vote = 'vote_sort';

    public $donate_link = 'http://bit.ly/gbreant';
    
        
	/**
	 * @var The one true Instance
	 */
	private static $instance;
        
	/**
	 * Main Instance
	 *
	 * @see bbpress_pencil_unread()
	 * @return The one Instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new bbP_Votes;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}
        
	/**
	 * A dummy constructor to prevent from being loaded more than once.
	 *
	 */
	private function __construct() { /* Do nothing here */ }
        
	function setup_globals() {
		/** Paths *************************************************************/
		$this->file       = __FILE__;
		$this->basename   = plugin_basename( $this->file );
		$this->plugin_dir = plugin_dir_path( $this->file );
		$this->plugin_url = plugin_dir_url ( $this->file );
        
        $this->options_default = array(
                    'ignored_post_type'     => array(),
                    'vote_down_enabled'     => 'on',
                    'unvote_enabled'        => 'on',
                    'embed_links'           => 'on',    //embed score, vote up, vote down links above replies
                    'embed_votes_log'       => 'on',    //embed vote log below replies content
                    'anonymous_vote'        => 'off',     //hides voters identity from the vote log
                    'unit_singular'         => '%s pt',
                    'unit_plural'           => '%s pts',
                    'vote_up_cap'           => 'read',  //capability required to vote up
                    'vote_down_cap'         => 'read',  //capability required to vote down
                    'unvote_cap'            => 'read',
                    'karma_cache_minutes'   => 60, //how many minutes do we cache a user's karma ?    

                );
        
        $options = wp_parse_args(get_option( $this->metaname_options), $this->options_default);
        $this->options = apply_filters('bbpvotes_options',$options);

	}
        
	function includes(){
            require( $this->plugin_dir . 'bbpvotes-functions.php');
            require( $this->plugin_dir . 'bbpvotes-template.php');
            require( $this->plugin_dir . 'bbpvotes-ajax.php');
            
            if (is_admin()){
                require( $this->plugin_dir . 'bbpvotes-admin.php');
            }
            
	}

	function includes_buddypress(){
            require( $this->plugin_dir . 'bbpvotes-buddypress.php');
	}
	
	function setup_actions(){
            
            /*actions are hooked on bbp hooks so plugin will not crash if bbpress is not enabled*/

            add_action('bbp_loaded', array($this, 'load_plugin_textdomain'));     //localization
            add_filter('query_vars', array(&$this,'register_query_vars' ));

            //scripts & styles
            add_action('bbp_init', array($this, 'register_scripts_styles'));
            add_action('bbp_init', array($this, 'upgrade'));                  //upgrade
            
            add_action('bbp_enqueue_scripts', array($this, 'scripts_styles'));

            add_filter( 'bbp_topic_admin_links', array($this, 'vote_admin_link'), 10, 2);
            add_filter( 'bbp_reply_admin_links', array($this, 'vote_admin_link'), 10, 2);
            
            add_filter( 'bbp_get_reply_content', array($this, 'post_content_append_votes_log'),  98,  2 );
            add_filter( 'bbp_get_topic_content', array($this, 'post_content_append_votes_log'),  98,  2 );
            
            add_action( 'bbp_theme_after_reply_author_details', array($this, 'display_reply_author_karma'));
            add_action( 'bbp_theme_after_topic_started_by', array($this, 'display_topic_score'));
            add_action( 'bbp_template_before_topics_loop', array($this, 'topics_loop_sort_link'),9);
            
            add_action( 'bp_include', array($this, 'includes_buddypress'));     //buddypress
            
            add_action( 'pre_get_posts', array($this, 'sort_by_votes'));
            
            add_action("wp", array(&$this,"process_vote_link"));    //vote without ajax
            
            add_action( 'delete_user', array(&$this,"delete_user_votes"));

	}

	function load_plugin_textdomain(){
		load_plugin_textdomain('bbpvotes', FALSE, $this->plugin_dir.'languages/');
	}
  
	function upgrade(){
		global $wpdb;
		
		$version_db_key = 'bbpvotes-db-version';
		$current_version = get_option($version_db_key);
		if ($current_version==$this->db_version) return false;
			
		if(!$current_version){  //install
			//require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			//dbDelta($sql);
		}else{  //upgrade
                    if ( $current_version < 101 ) {
                        bbpvotes_rebuild_scores();
                    }
                }

		//update DB version
		update_option($version_db_key, $this->db_version );
	}

        
	function register_scripts_styles(){
            wp_register_style('bbpvotes', $this->plugin_url . '_inc/css/bbpvotes.css',false,$this->version);
            wp_register_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css',false,'4.3.0');
            wp_register_script('bbpvotes', $this->plugin_url . '_inc/js/bbpvotes.js',array('jquery'),$this->version);
	}
	function scripts_styles(){
	
            //SCRIPTS

            wp_enqueue_script('bbpvotes');

            //localize vars
            $localize_vars=array();
            $localize_vars['ajaxurl']=admin_url( 'admin-ajax.php' );
            $localize_vars['vote_up']=__( 'Vote up',   'bbpvotes' );
            $localize_vars['vote_down']=__( 'Vote down',   'bbpvotes' );
            $localize_vars['you_voted_up']=__( 'You voted up',   'bbpvotes' );
            $localize_vars['you_voted_down']=__( 'You voted down',   'bbpvotes' );
            wp_localize_script('bbpvotes','bbpvotesL10n', $localize_vars);

            //STYLES
            wp_enqueue_style('bbpvotes');
            wp_enqueue_style('font-awesome');
	}
        
    function register_query_vars($vars) {
        $vars[] = $this->var_sort_by_vote;
        return $vars;
    }

    function vote_admin_link($links, $post_id){

        if ( bbpvotes()->get_options('embed_links') != 'on' ) return $links;
        
        //is this post type enabled ?
        if ( !in_array(get_post_type($post_id),bbpvotes_get_enabled_post_types()) ) return $links;

        $args = array();

        // Parse arguments against default values
        $r = bbp_parse_args( $args, array (
                'id'     => $post_id,
                'before' => '<span class="bbp-admin-links">',
                'after'  => '</span>',
                'sep'    => ' | ',
                'links'  => array()
        ), 'get_topic_admin_links' );

        $vote_links = array(
            'score' => bbpvotes_get_score_link( $r ),
            'vote_up' => bbpvotes_get_vote_up_link( $r ),
            'vote_down' => bbpvotes_get_vote_down_link( $r )
        );

        $vote_links = array_filter($vote_links);

        return array_merge($vote_links,$links);
    }

    function display_reply_author_karma(){
        $score = bbpvotes_get_author_score( bbp_get_reply_author_id() );

        if (!$score) return;

        $text = bbpvotes_get_score_text($score);
        $label_text = __('Karma:','bbpvotes');
        $score_el = sprintf('<span>%s</span>',$text);

        printf( '<div class="bbpvotes-score-wrapper bbpvotes-score-author-wrapper" alt="%1$s"><label>%1$s</label> %2$s</div>',$label_text,$score_el );
    }

    /*
    In a list of topics, display the topic scores
    */
    function display_topic_score(){
        if (!$score = bbpvotes_get_votes_for_post( bbp_get_topic_id() )) return;

        $text = bbpvotes_get_score_text($score);
        $label_text = __('Score:','bbpvotes');
        $score_el = sprintf('<span>%s</span>',$text);

        printf( '<span class="bbpvotes-score-wrapper bbpvotes-score-topic-wrapper" alt="%1$s"><label>%1$s</label> %2$s</span>',$label_text,$score_el );
    }

    function topics_loop_sort_link(){
        global $wp_query;
        $link = get_permalink();

        $query_var = $wp_query->get( $this->var_sort_by_vote );


        if (!$query_var){
            $link = add_query_arg(array($this->var_sort_by_vote => 'score_desc'),$link);
            $text = __('Sort topics by votes','bbpvotes');
        }else{
            $text = __('Sort topics by date','bbpvotes');
        }

        printf('<a href="%1$s" class="bbpvotes-forum-sort-topics">%2$s</a>',$link,$text);
    }

    /**
     * 
     * @param type $post_id
     * @param type $vote MUST BE defined, MUST BE a boolean
     * @return boolean|\WP_Error
     */

    function do_post_vote( $post_id, $vote = null ){

        //check vote value
        if (is_bool($vote) === false) return new WP_Error( 'vote_is_not_bool', __( 'Vote is not a boolean', 'bbpvotes' ));
        $voteplus = ($vote === true);
        $voteminus = ($vote === false);

        if (!$post = get_post( $post_id )) return false;

        if ( ($voteplus && !bbpvotes_can_user_vote_up_for_post($post->ID)) || ($voteminus && !bbpvotes_can_user_vote_down_for_post($post->ID)) ){
            return new WP_Error( 'missing_capability', __( "You don't have the required capability to vote", 'bbpvotes' ));
        }

        $user_id = get_current_user_id();


        //check user is not post author
        if ($post->post_author == $user_id){
            return new WP_Error( 'user_is_author', __( 'You cannot vote for your own post', 'bbpvotes' ));
        }

        //get current votes
        $post_score = bbpvotes_get_votes_score_for_post( $post->ID );
        $votes_count = bbpvotes_get_votes_count_for_post( $post->ID );
        $is_previous_vote_up = bbpvotes_has_user_voted_up_for_post($post->ID,$user_id);
        $is_previous_vote_down = bbpvotes_has_user_voted_down_for_post($post->ID,$user_id);
        $toggle_vote = ( ($voteplus && $is_previous_vote_down) || ($voteminus && $is_previous_vote_up) );
        $unvote = ( ($voteplus && $is_previous_vote_up) || ($voteminus && $is_previous_vote_down) );

        //remove old vote first if any
        if ( $toggle_vote || $unvote ){

            //get previous vote meta key
            if ($is_previous_vote_down){
                $meta_previous_vote = $this->metaname_post_vote_down;
            }else{
                $meta_previous_vote = $this->metaname_post_vote_up;
            }

            if ( $removed_previous_vote = delete_post_meta($post->ID, $meta_previous_vote, $user_id) ){ //successfully deleted

                $votes_count--;

                //restore score
                if ($is_previous_vote_down){
                    $post_score++;
                }else{
                    $post_score--;
                }

            }
        }

        if ( $unvote ){ //vote duplicate : remove or block it

            if (bbpvotes()->get_options('unvote_enabled') == 'on'){ //remove vote

                if ( !bbpvotes_can_user_unvote_for_post($post->ID) ){

                    return new WP_Error( 'missing_capability', __( "You don't have the required capability to unvote", 'bbpvotes' ));

                }else{

                    if ($removed_previous_vote){
                        update_post_meta($post->ID, $this->metaname_post_vote_score, $post_score);
                        update_post_meta($post->ID, $this->metaname_post_vote_count, $votes_count);
                        do_action('bbpvotes_do_post_unvote',$post->ID,$user_id);
                    }else{
                        return new WP_Error( 'unvoting_error', __( 'Error while unvoting for this post', 'bbpvotes' ));
                    }


                }
            }else{ //block vote
                return new WP_Error( 'already_voted', __( 'You have already voted for this post', 'bbpvotes' ));
            }

        }else{ //process vote
            //get new vote meta key
            if ( $voteplus ){
                $meta_vote = $this->metaname_post_vote_up;
            }else{
                $meta_vote = $this->metaname_post_vote_down;
            }

            if ( $result = add_post_meta($post->ID, $meta_vote, $user_id) ){

                $votes_count++;

                //update score
                if ($voteplus){
                    $post_score++;
                }else{
                    $post_score--;
                }

                update_post_meta($post->ID, $this->metaname_post_vote_score, $post_score);
                update_post_meta($post->ID, $this->metaname_post_vote_count, $votes_count);

            }else{
                return new WP_Error( 'voting_error', __( 'Error while voting for this post', 'bbpvotes' ));
            }

            do_action('bbpvotes_do_post_vote',$post->ID,$user_id,$vote);

        }

        return true;
    }

    function sort_by_votes( $query ){
        global $wp_query;

        $query_var = $query->get( $this->var_sort_by_vote ); //should be this ?
        //$query_var = $wp_query->get( $this->var_sort_by_vote );

        if ( ( !$order = $query_var ) || !in_array($query->get('post_type'),bbpvotes_get_enabled_post_types()) ) return $query;

        switch ($order){

            case 'score_desc':

                $query->set('meta_key', $this->metaname_post_vote_score );
                $query->set('orderby','meta_value_num');
                $query->set('order', 'DESC');

            break;

            case 'score_asc':
                $query->set('meta_key', $this->metaname_post_vote_score );
                $query->set('orderby','meta_value_num');
                $query->set('order', 'ASC');
            break;

            case 'count_desc':

                $query->set('meta_key', $this->metaname_post_vote_count );
                $query->set('orderby','meta_value_num');
                $query->set('order', 'DESC');

            break;

            case 'count_asc':
                $query->set('meta_key', $this->metaname_post_vote_count );
                $query->set('orderby','meta_value_num');
                $query->set('order', 'ASC');
            break;

        }

        return $query;
    }
        
	//vote without ajax
	public function process_vote_link() {

            if( !isset( $_GET['action'] ) || (!in_array($_GET['action'],array('bbpvotes_post_vote_up','bbpvotes_post_vote_down'))) )return false;

            if (!$post_id = $_GET['post_id']) return false;
            
            $vote = null;
            
            switch ($_GET['action']){
                case 'bbpvotes_post_vote_up':
                    $nonce = 'vote-up-post_'.$post_id;
                    $vote = true;
                break;
                case 'bbpvotes_post_vote_down':
                    $nonce = 'vote-down-post_'.$post_id;
                    $vote = false;
                break;
            };
            
            if (in_array( get_post_type( $post_id ),bbpvotes_get_enabled_post_types() ) ){ //single forum
                    if( !wp_verify_nonce( $_GET['_wpnonce'], $nonce ) ) return false;
                    self::do_post_vote($post_id,$vote);
            }
	}
  
        function delete_user_votes( $user_id ) {
            
            self::delete_user_votes_type( $user_id, true);
            self::delete_user_votes_type( $user_id, false);
            
        }
        
       /**
         * Delete user votes by vote type (up or down) and update post score.
         * @param type $user_id
         * @param type $up
         */

        
        function delete_user_votes_type( $user_id, $up = true){

            //get individual posts
            
            if ($up){ //up votes
                $meta_name = $this->metaname_post_vote_up;
            }else{ //down votes
                $meta_name = $this->metaname_post_vote_down;
            }
            
            $user_votes_args = array(
                'post_type'         => bbpvotes_get_enabled_post_types(),
                'post_status'   => 'any',
                'posts_per_page'    => -1,
                'fields'            => 'ids',
                'meta_query'        => array(
                    array(
                        'key'   => $meta_name,
                        'value'	=> $user_id
                    )
                )
            );
            $user_voted_query = new WP_Query( $user_votes_args );

            foreach ($user_voted_query->posts as $id){
                
                $post_score = bbpvotes_get_votes_score_for_post( $id );
                $votes_count = bbpvotes_get_votes_count_for_post( $id );
                
                if ($up){ //up votes
                    $post_score--;
                }else{ //down votes
                    $post_score++;
                }
                
                $votes_count--;
                
                if ( delete_post_meta($id, $meta_name, $user_id) ){
                    update_post_meta($id, $this->metaname_post_vote_score, $post_score);
                    update_post_meta($id, $this->metaname_post_vote_count, $votes_count);
                }

            }

        }
        
        function post_content_append_votes_log( $content = '', $post_id = 0 ) {
            
            if ( bbpvotes()->get_options('embed_votes_log') != 'on' ) return $content;
            
            if ( is_admin() || is_feed() ) return $content; // Bail if in admin or feed
            if (!in_array( get_post_type( $post_id ),bbpvotes_get_enabled_post_types() ) ) return $content;

            // Validate the ID
            //$topic_id = bbp_get_topic_id( $topic_id );

            return apply_filters( 'bbpvotes_post_append_votes', $content . bbpvotes_get_post_votes_log($post_id), $content, $post_id );
        }
    
        function get_options($keys = null){
            return bbpvotes_get_array_value($keys,$this->options);
        }

        public function get_default_option($keys = null){
            return bbpvotes_get_array_value($keys,$this->options_default);
        }

        function debug_log($message) {

            if (WP_DEBUG_LOG !== true) return false;

            $prefix = '[bbpvotes] : ';
            
            if (is_array($message) || is_object($message)) {
                error_log($prefix.print_r($message, true));
            } else {
                error_log($prefix.$message);
            }
        }
                
}
/**
 * The main function responsible for returning the one true Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 *
 * @return The one true Instance
 */
function bbpvotes() {
	return bbP_Votes::instance();
}
bbpvotes();
?>