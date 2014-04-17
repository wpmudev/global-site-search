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

function global_site_search_locate_template( $template_name, $template_path = 'global-site-search' ) {
	// Look within passed path within the theme - this is priority
	$template = locate_template( array(
		trailingslashit( $template_path ) . $template_name,
		$template_name
	) );

	// Get default template
	if ( !$template ) {
		$template = implode( DIRECTORY_SEPARATOR, array( dirname( __FILE__ ), 'templates', $template_name ) );
	}

	// Return what we found
	return apply_filters( 'global_site_search_locate_template', $template, $template_name, $template_path );
}

function global_site_search_form() {
	include global_site_search_locate_template( 'global-site-search-form.php' );
}

function global_site_search_get_search_base() {
	global $global_site_search;
	return $global_site_search->global_site_search_base;
}

function global_site_search_get_phrase() {
	global $wp_query;

	$phrase = isset( $wp_query->query_vars['search'] ) ? urldecode( $wp_query->query_vars['search'] ) : '';
	if ( empty( $phrase ) && isset( $_REQUEST['phrase'] ) ) {
		$phrase = trim( stripslashes($_REQUEST['phrase'] ));
	}
	return $phrase;
}

function global_site_search_get_pagination( $mainlink = '' ) {
	global $network_query, $current_site;
	if ( absint( $network_query->max_num_pages ) <= 1 ) {
		return '';
	}

	if ( empty( $mainlink ) ) {
		$mainlink = $current_site->path . global_site_search_get_search_base() . '/' . urlencode( global_site_search_get_phrase() );
	}

	return paginate_links( array(
		'base'      => trailingslashit( $mainlink ) . '%_%',
		'format'    => 'page/%#%',
		'total'     => $network_query->max_num_pages,
		'current'   => !empty( $network_query->query_vars['paged'] ) ? $network_query->query_vars['paged'] : 1,
		'prev_next' => true,
	) );
}

function global_site_search_get_background_color() {
	return get_site_option( 'global_site_search_background_color', '#F2F2EA' );
}

function global_site_search_get_alt_background_color() {
	return get_site_option( 'global_site_search_alternate_background_color', '#FFFFFF' );
}

function global_site_search_get_border_color() {
	return get_site_option( 'global_site_search_border_color', '#CFD0CB' );
}