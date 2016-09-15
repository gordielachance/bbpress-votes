<?php

class bbP_Votes_BuddyPress {
    function __construct(){
        add_action( 'bp_register_activity_actions',array(&$this,'register_activity_actions'));
        add_action( 'bbpvotes_do_post_vote',array(&$this,'voted_activity'),10,3);
        add_action( 'bp_setup_nav', array(&$this,'register_karma_nav') );
        add_action( 'bbp_template_before_user_replies', array(&$this,'before_karma_replies') );
    }
    
    function register_karma_nav(){
        global $bp;
        
        $karma = 10;
        $parent_slug = BP_FORUMS_SLUG;
        
        $karma = bbpvotes_get_author_score( bbp_get_reply_author_id() );
        $karma_text = bbpvotes_get_score_text($karma);

        bp_core_new_subnav_item( 
            
            array(
                'name'            => sprintf( __( 'Karma <span>%d</span>', 'bbpvotes' ), $karma_text ),
                'slug'            => 'bbpvotes_karma',
                'parent_url'      => trailingslashit( bp_displayed_user_domain() . $parent_slug ),
                'parent_slug'     => $parent_slug,
                'screen_function' => array(&$this,'karma_screen'),
                //'user_has_access'   => true,
                'item_css_id'       => 'bbpvotes-karma'
            )
        );
        
    }
    
    function karma_screen(){
        add_action( 'bp_template_content', array(&$this,'karma_replies_content') );
        bp_core_load_template( apply_filters( 'bbp_member_forums_screen_favorites', 'members/single/plugins' ) );
    }
    
    function karma_replies_content() {
    ?>

        <div id="bbpress-forums">

            <?php bbp_get_template_part( 'user', 'replies-created' ); ?>

        </div>

    <?php
    }
    
    function before_karma_replies(){
        add_filter( 'bbp_has_replies_query', array(&$this,'filter_karma_replies') );
    }
    
    function filter_karma_replies($args){
        $args[bbpvotes()->var_sort_by_vote] = 'score_desc';
        return $args;
    }
    
    function register_activity_actions() {
        // Sitewide activity stream items
        bp_activity_set_action( 'bbpress', 'bbpvotes_voted_up', esc_html__( 'New vote up', 'bbpvotes' ) );
        bp_activity_set_action( 'bbpress', 'bbpvotes_voted_down', esc_html__( 'New vote down', 'bbpvotes' ) );
    }

    function voted_activity($post_id,$user_id,$vote){

        //check vote value
        if (is_bool($vote) === false) return new WP_Error( 'vote_is_not_bool', __( 'Vote is not a boolean', 'bbpvotes' ));
        $voteplus = ($vote === true);
        $voteminus = ($vote === false);

        $post = get_post($post_id);
        $user_link = bbp_get_user_profile_link( $user_id  );

        //build item link
        if ($post->post_type == bbp_get_topic_post_type()){

            $topic_id = $post->ID;
            $post_permalink = get_permalink( $post->ID );

        }elseif ($post->post_type == bbp_get_reply_post_type()){

            $topic_id   = bbp_get_reply_topic_id( $post->ID );
            $post_permalink = bbp_get_reply_url( $post->ID );

        }

        //topic infos
        $topic = get_post($topic_id);
        $topic_author_link = bbp_get_user_profile_link( $topic->post_author  );
        $topic_title     = $topic->post_title;

        $post_link      = '<a href="' . $post_permalink . '">' . $topic_title . '</a>';

        if ($voteplus){

            $type       = 'bbpvotes_voted_up';

            if ($post->post_type == bbp_get_topic_post_type()){
                $action     = sprintf( esc_html__( '%1$s voted up to the topic %2$s by %3$s', 'bbpress' ), $user_link, $post_link, $topic_author_link );
            }elseif ($post->post_type == bbp_get_reply_post_type()){
                $action     = sprintf( esc_html__( '%1$s voted up to a reply by %3$s in the topic %2$s', 'bbpress' ), $user_link, $post_link, $topic_author_link );
            }


        }else{

            $type       = 'bbpvotes_voted_down';

            if ($post->post_type == bbp_get_topic_post_type()){
                $action     = sprintf( esc_html__( '%1$s voted down to the topic %2$s by %3$s', 'bbpress' ), $user_link, $post_link, $topic_author_link );
            }elseif ($post->post_type == bbp_get_reply_post_type()){
                $action     = sprintf( esc_html__( '%1$s voted down to a reply by %3$s in the topic %2$s', 'bbpress' ), $user_link, $post_link, $topic_author_link );
            }

        }

        $args = array(
            'action'    => $action,
            //'content'   => bp_create_excerpt($post->post_content),
            'component' => 'bbpress',
            'type'      => $type,
            'item_id'   => $topic->ID,
        );

        if ($post->post_type == bbp_get_reply_post_type()){
            $args['secondary_item_id'] = $post->ID;
        }

        /*
        if ($is_update){
            $previous_activity_id = 
            $args['id'] = $previous_activity_id;
        }
         */

        bp_activity_add( $args );
    }
}

new bbP_Votes_BuddyPress;