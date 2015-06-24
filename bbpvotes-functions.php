<?php

/**
 * Rebuild scores for all votes
 */

function bbpvotes_rebuild_scores(){
    
    $post_ids = array();
    
    $default_query_args = array(
        'post_type'         => bbpvotes()->supported_post_types,
        'post_status'   => 'any',
        'posts_per_page'    => -1,
        'fields'            => 'ids'
    );

    $votes_up_query_args = wp_parse_args( array('meta_key' => bbpvotes()->metaname_post_vote_up), $default_query_args );
    $votes_down_query_args = wp_parse_args( array('meta_key' => bbpvotes()->metaname_post_vote_down), $default_query_args );

    $votes_up_query = new WP_Query( $votes_up_query_args );
    $votes_down_query = new WP_Query( $votes_down_query_args );

    foreach ($votes_up_query->posts as $id){
        $post_ids[] = $id;
    }

    foreach ($votes_down_query->posts as $id){
        $post_ids[] = $id;
    }
    
    $post_ids = array_unique($post_ids);

    foreach ($post_ids as $id){
        
        $post_score = 0;
        $vote_count = 0;
        $votes = bbpvotes_get_votes_for_post( $id );
        
        foreach ($votes as $user_id => $vote){
            $post_score += $vote;
            $vote_count++;
        }

        update_post_meta($id, bbpvotes()->metaname_post_vote_score, $post_score);
        update_post_meta($id, bbpvotes()->metaname_post_vote_count, $vote_count);

    }

}

?>