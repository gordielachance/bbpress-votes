<?php

function bbpvotes_buddypress_register_activity_actions() {
    // Sitewide activity stream items
    bp_activity_set_action( 'bbpress', 'bbpvotes_voted_up', esc_html__( 'New vote up', 'bbpvotes' ) );
    bp_activity_set_action( 'bbpress', 'bbpvotes_voted_down', esc_html__( 'New vote down', 'bbpvotes' ) );
}

function bbpvotes_buddypress_voted_activity($post_id,$user_id,$vote){
    
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

    if(function_exists('bp_activity_add')){
        bp_activity_add( $args );
    }
}


add_action( 'bp_register_activity_actions','bbpvotes_buddypress_register_activity_actions');
add_action( 'bbpvotes_do_post_vote','bbpvotes_buddypress_voted_activity',10,3)

?>