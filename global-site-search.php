<?php
/*
Plugin Name: Global Site Search
Plugin URI: http://premium.wpmudev.org/project/global-site-search
Description: A magnificent plugin that allows global search across all blogs on your WordPress Multisite / BuddyPress install with ease!
Author: WPMU DEV
Version: 3.1.0.1
Author URI: http://premium.wpmudev.org
WDP ID: 102
Network: true
*/

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

require_once 'functions.php' ;
require_once 'widgets.php';

class global_site_search {

	var $build = 4;

	var $db;

	var $global_site_search_base = 'site-search'; //domain.tld/BASE/ Ex: domain.tld/user/

	function __construct() {
		global $wpdb;

		$this->db = $wpdb;

		if ( in_array( get_current_blog_id(), global_site_search_get_allowed_blogs() ) ) {
			if ( get_option( 'gss_installed', 0 ) < $this->build || get_option( 'gss_installed', 0 ) == 'yes' ) {
				add_action( 'init', array( $this, 'initialise_plugin' ) );
			}

			// Add the rewrites
			add_action( 'generate_rewrite_rules', array( $this, 'add_rewrite' ) );
			add_filter( 'query_vars', array( $this, 'add_queryvars' ) );

			add_filter( 'the_content', array( $this, 'global_site_search_output' ), 20 );
			//add_filter( 'the_title', array( $this, 'global_site_search_title_output'), 99, 2 );
		}

		add_action( 'wpmu_options', array( $this, 'global_site_search_site_admin_options' ) );
		add_action( 'update_wpmu_options', array( $this, 'global_site_search_site_admin_options_process' ) );

		add_action( 'plugins_loaded', array( $this, 'global_site_search_site_load_textdomain' ) );
	}

	function initialise_plugin() {
		flush_rewrite_rules();
		$this->global_site_search_page_setup();
		update_option( 'gss_installed', $this->build );
	}

	function add_queryvars( $vars ) {
		// This function add the namespace (if it hasn't already been added) and the
		// eventperiod queryvars to the list that WordPress is looking for.
		// Note: Namespace provides a means to do a quick check to see if we should be doing anything

		return array_unique( array_merge( $vars, array( 'namespace', 'search', 'paged', 'type' ) ) );
	}

	function add_rewrite( $wp_rewrite ) {

		// This function adds in the api rewrite rules
		// Note the addition of the namespace variable so that we know these are vent based
		// calls
		$new_rules = array();

		$new_rules[$this->global_site_search_base . '/(.+)/page/?([0-9]{1,})'] = 'index.php?namespace=gss&search=' . $wp_rewrite->preg_index( 1 ) . '&paged=' . $wp_rewrite->preg_index( 2 ) . '&type=search&pagename=' . $this->global_site_search_base;
		$new_rules[$this->global_site_search_base . '/(.+)'] = 'index.php?namespace=gss&search=' . $wp_rewrite->preg_index( 1 ) . '&type=search&pagename=' . $this->global_site_search_base;
		$new_rules[$this->global_site_search_base] = 'index.php?namespace=gss&type=search&pagename=' . $this->global_site_search_base;

		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;

		return $wp_rewrite;
	}

	function global_site_search_page_setup() {
		if ( get_option( 'global_site_search_page_setup' ) == 'complete' || !is_super_admin() ) {
			return;
		}

		$page_id = get_option( 'global_site_search_page' );
		if ( empty( $page_id ) ) {
			// a page hasn't been set - so check if there is already one with the base name
			$page_id = $this->db->get_var( sprintf(
				"SELECT ID FROM %s WHERE post_name = '%s' AND post_type = 'page'",
				$this->db->posts,
				esc_sql( $this->global_site_search_base )
			) );

			if ( empty( $page_id ) ) {
				// Doesn't exist so create the page
				$page_id = wp_insert_post( array(
					"post_content"   => '',
					"post_title"     => __( 'Site Search', 'globalsitesearch' ),
					"post_excerpt"   => '',
					"post_status"    => 'publish',
					"comment_status" => 'closed',
					"ping_status"    => 'closed',
					"post_name"      => $this->global_site_search_base,
					"post_type"      => 'page',
				) );
			}

			update_option( 'global_site_search_page', $page_id );
		}

		update_option( 'global_site_search_page_setup', 'complete' );
	}

	function global_site_search_site_load_textdomain() {
		load_plugin_textdomain( 'globalsitesearch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	function global_site_search_site_admin_options() {
		$global_site_search_per_page = get_site_option( 'global_site_search_per_page', 10 );
		$global_site_search_post_type = get_site_option( 'global_site_search_post_type', 'post' );

		$post_types = $this->db->get_col( "SELECT post_type FROM {$this->db->base_prefix}network_posts GROUP BY post_type" );

		?><h3><?php _e( 'Site Search', 'globalsitesearch' ) ?></h3>
		<table class="form-table">
			<tr valign="top">
				<th width="33%" scope="row"><?php _e( 'Listing Per Page', 'globalsitesearch' ) ?></th>
				<td>
					<select name="global_site_search_per_page" id="global_site_search_per_page">
						<?php for ( $i = 5; $i <= 50; $i += 5 ) : ?>
						<option<?php selected( $global_site_search_per_page, $i ) ?>><?php echo $i ?></option>
						<?php endfor; ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th width="33%" scope="row"><?php _e( 'Background Color', 'globalsitesearch' ) ?></th>
				<td>
					<input name="global_site_search_background_color" type="text" id="global_site_search_background_color" value="<?php echo esc_attr( get_site_option( 'global_site_search_background_color', '#F2F2EA' ) ) ?>" size="20">
					<br><?php _e( 'Default', 'globalsitesearch' ) ?>: #F2F2EA
				</td>
			</tr>
			<tr valign="top">
				<th width="33%" scope="row"><?php _e( 'Alternate Background Color', 'globalsitesearch' ) ?></th>
				<td>
					<input name="global_site_search_alternate_background_color" type="text" id="global_site_search_alternate_background_color" value="<?php echo esc_attr( get_site_option( 'global_site_search_alternate_background_color', '#FFFFFF' ) ) ?>" size="20">
					<br><?php _e( 'Default', 'globalsitesearch' ) ?>: #FFFFFF
				</td>
			</tr>
			<tr valign="top">
				<th width="33%" scope="row"><?php _e( 'Border Color', 'globalsitesearch' ) ?></th>
				<td>
					<input name="global_site_search_border_color" type="text" id="global_site_search_border_color" value="<?php echo esc_attr( get_site_option( 'global_site_search_border_color', '#CFD0CB' ) ) ?>" size="20">
					<br><?php _e( 'Default', 'globalsitesearch' ) ?>: #CFD0CB
				</td>
			</tr>

			<tr valign="top">
				<th width="33%" scope="row"><?php _e( 'List Post Type', 'globalsitesearch' ) ?></th>
				<td>
					<select name="global_site_search_post_type" id="global_site_search_post_type">
						<option value="all"><?php _e( 'all', 'globalsitesearch' ) ?></option>
						<?php foreach ( $post_types as $r ) : ?>
						<option value="<?php echo esc_attr( $r ) ?>"<?php selected( $global_site_search_post_type, $r ) ?> ><?php _e( $r, 'globalsitesearch' ) ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table><?php
	}

	function global_site_search_site_admin_options_process() {
		update_site_option( 'global_site_search_per_page', filter_input( INPUT_POST, 'global_site_search_per_page', FILTER_VALIDATE_INT, array(
			'options' => array(
				'min_range' => 5,
				'max_range' => 50,
				'default'   => 10,
			),
		) ) );

		update_site_option( 'global_site_search_background_color', trim( filter_input( INPUT_POST, 'global_site_search_background_color' ) ) );
		update_site_option( 'global_site_search_alternate_background_color', trim( filter_input( INPUT_POST, 'global_site_search_alternate_background_color' ) ) );
		update_site_option( 'global_site_search_border_color', trim( filter_input( INPUT_POST, 'global_site_search_border_color' ) ) );
		update_site_option( 'global_site_search_post_type', filter_input( INPUT_POST, 'global_site_search_post_type' ) );
	}

	function global_site_search_title_output( $title ) {
		global $current_site, $wp_query;

		if ( isset( $wp_query->query_vars['namespace'] ) && $wp_query->query_vars['namespace'] == 'gss' && $wp_query->query_vars['type'] == 'search' ) {
			$title = '<a href="http://' . $current_site->domain . $current_site->path . $this->global_site_search_base . '/">' . __( 'Site Search', 'globalsitesearch' ) . '</a>';
			if ( isset( $wp_query->query_vars['paged'] ) && $wp_query->query_vars['paged'] > 1 ) {
				$search = isset( $wp_query->query_vars['search'] ) ? $wp_query->query_vars['search'] : '';
				$title .= ' &raquo; <a href="http://' . $current_site->domain . $current_site->path . $this->global_site_search_base . '/' . urlencode( $search ) . '/page/' . $wp_query->query_vars['paged'] . '/">' . $wp_query->query_vars['paged'] . '</a>';
			}
		}

		return $title;
	}

	function global_site_search_output( $content ) {
		global $wp_query;

		if ( !isset( $wp_query->query_vars['namespace'] ) || $wp_query->query_vars['namespace'] != 'gss' || $wp_query->query_vars['type'] != 'search' ) {
			return $content;
		}

		// We are on a search results page

		$global_site_search_per_page = get_site_option( 'global_site_search_per_page', '10' );
		$global_site_search_post_type = get_site_option( 'global_site_search_post_type', 'post' );

		//=====================================//
		//
		$phrase = isset( $wp_query->query_vars['search'] ) ? urldecode( $wp_query->query_vars['search'] ) : '';
		if ( empty( $phrase ) && isset( $_REQUEST['phrase'] ) ) {
			$phrase = trim( $_REQUEST['phrase'] );
		}

		if ( empty( $phrase ) ) {
			ob_start();
			global_site_search_form();
			$content .= ob_get_clean();

			return $content;
		}

		$theauthor = get_user_by( 'login', $phrase );
		if ( is_object( $theauthor ) ) {
			$author_id = $theauthor->ID;
		}

		$parameters = array();
		if ( isset( $author_id ) && is_numeric( $author_id ) && $author_id != 0 ) {
			$parameters['author'] = $author_id;
		} else {
			$parameters['s'] = $phrase;
		}

		$parameters['post_type'] = $global_site_search_post_type != 'all'
			? $global_site_search_post_type
			: $this->db->get_col( "SELECT post_type FROM {$this->db->base_prefix}network_posts GROUP BY post_type" );

		// Add in the start and end numbers
		$parameters['posts_per_page'] = absint( $global_site_search_per_page );

		// Set the page number
		if ( !isset( $wp_query->query_vars['paged'] ) || $wp_query->query_vars['paged'] <= 1 ) {
			$parameters['paged'] = 1;
			$start = 0;
		} else {
			$parameters['paged'] = absint( $wp_query->query_vars['paged'] );
			$start = $global_site_search_per_page * ( $wp_query->query_vars['paged'] - 1 );
		}

		//=====================================//

		ob_start();
		$network_query_posts = network_query_posts( $parameters );
		include global_site_search_locate_template( 'global-site-search.php' );
		$content .= ob_get_clean();

		return $content;
	}

}

$global_site_search = new global_site_search();

register_activation_hook( __FILE__, 'flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );