<?php
/*
Plugin Name: Global Site Search
Plugin URI: http://premium.wpmudev.org/project/global-site-search
Description: A magnificent plugin that allows global search across all blogs on your WordPress Multisite / BuddyPress install with ease!
Author: Incsub
Version: 3.0.3
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
		global $current_site, $members_directory_base, $wp_query;

		if ( !isset( $wp_query->query_vars['namespace'] ) || $wp_query->query_vars['namespace'] != 'gss' || $wp_query->query_vars['type'] != 'search' ) {
			return $content;
		}

		// We are on a search results page

		$global_site_search_per_page = get_site_option( 'global_site_search_per_page', '10' );
		$global_site_search_background_color = get_site_option( 'global_site_search_background_color', '#F2F2EA' );
		$global_site_search_alternate_background_color = get_site_option( 'global_site_search_alternate_background_color', '#FFFFFF' );
		$global_site_search_border_color = get_site_option( 'global_site_search_border_color', '#CFD0CB' );

		$global_site_search_post_type = get_site_option( 'global_site_search_post_type', 'post' );

		//=====================================//
		$parameters = array();

		// Set the page number
		if ( !isset( $wp_query->query_vars['paged'] ) || $wp_query->query_vars['paged'] <= 1 ) {
			$page = 1;
			$start = 0;
		} else {
			$page = $wp_query->query_vars['paged'];
			$start = $global_site_search_per_page * ( $wp_query->query_vars['paged'] - 1 );
		}

		$phrase = isset( $wp_query->query_vars['search'] ) ? urldecode( $wp_query->query_vars['search'] ) : '';
		if ( empty( $phrase ) && isset( $_REQUEST['phrase'] ) ) {
			$phrase = trim( $_REQUEST['phrase'] );
		}

		$theauthor = get_user_by( 'login', $phrase );
		if ( is_object( $theauthor ) ) {
			$author_id = $theauthor->ID;
		}

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
		$parameters['paged'] = absint( $page );

		//=====================================//
		$search_form_content = $this->global_site_search_search_form_output( '', $phrase );

		$content .= $search_form_content;
		$content .= '<br>';

		if ( empty( $phrase ) ) {
			return $content;
		}

		$network_query_posts = network_query_posts( $parameters );

		//found_posts
		if ( network_have_posts() ) {
			$navigation_content = isset( $GLOBALS['network_query']->found_posts ) && $GLOBALS['network_query']->found_posts > intval( $global_site_search_per_page )
				? $this->new_pagination( $GLOBALS['network_query'], $current_site->path . $this->global_site_search_base . '/' . urlencode( $phrase ) )
				: '';

			$content .= $navigation_content;
			$content .= '<div style="float:left; width:100%">';
				$content .= '<table border="0" width="100%" bgcolor="">';
					$content .= '<tr>';
						$content .= '<td style="background-color:' . $global_site_search_background_color . '; border-bottom-style:solid; border-bottom-color:' . $global_site_search_border_color . '; border-bottom-width:1px; font-size:12px;" width="10%">&nbsp;</td>';
						$content .= '<td style="background-color:' . $global_site_search_background_color . '; border-bottom-style:solid; border-bottom-color:' . $global_site_search_border_color . '; border-bottom-width:1px; font-size:12px; text-align: ceter;" width="90%"><strong>' . __( 'Posts', 'globalsitesearch' ) . '</strong></td>';
					$content .= '</tr>';

					// Search results

					$avatar_default = get_option( 'avatar_default' );
					$tic_toc = 'toc';

					$substr = function_exists( 'mb_substr' ) ? 'mb_substr' : 'substr';
					while ( network_have_posts() ) {
						network_the_post();

						$author_id = network_get_the_author_id();
						$the_author = get_user_by( 'id', $author_id );
						$post_author_display_name = $the_author ? $the_author->display_name : __( 'Unknown', 'globalsitesearch' );

						$tic_toc = ($tic_toc == 'toc') ? 'tic' : 'toc';
						$bg_color = ($tic_toc == 'tic') ? $global_site_search_alternate_background_color : $global_site_search_background_color;

						$content .= '<tr>';
							$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px; text-align: center;" valign="top" width="10%"><a style="text-decoration:none;" href="' . network_get_permalink() . '">' . get_avatar( $author_id, 32, $avatar_default ) . '</a></td>';
							$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px; vertical-align: top; text-align: left;" width="90%" valign="top">';
								if ( function_exists( 'members_directory_site_admin_options' ) ) {
									$post_author_nicename = $the_author->user_nicename;
									$content .= '<strong><a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $post_author_nicename . '/">' . $post_author_display_name . '</a> ' . __( ' wrote', 'globalsitesearch' ) . ': </strong> ';
								} else {
									$content .= '<strong>' . $post_author_display_name . __( ' wrote', 'globalsitesearch' ) . ': </strong> ';
								}
								$content .= '<strong><a style="text-decoration:none;" href="' . network_get_permalink() . '">' . network_get_the_title() . '</a></strong><br />';
								$the_content = preg_replace( "~(?:\[/?)[^/\]]+/?\]~s", '', network_get_the_content() );
								$content .= $substr( strip_tags( $the_content ), 0, 250 ) . ' (<a href="' . network_get_permalink() . '">' . __( 'More', 'globalsitesearch' ) . '</a>)';
							$content .= '</td>';
						$content .= '</tr>';
					}

				$content .= '</table>';
			$content .= '</div>';
			$content .= $navigation_content;
		} else {
			$content .= '<p style="text-align:center">' . __( 'Nothing found for search term(s).', 'globalsitesearch' ) . '</p>';
		}

		return $content;
	}

	function new_pagination( $wp_query, $mainlink = '' ) {
		$html = '';

		if ( (int)$wp_query->max_num_pages > 1 ) {
			$html = '<div class="gssnav">' . paginate_links( array(
				'base'      => trailingslashit( $mainlink ) . '%_%',
				'format'    => 'page/%#%',
				'total'     => $wp_query->max_num_pages,
				'current'   => !empty( $wp_query->query_vars['paged'] ) ? $wp_query->query_vars['paged'] : 1,
				'prev_next' => true,
			) ) . '</div>';
		}

		return $html;
	}

	function global_site_search_search_form_output( $content, $phrase ) {
		global $current_site;

		$content .= '<form action="' . esc_url( trailingslashit( $current_site->path . $this->global_site_search_base ) ) . '">';
			$content .= '<table border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
				$content .= '<tr>';
					$content .= '<td style="font-size:12px; text-align:left;" width="80%">';
						$content .= '<input type="text" name="phrase" style="width: 100%;" value="' . esc_attr( stripslashes( $phrase ) ) . '">';
					$content .= '</td>';
					$content .= '<td style="font-size:12px; text-align:right;" width="20%">';
						$content .= '<input type="submit" value="' . __( 'Search', 'globalsitesearch' ) . '">';
					$content .= '</td>';
				$content .= '</tr>';
			$content .= '</table>';
		$content .= '</form>';

		return $content;
	}

}

$global_site_search = new global_site_search();

class Global_Site_Search_Widget extends WP_Widget {

	public function __construct() {
		$widget_options = array(
			'classname'   => 'global-site-search',
			'description' => __( 'Global Site Search Widget', 'globalsitesearch' ),
		);

		$control_options = array(
			'id_base' => 'global-site-search-widget',
		);

		parent::__construct( 'global-site-search-widget', __( 'Global Site Search Widget', 'globalsitesearch' ), $widget_options, $control_options );
	}

	function widget( $args, $instance ) {
		global $global_site_search, $wp_query;

		extract( $args );

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( !empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		$phrase = '';
		if ( isset( $wp_query->query_vars['namespace'] ) && $wp_query->query_vars['namespace'] == 'gss' && $wp_query->query_vars['type'] == 'search' ) {
			$phrase = isset( $wp_query->query_vars['search'] ) ? urldecode( $wp_query->query_vars['search'] ) : '';
			if ( empty( $phrase ) ) {
				if ( isset( $_REQUEST['phrase'] ) ) {
					$phrase = urldecode( $_REQUEST['phrase'] );
				}
			}
		}

		echo $global_site_search->global_site_search_search_form_output( '', $phrase );

		/* After widget (defined by themes). */
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array)$instance, array(
			'title' => __( 'Global Site Search', 'globalsitesearch' ),
		) );

		?><p>
			<label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title', 'globalsitesearch' ) ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ) ?>" name="<?php echo $this->get_field_name( 'title' ) ?>" value="<?php echo esc_attr( $instance['title'] ) ?>" class="widefat">
		</p><?php
	}

}

function global_site_search_roundup( $value, $dp ) {
	return ceil( $value * pow( 10, $dp ) ) / pow( 10, $dp );
}

add_action( 'widgets_init', 'global_site_search_load_widgets' );
function global_site_search_load_widgets() {
	if ( in_array( get_current_blog_id(), global_site_search_get_allowed_blogs() ) ) {
		register_widget( 'Global_Site_Search_Widget' );
	}
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

register_activation_hook( __FILE__, 'flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );