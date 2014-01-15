<?php

function global_site_search_roundup( $value, $dp ) {
	return ceil( $value * pow( 10, $dp ) ) / pow( 10, $dp );
}

function global_site_search_get_allowed_blogs() {
	if ( !defined( 'GLOBAL_SITE_SEARCH_BLOG' ) ) {
		define( 'GLOBAL_SITE_SEARCH_BLOG', 1 );
	}

	$site_search_blog = GLOBAL_SITE_SEARCH_BLOG;
	if ( is_string( $site_search_blog ) ) {
		$site_search_blog = array_filter( array_map( 'absint', explode( ',', $site_search_blog ) ) );
	}

	return apply_filters( 'global_site_search_allowed_blogs', (array)$site_search_blog );
}