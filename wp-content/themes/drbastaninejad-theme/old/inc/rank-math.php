<?php

defined( 'ABSPATH' ) || exit;

function drb_rank_math_case_robots( $robots ) {
    if ( is_singular( 'case_study' ) ) {
        $options = drb_get_theme_options();
        $verified = (bool) get_post_meta( get_queried_object_id(), '_drb_consent_verified', true );
        if ( empty( $options['gallery_enabled'] ) || ! $verified ) {
            $robots['index'] = 'noindex';
            $robots['follow'] = 'nofollow';
        }
    }
    if ( is_singular( array( 'post', 'page', 'service' ) ) ) {
        $post = get_queried_object();
        if ( $post && preg_match( '/(?:بینی(?:‌|\s)*(?:گوشتی|فانتزی|عروسکی)|گوشتی|fleshy|fantasy)/iu', $post->post_title . ' ' . $post->post_name ) ) {
            $robots['index'] = 'noindex';
            $robots['follow'] = 'nofollow';
        }
    }
    return $robots;
}
add_filter( 'rank_math/frontend/robots', 'drb_rank_math_case_robots' );

function drb_rank_math_exclude_unverified_cases( $ids ) {
    $unverified = get_posts(
        array(
            'post_type' => 'case_study', 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array( 'key' => '_drb_consent_verified', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_drb_consent_verified', 'value' => '1', 'compare' => '!=' ),
            ),
        )
    );
    return array_values( array_unique( array_merge( $ids, $unverified ) ) );
}
add_filter( 'rank_math/sitemap/exclude_post_ids', 'drb_rank_math_exclude_unverified_cases' );

function drb_rank_math_default_social_image( $image ) {
    $options = drb_get_theme_options();
    return empty( $image ) && ! empty( $options['default_social_image'] ) ? $options['default_social_image'] : $image;
}
add_filter( 'rank_math/opengraph/facebook/image', 'drb_rank_math_default_social_image' );
add_filter( 'rank_math/opengraph/twitter/image', 'drb_rank_math_default_social_image' );
