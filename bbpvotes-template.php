<?php

function bbpvotes_get_score_text($score){
    $formatted_score = bbpvotes_number_format($score);
    $string = null;

    if ($score <= 1){
        $string = bbpvotes()->get_options('unit_singular');
    }else{
        $string = bbpvotes()->get_options('unit_plural');
    }

    $retval = str_replace('%s',$formatted_score,$string);

    return apply_filters('bbpvotes_get_score_text',$retval,$score);

}

function bbpvotes_get_score_link( $args = '' ) {

        // Parse arguments against default values
        $r = bbp_parse_args( $args, array(
                'id'           => 0,
                'link_before'  => '',
                'link_after'   => '',
                'sep'          => ' | ',
                'text'    => esc_html__( 'Vote up',   'bbpvotes' ),
        ), 'get_post_vote_score_link' );

        $post = get_post( (int) $r['id'] );
        $post_type = $post->post_type;
        $score = bbpvotes_get_votes_score_for_post($post->ID);
        $score_display = bbpvotes_number_format($score);
        $votes_count = bbpvotes_get_votes_count_for_post($post->ID);
        $votes_count_display = bbpvotes_number_format($score);

        $r['text'] = sprintf(__('Score: %1$s','bbpvotes'),$score_display);
        $r['title'] = sprintf(__('Votes count: %1$s','bbpvotes'),$votes_count_display);
        
        $link_classes = array(
            'bbpvotes-post-vote-link',
            'bbpvotes-post-score-link'
        
        );
        
        if ( !$votes_count ) $link_classes[] = 'bbpvotes-post-no-score';

        $retval  = $r['link_before'] . '<a href="#" title="' . $r['title'] . '"'.bbpvotes_classes_attr($link_classes).'>' . $r['text'] . '</a>' . $r['link_after'];

        return apply_filters( 'bbpvotes_get_score_link', $retval, $r );
}

function bbpvotes_get_link_icons(){
    //icons
    // $icons = array(
    //     '<i class="bbpvotes-icon bbpvotes-icon-loading fa fa-circle-o-notch fa-spin"></i>',
    //     '<i class="bbpvotes-icon bbpvotes-icon-error fa fa-exclamation-triangle"></i>',
    //     '<i class="bbpvotes-icon bbpvotes-icon-success fa fa-check"></i>',
    // );
    // return implode('',$icons);
    return "";
}

function bbpvotes_can_user_vote_up_for_post($post_id = null){
    if (!$post_id) return false;
    if (!$user_id = get_current_user_id()) return false;
    $can = current_user_can( bbpvotes()->get_options('vote_up_cap'), $post_id );
    return apply_filters('bbpvotes_can_user_vote_up_for_post',$can,$post_id);
}

function bbpvotes_can_user_vote_down_for_post($post_id = null){
    if (!$post_id) return false;
    if (!$user_id = get_current_user_id()) return false;
    if (bbpvotes()->get_options('vote_down_enabled') != 'on') return false;
    $can = current_user_can( bbpvotes()->get_options('vote_down_cap'), $post_id );
    return apply_filters('bbpvotes_can_user_vote_down_for_post',$can,$post_id);
}

function bbpvotes_can_user_unvote_for_post($post_id = null){
    if (!$post_id) return false;
    if (!$user_id = get_current_user_id()) return false;
    if (bbpvotes()->get_options('unvote_enabled') != 'on') return false;
    $can = current_user_can( bbpvotes()->get_options('unvote_cap'), $post_id );
    return apply_filters('bbpvotes_can_user_unvote_for_post',$can,$post_id);
}


function bbpvotes_get_vote_up_link( $args = '' ) {

        // Parse arguments against default values
        $r = bbp_parse_args( $args, array(
                'id'           => 0,
                'link_before'  => '',
                'link_after'   => '',
                'sep'          => ' | ',
                'text'    =>   '<i class="bbpvotes-icon bbpvotes-icon-success fa fa-thumbs-up"></i> ' . esc_html__( 'Vote up',   'bbpvotes' ),
        ), 'get_post_vote_up_link' );

        if (!$post = get_post( (int) $r['id'] )) return false;
        
        //capability check
        if (!bbpvotes_can_user_vote_up_for_post($post->ID)) return false;
        
        if ( $post->post_author == get_current_user_id() ) return false;    //user cannot vote for himself
        
        $post_type = $post->post_type;
        $link_classes = array(
            'bbpvotes-post-vote-link',
            'bbpvotes-post-voteup-link'
        );

        
        switch($post_type){
            case bbp_get_topic_post_type():
                $r['title'] = __('This topic is useful and clear','bbpvotes');
            break;
            case bbp_get_reply_post_type():
                $r['title'] = __('This reply is useful','bbpvotes');
            break;
        }

        //check if user has already voted
        if ($voted_up = bbpvotes_has_user_voted_up_for_post( $post->ID )){
            $r['text'] = esc_html__( 'You voted up',   'bbpvotes' );
            $r['title'] = esc_html__( 'You have voted up for this post',   'bbpvotes' );
            
            if ( bbpvotes_can_user_unvote_for_post($post->ID) ){
                $r['title'] .= ' - '.esc_html__( 'Click to remove your vote',   'bbpvotes' );
            }
            
            $link_classes[] = 'bbpvotes-post-voted';
        }

        $uri     = add_query_arg( array( 'action' => 'bbpvotes_post_vote_up', 'post_id' => $post->ID ) );
        $uri     = wp_nonce_url( $uri, 'vote-up-post_' . $post->ID );
        $retval  = $r['link_before'] . '<a href="' . esc_url( $uri ) . '" title="' . $r['title'] . '"'.bbpvotes_classes_attr($link_classes).'>' . bbpvotes_get_link_icons() . $r['text'] . '</a>' . $r['link_after'];

        return apply_filters( 'bbpvotes_get_vote_up_link', $retval, $r );
}

function bbpvotes_get_vote_down_link( $args = '' ) {

        // Parse arguments against default values
        $r = bbp_parse_args( $args, array(
                'id'           => 0,
                'link_before'  => '',
                'link_after'   => '',
                'sep'          => ' | ',
                'text'    => '<i class="bbpvotes-icon bbpvotes-icon-success fa fa-thumbs-down"></i> '.esc_html__( 'Vote down',   'bbpvotes' ),
        ), 'get_post_vote_down_link' );

        if (!$post = get_post( (int) $r['id'] )) return false;
        
        //capability check
        if (!bbpvotes_can_user_vote_down_for_post($post->ID)) return false;
        
        if ( $post->post_author == get_current_user_id() ) return false;    //user cannot vote for himself
        
        $post_type = $post->post_type;
        $link_classes = array(
            'bbpvotes-post-vote-link',
            'bbpvotes-post-votedown-link'
        );
        
        switch($post_type){
            case bbp_get_topic_post_type():
                $r['title'] = __('This topic it is unclear or not useful','bbpvotes');
            break;
            case bbp_get_reply_post_type():
                $r['title'] = __('This reply answer is not useful','bbpvotes');
            break;
        };
        
        //check if user has already voted
        if ($voted_down = bbpvotes_has_user_voted_down_for_post( $post->ID )){
            $r['text'] = esc_html__( 'You voted down',   'bbpvotes' );
            $r['title'] = esc_html__( 'You have voted down for this post',   'bbpvotes' );
            if ( bbpvotes_can_user_unvote_for_post($post->ID) ){
                $r['title'] .= ' - '.esc_html__( 'Click to remove your vote',   'bbpvotes' );
            }
            $link_classes[] = 'bbpvotes-post-voted';
        }

        $uri     = add_query_arg( array( 'action' => 'bbpvotes_post_vote_down', 'post_id' => $post->ID ) );
        $uri     = wp_nonce_url( $uri, 'vote-down-post_' . $post->ID );
        $retval  = $r['link_before'] . '<a href="' . esc_url( $uri ) . '"  title="' . $r['title'] . '"'.bbpvotes_classes_attr($link_classes).'>' . bbpvotes_get_link_icons() . $r['text'] . '</a>' . $r['link_after'];

        return apply_filters( 'bbpvotes_get_vote_down_link', $retval, $r );
}

function bbpvotes_get_unvote_link( $args = '' ) {

        // Parse arguments against default values
        $r = bbp_parse_args( $args, array(
                'id'           => 0,
                'link_before'  => '',
                'link_after'   => '',
                'sep'          => ' | ',
                'text'    => esc_html__( 'Unvote',   'bbpvotes' ),
        ), 'get_post_unvote_link' );

        if (!$post = get_post( (int) $r['id'] )) return false;
        
        //capability check
        if (!bbpvotes_can_user_unvote_for_post($post->ID)) return false;
        
        if ( $post->post_author == get_current_user_id() ) return false;    //user cannot vote for himself
        
        $post_type = $post->post_type;
        $link_classes = array(
            'bbpvotes-post-vote-link',
            'bbpvotes-post-unvote-link'
        
        );
        
        $r['title'] = __('Remove my vote','bbpvotes');
        
        //check if user has already voted
        if ($voted_down = bbpvotes_has_user_voted_down_for_post( $post->ID )){
            $r['text'] = esc_html__( 'Vote removed',   'bbpvotes' );
            $r['title'] = esc_html__( 'Your vote for this post has been removed',   'bbpvotes' );
            $link_classes[] = 'bbpvotes-post-unvoted';
        }

        $uri     = add_query_arg( array( 'action' => 'bbpvotes_post_unvote', 'post_id' => $post->ID ) );
        $uri     = wp_nonce_url( $uri, 'unvote-post_' . $post->ID );
        $retval  = $r['link_before'] . '<a href="' . esc_url( $uri ) . '"  title="' . $r['title'] . '"'.bbpvotes_classes_attr($link_classes).'>' . bbpvotes_get_link_icons() . $r['text'] . '</a>' . $r['link_after'];

        return apply_filters( 'bbpvotes_get_unvote_link', $retval, $r );
}

/**
 * Check if a user has already voted for a post
 * @param type $post_id
 * @param type $user_id
 * @return bool
 */

function bbpvotes_has_user_voted_for_post( $post_id = null, $user_id = 0 ){

    $votes_up = bbpvotes_has_user_voted_up_for_post($post_id, $user_id);
    $votes_down = bbpvotes_has_user_voted_down_for_post($post_id, $user_id);
    
    if (!$votes_up && !$votes_down) return false;
    return true;
    
}

/**
 * Check if a user has voted up for a post
 * @param type $post_id
 * @param type $user_id
 * @return bool
 */

function bbpvotes_has_user_voted_up_for_post( $post_id = null, $user_id = 0 ){
    if (!$post_id) $post_id = get_the_ID();
    if (!$user_id) $user_id = get_current_user_id();

    $args = array(
        'p'             => $post_id,
        'post_type'	=> get_post_type($post_id), //TO FIX this should not be set to 'any' but does not work - or eventually bbpvotes_get_enabled_post_types()
        'post_status'   => 'any',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                    'key'     => bbpvotes()->metaname_post_vote_up,
                    'value'   => $user_id,
            ),
        ),
    );
    
    $my_query = new WP_Query( $args );
    return (bool)$my_query->have_posts();
    
}

/**
 * Check if a user has voted down for a post
 * @param type $post_id
 * @param type $user_id
 * @return bool
 */

function bbpvotes_has_user_voted_down_for_post( $post_id = null, $user_id = 0 ){
    if (!$post_id) $post_id = get_the_ID();
    if (!$user_id) $user_id = get_current_user_id();
    
    $args = array(
        'p'             => $post_id,
        'post_type'	=> get_post_type($post_id), //TO FIX this should not be set to 'any' but does not work
        'post_status'   => 'any',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                    'key'     => bbpvotes()->metaname_post_vote_down,
                    'value'   => $user_id,
            ),
        ),
    );
    $my_query = new WP_Query( $args );
    return (bool)$my_query->have_posts();
}

/**
 * Get the number of votes for a post
 * @param type $post_id
 * @return int
 */

function bbpvotes_get_votes_count_for_post( $post_id = null ){
    if (!$post_id) $post_id = get_the_ID();
    return get_post_meta( $post_id, bbpvotes()->metaname_post_vote_count, true );
    
}

/**
 * Get votes score for a post
 * @param type $post_id
 * @return int
 */

function bbpvotes_get_votes_score_for_post( $post_id = null ){
    
    if (!$post_id) $post_id = get_the_ID();
    return get_post_meta( $post_id, bbpvotes()->metaname_post_vote_score, true );
    
}

/*
 * Get detailed array of votes for this post.
 * To get only the post score, use bbpvotes_get_votes_score_for_post().
 */

function bbpvotes_get_votes_for_post( $post_id = null) {
    if (!$post_id) $post_id = get_the_ID();

    $votes_up = bbpvotes_get_votes_up_for_post( $post_id );
    $votes_down = bbpvotes_get_votes_down_for_post( $post_id );

    $votes = array();

    foreach ( (array) $votes_up as $user_id ) {
        $votes[$user_id] = 1;
    }

    foreach ( (array) $votes_down as $user_id ) {
        $votes[$user_id] = -1;
    }
    
    return apply_filters('bbpvotes_get_votes_for_post',$votes,$post_id);
    
}

function bbpvotes_get_votes_up_for_post( $post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    return get_post_meta( $post_id, bbpvotes()->metaname_post_vote_up );
}

function bbpvotes_get_votes_down_for_post( $post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    return get_post_meta( $post_id, bbpvotes()->metaname_post_vote_down );
}

    
function bbpvotes_get_post_votes_log( $post_id = 0 ) {
    
        if (!$votes = bbpvotes_get_votes_for_post( $post_id )) return;

        $r = "\n\n" . '<div id="bbpvotes-post-votes-log-' . esc_attr( $post_id ) . '" class="bbpvotes-post-votes-log">' . "\n\n";
        
        if ( bbpvotes()->get_options('anonymous_vote') == 'off'){
            foreach ( $votes as $user_id => $score ) {

                $user_id = bbp_get_user_id( $user_id );
                if (!$user = get_userdata( $user_id )) continue;

                if ($score>0){
                    $title = sprintf( esc_html__( '%1$s voted up', 'bbpvotes' ), $user->display_name);
                    $icon = '<span class="dashicons dashicons-plus bbpvotes-avatar-icon-vote bbpvotes-avatar-icon-plus"></span>';
                }else{
                    $title = sprintf( esc_html__( '%1$s voted down', 'bbpvotes' ), $user->display_name);
                    $icon = '<span class="dashicons dashicons-minus bbpvotes-avatar-icon-vote bbpvotes-avatar-icon-vote bbpvotes-avatar-icon-minus"></span>';
                }


                $user_avatar = get_avatar( $user_id, 30 );
                $user_vote_link = sprintf( '<a title="%1$s" href="%2$s">%3$s</a>',
                    $title,
                    esc_url( bbp_get_user_profile_url( $user_id ) ),
                    $user_avatar . $icon
                );

                $r.= apply_filters('bbpvotes_get_post_votes_log_user',$user_vote_link,$user_id,$score);
            }
        }else{

            $votes_str=array();
            
            if ( $votes_up = bbpvotes_get_votes_up_for_post( $post_id ) ){
                $votes_up_count = count($votes_up);
                $votes_up_count_display = bbpvotes_number_format($votes_up_count);
                $votes_str[] = sprintf( _n( '%s vote up', '%s votes up', $votes_up_count ), '<span class="bbpvotes-score">'.$votes_up_count_display.'</span>' );
            }
            
            if ( $votes_down = bbpvotes_get_votes_down_for_post( $post_id ) ){
                $votes_down_count = count($votes_down);
                $votes_down_count_display = bbpvotes_number_format($votes_down_count);
                $votes_str[] = sprintf( _n( '%s vote down', '%s votes down', $votes_down_count ), '<span class="bbpvotes-score">'.$votes_down_count_display.'</span>' );
            }
            
            $votes_str = implode(' '.__('and','bbpvotes').' ',$votes_str);

            $r.= sprintf(__('This post has received %s.','bbpvotes'),$votes_str);
            
        }



        
        $r .= "\n" . '</div>' . "\n\n";

        return apply_filters( 'bbpvotes_get_post_votes_log', $r, $post_id );
 
}

/**
 * 
 * Get the count of down votes by user
 * @global type $wpdb
 * @param type $user_id
 * @param type $post_args
 * @return int
 */

function bbpvotes_get_votes_down_by_user_count( $user_id = 0, $post_args = null ){
    global $wpdb;
    
    //1. Get votes by this user
    
    if (!$user_id) $user_id = get_current_user_id();
    
    $post_ids = $wpdb->get_col( $wpdb->prepare(
            "
            SELECT      post_id
            FROM        $wpdb->postmeta
            WHERE       meta_key = %s 
                        AND meta_value = %s
            ",
            bbpvotes()->metaname_post_vote_down, 
            $user_id
    ) ); 
    
    if ($post_ids){
        
        //2. limit results with regular posts query (allow to exclude by post status, etc.)

        $defaults = array(
            'post_type'         => bbpvotes_get_enabled_post_types(),
            'post_status'       => 'any',
            'posts_per_page'    => -1,
            'fields'            => 'ids',
            'post__in' => $post_ids, //limit to votes down
        );

        // Parse arguments against default values
        $post_args = bbp_parse_args( $post_args, $defaults, 'bbpvotes_get_votes_down_by_user_count_post_args' );

        $query = new WP_Query( $post_args );
        
        return (int)$query->found_posts;
    }else{
        return 0;
    }
}

/**
 * 
 * Get the count of down votes by user
 * @global type $wpdb
 * @param type $user_id
 * @param type $post_args
 * @return int
 */

function bbpvotes_get_votes_up_by_user_count( $user_id = 0, $post_args = null ){
    global $wpdb;
    
    //1. Get votes by this user
    
    if (!$user_id) $user_id = get_current_user_id();
    
    $post_ids = $wpdb->get_col( $wpdb->prepare(
            "
            SELECT      post_id
            FROM        $wpdb->postmeta
            WHERE       meta_key = %s 
                        AND meta_value = %s
            ",
            bbpvotes()->metaname_post_vote_up, 
            $user_id
    ) ); 
    
    if ($post_ids){
        
        //2. limit results with regular posts query (allow to exclude by post status, etc.)

        $defaults = array(
            'post_type'         => bbpvotes_get_enabled_post_types(),
            'post_status'       => 'any',
            'posts_per_page'    => -1,
            'fields'            => 'ids',
            'post__in' => $post_ids, //limit to votes down
        );

        // Parse arguments against default values
        $post_args = bbp_parse_args( $post_args, $defaults, 'bbpvotes_get_votes_up_by_user_count_post_args' );

        $query = new WP_Query( $post_args );
        
        return (int)$query->found_posts;
    }else{
        return 0;
    }
}

/**
 * Get the total count of votes by user
 * @param type $user_id
 * @return type
 */

function bbpvotes_get_votes_total_by_user_count( $user_id = 0, $post_args = null ){
    $votes_up = bbpvotes_get_votes_up_by_user_count($user_id,$post_args);
    $votes_down = bbpvotes_get_votes_down_by_user_count($user_id,$post_args);
    
    return (int)($votes_up+$votes_down);
}

function bbpvotes_get_author_score( $author_id = 0, $post_args = null ){
    global $wpdb;

    if (!$author_id) $author_id = get_current_user_id();
    
    $retval = false;

    if ($author_id){
        
        $transient_name = 'bbpvotes_karma_user_'.$author_id;
        $transient_duration = bbpvotes()->get_options('karma_cache_minutes') * MINUTE_IN_SECONDS;

        if ( (!$transient_duration) || ( false === ( $retval = get_transient( $transient_name ) ) ) ) { //is cache enabled ?

            //Get all posts by this author

            $defaults = array(
                'author'            => $author_id,
                'post_type'         => bbpvotes_get_enabled_post_types(),
                'post_status'       => 'any',
                'fields'            => 'ids',
                'posts_per_page'    => -1
            );

            $post_args = bbp_parse_args( $post_args, $defaults, 'bbpvotes_get_votes_down_for_author_count_post_args' );

            $query = new WP_Query( $post_args );

            if ($query->found_posts){

                $post_ids = $query->posts;
                $post_ids_str = implode(',',$post_ids);

                //Get sum of scores for those posts

                $query =  $wpdb->prepare(
                        "
                        SELECT      meta_value
                        FROM        $wpdb->postmeta
                        WHERE       meta_key = %s 
                                    AND post_id IN ({$post_ids_str})
                        ",
                        bbpvotes()->metaname_post_vote_score
                );

                $votes_scores = $wpdb->get_col( $query ); 

                bbpvotes()->debug_log("bbpvotes_get_author_score for user#".$author_id);
                bbpvotes()->debug_log($query);

                $retval = array_sum($votes_scores);

            }else{
                $retval = 0;
            }
            
            if ($transient_duration){
                set_transient( $transient_name, $retval, $transient_duration );
            }
            
        }
        
    }
    
    return $retval;

}

function bbpvotes_classes_attr($classes=false){
    if (!$classes) return false;
    return ' class="'.implode(" ",(array)$classes).'"';	
}


?>
