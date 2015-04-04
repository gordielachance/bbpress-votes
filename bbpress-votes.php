<?php
/*
Plugin Name: bbPress Votes
Plugin URI: http://wordpress.org/extend/plugins/bbpress-pencil-unread
Description: Allow users to vote up or down to topics and replies inside bbPress, just like you can on StackOverflow for example.
Author: G.Breant
Version: 1.0.1
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
	public $version = '1.0.1';
        
	/**
	 * @public string plugin DB version
	 */
	public $db_version = '100';
	
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
	public $metaname_post_vote_up = 'bbpvotes_vote_up';
        public $metaname_post_vote_down = 'bbpvotes_vote_down';
        public $metaname_options = 'bbpvotes_options';
        
        public $supported_post_types;
        
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
                
                //options
                $this->options_default = array(
                    'vote_up_cap'       => 'read',  //capability required to vote up
                    'vote_down_cap'     => 'read',  //capability required to vote down
                    'embed_links'       => true,    //embed score, vote up, vote down links above replies
                    'embed_votes_log'   => true,    //embed vote log after replies content

                );
                $options = get_option( $this->metaname_options, $this->options_default );
                $this->options = apply_filters('bbpvotes_options',$options);

	}
        
	function includes(){
            require( $this->plugin_dir . 'bbpvotes-template.php');
            require( $this->plugin_dir . 'bbpvotes-ajax.php');
            require( $this->plugin_dir . 'bbpvotes-buddypress.php');
            if (is_admin()){
                require( $this->plugin_dir . 'bbpvotes-admin.php');
            }
	}
	
	function setup_actions(){
            
            /*actions are hooked on bbp hooks so plugin will not crash if bbpress is not enabled*/

            add_action('bbp_init', array($this, 'load_plugin_textdomain'));     //localization
            add_action('bbp_loaded', array($this, 'upgrade'));                  //upgrade

            //scripts & styles
            add_action('bbp_init', array($this, 'register_scripts_styles'));
            add_action('bbp_enqueue_scripts', array($this, 'scripts_styles'));
            
            add_action('bbp_init', array($this, 'populate_post_types'));

            add_filter( 'bbp_topic_admin_links', array($this, 'vote_admin_link'), 10, 2);
            add_filter( 'bbp_reply_admin_links', array($this, 'vote_admin_link'), 10, 2);
            
            add_filter( 'bbp_get_reply_content', array($this, 'post_content_append_votes_log'),  98,  2 );
            add_filter( 'bbp_get_topic_content', array($this, 'post_content_append_votes_log'),  98,  2 );
            
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
            $localize_vars['vote_up']=__( 'Vote up',   'bbpvotes' );
            $localize_vars['vote_down']=__( 'Vote down',   'bbpvotes' );
            $localize_vars['you_voted_up']=__( 'You voted up',   'bbpvotes' );
            $localize_vars['you_voted_down']=__( 'You voted down',   'bbpvotes' );
            wp_localize_script('bbpvotes','bbpvotesL10n', $localize_vars);

            //STYLES
            wp_enqueue_style('bbpvotes');
            wp_enqueue_style('font-awesome');
	}

        function populate_post_types(){
            $this->supported_post_types = array(
                bbp_get_topic_post_type(),
                bbp_get_reply_post_type()
            );
        }
        
        function vote_admin_link($links, $post_id){
            
            if (!$this->options['embed_links']) return $links;

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
            $user_voted_up = bbpvotes_has_user_voted_up_for_post($post->ID,$user_id);
            $user_voted_down = bbpvotes_has_user_voted_down_for_post($post->ID,$user_id);
            
            //already voted the same way
            if ( ($voteplus && $user_voted_up) || ($voteminus && $user_voted_down) ){
                return new WP_Error( 'already_voted', __( 'You have already voted for this post', 'bbpvotes' ));
            }
            
            //toggle vote so remove old vote
            if ( ($voteplus && $user_voted_down) || ($voteminus && $user_voted_up) ){
                $previousvote = $this->metaname_post_vote_down; //negative key
                if ($voteminus) $previousvote = $this->metaname_post_vote_up; //positive key
                delete_post_meta($post->ID, $previousvote, $user_id);
            }
            
            //insert new vote
            $newvote = $this->metaname_post_vote_up; //positive key
            if ($voteminus) $newvote = $this->metaname_post_vote_down; //negative key
            if ( !$result = add_post_meta($post->ID, $newvote, $user_id) ){
                return new WP_Error( 'voting_error', __( 'Error while voting for this post', 'bbpvotes' ));
            }
            
            do_action('bbpvotes_do_post_vote',$post->ID,$user_id,$vote);
            
            return true;
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
            
            if (in_array( get_post_type( $post_id ),$this->supported_post_types ) ){ //single forum
                    if( !wp_verify_nonce( $_GET['_wpnonce'], $nonce ) ) return false;
                    self::do_post_vote($post_id,$vote);
            }
	}
        
        function delete_user_votes( $user_id ) {
            global $wpdb;
            $wpdb->delete($wpdb->postmeta, array( 'meta_key' => $this->metaname_post_vote_up, 'meta_value' => $user_id ), array('%s', '%d'));
            $wpdb->delete($wpdb->postmeta, array( 'meta_key' => $this->metaname_post_vote_down, 'meta_value' => $user_id ), array('%s', '%d'));
        }
        
        function post_content_append_votes_log( $content = '', $post_id = 0 ) {
            
            if (!$this->options['embed_votes_log']) return $content;
            
            if ( is_admin() || is_feed() ) return $content; // Bail if in admin or feed
            if (!in_array( get_post_type( $post_id ),$this->supported_post_types ) ) return $content;

            // Validate the ID
            //$topic_id = bbp_get_topic_id( $topic_id );

            return apply_filters( 'bbpvotes_post_append_votes', $content . bbpvotes_get_post_votes_log($post_id), $content, $post_id );
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
