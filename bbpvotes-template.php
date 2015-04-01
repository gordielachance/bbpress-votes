<?php

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
        $votes_count = bbpvotes_get_votes_count_for_post($post->ID);

        $r['text'] = sprintf(__('Score: %1$d','bbpvotes'),$score);
        $r['title'] = sprintf(__('Votes count: %1$d','bbpvotes'),$votes_count);
        
        $link_classes = array(
            'bbpvotes-post-vote-link',
            'bbpvotes-post-score-link'
        
        );
        
        if ( $score === false ) $link_classes[] = 'bbpvotes-post-no-score';

        //if ( empty( $post ) || !current_user_can( 'moderate', $post->ID ) ) return;
        $retval  = $r['link_before'] . '<a href="#" title="' . $r['title'] . '"'.bbpvotes_classes_attr($link_classes).'>' . $r['text'] . '</a>' . $r['link_after'];

        return apply_filters( 'bbpvotes_get_vote_up_link', $retval, $r );
}

function bbpvotes_get_link_icons(){
    //icons
    $icons = array(
        '<i class="bbpvotes-icon bbpvotes-icon-loading fa fa-circle-o-notch fa-spin"></i>',
        '<i class="bbpvotes-icon bbpvotes-icon-error fa fa-exclamation-triangle"></i>',
        '<i class="bbpvotes-icon bbpvotes-icon-success fa fa-check"></i>',
    );
    return implode('',$icons);
}


function bbpvotes_get_vote_up_link( $args = '' ) {

        // Parse arguments against default values
        $r = bbp_parse_args( $args, array(
                'id'           => 0,
                'link_before'  => '',
                'link_after'   => '',
                'sep'          => ' | ',
                'text'    => esc_html__( 'Vote up',   'bbpvotes' ),
        ), 'get_post_vote_up_link' );

        $post = get_post( (int) $r['id'] );
        
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
            $link_classes[] = 'bbpvotes-post-voted';
        }
        


        if ( empty( $post ) || !current_user_can( 'moderate', $post->ID ) ) return;

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
                'text'    => esc_html__( 'Vote down',   'bbpvotes' ),
        ), 'get_post_vote_down_link' );

        $post = get_post( (int) $r['id'] );
        
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
            $link_classes[] = 'bbpvotes-post-voted';
        }

        if ( empty( $post ) || !current_user_can( 'moderate', $post->ID ) ) return;

        $uri     = add_query_arg( array( 'action' => 'bbpvotes_post_vote_down', 'post_id' => $post->ID ) );
        $uri     = wp_nonce_url( $uri, 'vote-down-post_' . $post->ID );
        $retval  = $r['link_before'] . '<a href="' . esc_url( $uri ) . '"  title="' . $r['title'] . '"'.bbpvotes_classes_attr($link_classes).'>' . bbpvotes_get_link_icons() . $r['text'] . '</a>' . $r['link_after'];

        return apply_filters( 'bbpvotes_get_vote_down_link', $retval, $r );
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
        'post_type'	=> get_post_type($post_id), //TO FIX this should not be set to 'any' but does not work
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
 * Get votes count for a post
 * @param type $post_id
 * @return int
 */

function bbpvotes_get_votes_count_for_post( $post_id = null ){
    
    $result = false;
    
    $votes_up = count(bbpvotes_get_votes_up_for_post($post_id));
    $votes_down = count(bbpvotes_get_votes_down_for_post($post_id));
    
    if ($votes_up || $votes_down){
        $result = (int)($votes_up + $votes_down);
    }

    return apply_filters('bbpvotes_get_votes_count_for_post',$result,$post_id);
    
}

/**
 * Get votes score for a post
 * @param type $post_id
 * @return int
 */

function bbpvotes_get_votes_score_for_post( $post_id = null ){
    
    $result = false;
    
    $votes_up = count(bbpvotes_get_votes_up_for_post($post_id));
    $votes_down = count(bbpvotes_get_votes_down_for_post($post_id));
    
    if ($votes_up || $votes_down){
        $result = (int)($votes_up - $votes_down);
    }

    return apply_filters('bbpvotes_get_votes_score_for_post',$result,$post_id);
    
}

    /**
     * Get array of positive votes for a post
     * @param type $post_id
     * @return array
     */

    function bbpvotes_get_votes_up_for_post( $post_id = null) {
        if (!$post_id) $post_id = get_the_ID();
        $votes = get_post_meta( $post_id, bbpvotes()->metaname_post_vote_up );
        return apply_filters('bbpvotes_get_votes_up_for_post',$votes,$post_id);
    }
    
    /**
     * Get array of negative votes for a post
     * @param type $post_id
     * @return array
     */

    function bbpvotes_get_votes_down_for_post( $post_id = null) {
        if (!$post_id) $post_id = get_the_ID();
        $votes = get_post_meta( $post_id, bbpvotes()->metaname_post_vote_down );
        return apply_filters('bbpvotes_get_votes_down_for_post',$votes,$post_id);
    }
    
function bbpvotes_get_post_votes_log( $post_id = 0 ) {
    
        if (!bbpvotes_get_votes_score_for_post( $post_id )) return;
        
        $votes_up = bbpvotes_get_votes_up_for_post();
        $votes_down = bbpvotes_get_votes_down_for_post();

        
        $r = "\n\n" . '<div id="bbpvotes-post-votes-log-' . esc_attr( $post_id ) . '" class="bbpvotes-post-votes-log">' . "\n\n";

        if($votes_up){
            $r .='<p class="bbpvotes-post-votes-up-log">';
            // Loop through revisions
            foreach ( (array) $votes_up as $user_id ) {
                
                $user_id = bbp_get_user_id( $user_id );
                $user      = get_userdata( $user_id );
                
                $title = sprintf( esc_html__( '%1$s voted up', 'bbpvotes' ), $user->display_name);

                $user_avatar = get_avatar( $user_id, 30 );
                $r .= '<a title="'.$title.'" href="' . esc_url( bbp_get_user_profile_url( $user_id ) ) . '">' . $user_avatar . '<small>+1</small></a>';
            }
            $r .='</p>';
        }
        
        if($votes_down){
            $r .='<p class="bbpvotes-post-votes-down-log">';
            // Loop through revisions
            foreach ( (array) $votes_down as $user_id ) {

                $user_id = bbp_get_user_id( $user_id );
                $user      = get_userdata( $user_id );
                
                $title = sprintf( esc_html__( '%1$s voted down', 'bbpvotes' ), $user->display_name);

                $user_avatar = get_avatar( $user_id, 30 );
                $r .= '<a title="'.$title.'" href="' . esc_url( bbp_get_user_profile_url( $user_id ) ) . '">' . $user_avatar . '<small>-1</small></a>';
            }
            $r .='</p>';
        }

        
        $r .= "\n" . '</div>' . "\n\n";

        return apply_filters( 'bbpvotes_get_post_votes_log', $r, $post_id );
 
}

function bbpvotes_classes_attr($classes=false){
    if (!$classes) return false;
    return ' class="'.implode(" ",(array)$classes).'"';	
}  


?>