<?php
/*
Plugin Name: Global Site Search
Plugin URI:
Description:
Author: Andrew Billits (Incsub)
Version: 2.0
Author URI: http://premium.wpmudev.org
WDP ID: 102
Network: true
*/

/*
Copyright 2007-2009 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/* -------------------- Update Notifications Notice -------------------- */
if ( !function_exists( 'wdp_un_check' ) ) {
  //add_action( 'admin_notices', 'wdp_un_check', 5 );
  //add_action( 'network_admin_notices', 'wdp_un_check', 5 );
  function wdp_un_check() {
    if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'edit_users' ) )
      echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
  }
}
/* --------------------------------------------------------------------- */

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//

$global_site_search_base = 'site-search'; //domain.tld/BASE/ Ex: domain.tld/user/

//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//

if ($current_blog->domain . $current_blog->path == $current_site->domain . $current_site->path){
	add_filter('generate_rewrite_rules','global_site_search_rewrite');
	$global_site_search_wp_rewrite = new WP_Rewrite;
	$global_site_search_wp_rewrite->flush_rules();
	add_filter('the_content', 'global_site_search_output', 20);
	add_filter('the_title', 'global_site_search_title_output', 99, 2);
	add_action('admin_footer', 'global_site_search_page_setup');
}

add_action('wpmu_options', 'global_site_search_site_admin_options');
add_action('update_wpmu_options', 'global_site_search_site_admin_options_process');

//add_action( 'plugins_loaded', 'global_site_search_site_load_textdomain');

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function global_site_search_page_setup() {
	global $wpdb, $user_ID, $global_site_search_base;
	if ( get_site_option('global_site_search_page_setup') != 'complete' && is_site_admin() ) {
		$page_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_name = '" . $global_site_search_base . "' AND post_type = 'page'");
		if ( $page_count < 1 ) {
			$wpdb->query( "INSERT INTO " . $wpdb->posts . " ( post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count ) VALUES ( '" . $user_ID . "', '" . current_time( 'mysql' ) . "', '" . current_time( 'mysql' ) . "', '', '" . __('Site Search') . "', '', 'publish', 'closed', 'closed', '', '" . $global_site_search_base . "', '', '', '" . current_time( 'mysql' ) . "', '" . current_time( 'mysql' ) . "', '', 0, '', 0, 'page', '', 0 )" );
		}
		update_site_option('global_site_search_page_setup', 'complete');
	}
}

function global_site_search_site_load_textdomain() {
	// Load the text-domain
	$locale = apply_filters( 'globalsitesearch_locale', get_locale() );
	$mofile = dirname(__FILE__) . "/languages/globalsitesearch-$locale.mo";

	if ( file_exists( $mofile ) )
		load_textdomain( 'globalsitesearch', $mofile );
}

function global_site_search_site_admin_options() {
	$global_site_search_per_page = get_site_option('global_site_search_per_page', '10');
	$global_site_search_background_color = get_site_option('global_site_search_background_color', '#F2F2EA');
	$global_site_search_alternate_background_color = get_site_option('global_site_search_alternate_background_color', '#FFFFFF');
	$global_site_search_border_color = get_site_option('global_site_search_border_color', '#CFD0CB');
	?>
		<h3><?php _e('Site Search') ?></h3>
		<table class="form-table">
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Listing Per Page', 'globalsitesearch') ?></th>
                <td>
				<select name="global_site_search_per_page" id="global_site_search_per_page">
				   <option value="5" <?php if ( $global_site_search_per_page == '5' ) { echo 'selected="selected"'; } ?> ><?php _e('5', 'globalsitesearch'); ?></option>
				   <option value="10" <?php if ( $global_site_search_per_page == '10' ) { echo 'selected="selected"'; } ?> ><?php _e('10', 'globalsitesearch'); ?></option>
				   <option value="15" <?php if ( $global_site_search_per_page == '15' ) { echo 'selected="selected"'; } ?> ><?php _e('15', 'globalsitesearch'); ?></option>
				   <option value="20" <?php if ( $global_site_search_per_page == '20' ) { echo 'selected="selected"'; } ?> ><?php _e('20', 'globalsitesearch'); ?></option>
				   <option value="25" <?php if ( $global_site_search_per_page == '25' ) { echo 'selected="selected"'; } ?> ><?php _e('25', 'globalsitesearch'); ?></option>
				   <option value="30" <?php if ( $global_site_search_per_page == '30' ) { echo 'selected="selected"'; } ?> ><?php _e('30', 'globalsitesearch'); ?></option>
				   <option value="35" <?php if ( $global_site_search_per_page == '35' ) { echo 'selected="selected"'; } ?> ><?php _e('35', 'globalsitesearch'); ?></option>
				   <option value="40" <?php if ( $global_site_search_per_page == '40' ) { echo 'selected="selected"'; } ?> ><?php _e('40', 'globalsitesearch'); ?></option>
				   <option value="45" <?php if ( $global_site_search_per_page == '45' ) { echo 'selected="selected"'; } ?> ><?php _e('45', 'globalsitesearch'); ?></option>
				   <option value="50" <?php if ( $global_site_search_per_page == '50' ) { echo 'selected="selected"'; } ?> ><?php _e('50', 'globalsitesearch'); ?></option>
				</select>
                <br /><?php //_e('') ?></td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Background Color', 'globalsitesearch') ?></th>
                <td><input name="global_site_search_background_color" type="text" id="global_site_search_background_color" value="<?php echo $global_site_search_background_color; ?>" size="20" />
                <br /><?php _e('Default', 'globalsitesearch') ?>: #F2F2EA</td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Alternate Background Color', 'globalsitesearch') ?></th>
                <td><input name="global_site_search_alternate_background_color" type="text" id="global_site_search_alternate_background_color" value="<?php echo $global_site_search_alternate_background_color; ?>" size="20" />
                <br /><?php _e('Default', 'globalsitesearch') ?>: #FFFFFF</td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Border Color', 'globalsitesearch') ?></th>
                <td><input name="global_site_search_border_color" type="text" id="global_site_search_border_color" value="<?php echo $global_site_search_border_color; ?>" size="20" />
                <br /><?php _e('Default', 'globalsitesearch') ?>: #CFD0CB</td>
            </tr>
		</table>
	<?php
}

function global_site_search_site_admin_options_process() {

	update_site_option( 'global_site_search_per_page' , $_POST['global_site_search_per_page']);
	update_site_option( 'global_site_search_background_color' , trim( $_POST['global_site_search_background_color'] ));
	update_site_option( 'global_site_search_alternate_background_color' , trim( $_POST['global_site_search_alternate_background_color'] ));
	update_site_option( 'global_site_search_border_color' , trim( $_POST['global_site_search_border_color'] ));
}

function global_site_search_rewrite($wp_rewrite){
	global $global_site_search_base;
    $global_site_search_rules = array(
        $global_site_search_base . '/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?pagename=' . $global_site_search_base,
        $global_site_search_base . '/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?pagename=' . $global_site_search_base,
        $global_site_search_base . '/([^/]+)/([^/]+)/?$' => 'index.php?pagename=' . $global_site_search_base,
        $global_site_search_base . '/([^/]+)/?$' => 'index.php?pagename=' . $global_site_search_base
    );
    $wp_rewrite->rules = $global_site_search_rules + $wp_rewrite->rules;
	return $wp_rewrite;
}

function global_site_search_url_parse(){
	global $wpdb, $current_site, $global_site_search_base;
	$global_site_search_url = $_SERVER['REQUEST_URI'];
	if ( $current_site->path != '/' ) {
		$global_site_search_url = str_replace('/' . $current_site->path . '/', '', $global_site_search_url);
		$global_site_search_url = str_replace($current_site->path . '/', '', $global_site_search_url);
		$global_site_search_url = str_replace($current_site->path, '', $global_site_search_url);
	}
	$global_site_search_url = ltrim($global_site_search_url, "/");
	$global_site_search_url = rtrim($global_site_search_url, "/");
	$global_site_search_url = ltrim($global_site_search_url, $global_site_search_base);
	$global_site_search_url = ltrim($global_site_search_url, "/");

	list($global_site_search_1, $global_site_search_2, $global_site_search_3, $global_site_search_4) = explode("/", $global_site_search_url);

	$page_type = '';
	$page_subtype = '';
	$page = '';
	$post = '';

	$page_type = 'landing';
	$phrase = $_POST['phrase'];
	if ( empty( $phrase ) ) {
		$phrase = $global_site_search_1;
		$page = $global_site_search_2;
		if ( empty( $page ) ) {
			$page = 1;
		}
	} else {
		$page = $global_site_search_2;
		if ( empty( $page ) ) {
			$page = 1;
		}
	}
	$phrase = urldecode( $phrase );

	$global_site_search['page_type'] = $page_type;
	$global_site_search['page'] = $page;
	$global_site_search['phrase'] = $phrase;

	return $global_site_search;
}

//------------------------------------------------------------------------//
//---Output Functions-----------------------------------------------------//
//------------------------------------------------------------------------//

function global_site_search_title_output($title, $post_ID = '') {
	global $wpdb, $current_site, $post, $global_site_search_base;
	if ( $post->post_name == $global_site_search_base && $post_ID == $post->ID) {
		$global_site_search = global_site_search_url_parse();
		if ( $global_site_search['page_type'] == 'landing' ) {
			if ( $global_site_search['page'] > 1 ) {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $global_site_search_base . '/">' . __('Site Search', 'globalsitesearch') . '</a> &raquo; ' . '<a href="http://' . $current_site->domain . $current_site->path . $global_site_search_base . '/' . urlencode($global_site_search['phrase']) .  '/' . $global_site_search['page'] . '/">' . $global_site_search['page'] . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $global_site_search_base . '/">' . __('Site Search', 'globalsitesearch') . '</a>';
			}
		}
	}
	return $title;
}

function global_site_search_output($content) {
	global $wpdb, $current_site, $post, $global_site_search_base, $members_directory_base;
	if ( $post->post_name == $global_site_search_base ) {
		$global_site_search_per_page = get_site_option('global_site_search_per_page', '10');
		$global_site_search_background_color = get_site_option('global_site_search_background_color', '#F2F2EA');
		$global_site_search_alternate_background_color = get_site_option('global_site_search_alternate_background_color', '#FFFFFF');
		$global_site_search_border_color = get_site_option('global_site_search_border_color', '#CFD0CB');
		$global_site_search = global_site_search_url_parse();
		if ( $global_site_search['page_type'] == 'landing' ) {
			//=====================================//
			if ($global_site_search['page'] == 1){
				$start = 0;
			} else {
				$math = $global_site_search['page'] - 1;
				$math = $global_site_search_per_page * $math;
				$start = $math;
			}
			$author_id = $wpdb->get_var("SELECT ID FROM " . $wpdb->base_prefix . "users WHERE user_login = '" . $global_site_search['phrase'] . "'");
			if ( is_numeric( $author_id ) && $author_id != 0 ) {
				$author_search = " OR post_author = '" . $author_id . "'";
			}
			$query = "SELECT * FROM " . $wpdb->base_prefix . "site_posts WHERE ( post_title LIKE '%" . $global_site_search['phrase'] . "%' OR post_content LIKE '%" . $global_site_search['phrase'] . "%'" . $author_search . " ) AND blog_public = 1 ORDER BY site_post_id DESC";
			$query .= " LIMIT " . intval( $start ) . ", " . intval( $global_site_search_per_page );
			if ( !empty( $global_site_search['phrase'] ) ) {
				$posts = $wpdb->get_results( $query, ARRAY_A );
			}
			//=====================================//
			$search_form_content = global_site_search_search_form_output('', $global_site_search['phrase']);
			if ( count( $posts ) > 0 ) {
				if ( count( $posts ) < $global_site_search_per_page ) {
					$next = 'no';
				} else {
					$next = 'yes';
				}
				$navigation_content = global_site_search_landing_navigation_output('', $global_site_search_per_page, $global_site_search['page'], $global_site_search['phrase'], $next);
			}
			$content .= $search_form_content;
			$content .= '<br />';
			if ( count( $posts ) > 0 ) {
				$content .= $navigation_content;
			}
			if ( count( $posts ) > 0 ) {
				$content .= '<div style="float:left; width:100%">';
				$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
					$content .= '<tr>';
						$content .= '<td style="background-color:' . $global_site_search_background_color . '; border-bottom-style:solid; border-bottom-color:' . $global_site_search_border_color . '; border-bottom-width:1px; font-size:12px;" width="10%"> </td>';
						$content .= '<td style="background-color:' . $global_site_search_background_color . '; border-bottom-style:solid; border-bottom-color:' . $global_site_search_border_color . '; border-bottom-width:1px; font-size:12px;" width="90%"><center><strong>' .  __('Posts', 'globalsitesearch') . '</strong></center></td>';
					$content .= '</tr>';
			}
				//=================================//
				$avatar_default = get_option('avatar_default');
				$tic_toc = 'toc';
				//=================================//
				if ( count( $posts ) > 0 ) {
					foreach ($posts as $post){
						//=============================//
						$post_author_display_name = $wpdb->get_var("SELECT display_name FROM " . $wpdb->base_prefix . "users WHERE ID = '" . $post['post_author'] . "'");
						if ($tic_toc == 'toc'){
							$tic_toc = 'tic';
						} else {
							$tic_toc = 'toc';
						}
						if ($tic_toc == 'tic'){
							$bg_color = $global_site_search_alternate_background_color;
						} else {
							$bg_color = $global_site_search_background_color;
						}
						//=============================//
						$content .= '<tr>';
							$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px;" valign="top" width="10%"><center><a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . get_avatar($post['post_author'], 32, $avatar_default) . '</a></center></td>';
							$content .= '<td style="background-color:' . $bg_color . ';" width="90%">';
							if ( function_exists('members_directory_site_admin_options') ) {
								$post_author_nicename = $wpdb->get_var("SELECT user_nicename FROM " . $wpdb->base_prefix . "users WHERE ID = '" . $post['post_author'] . "'");
								$content .= '<strong><a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $post_author_nicename . '/">' . $post_author_display_name . '</a> ' . __('Wrote', 'globalsitesearch') . ': </strong> ';
							} else {
								$content .= '<strong' . $post_author_display_name . ' ' . __('wrote') . ': </strong> ';
							}
							$content .= '<strong><a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . $post['post_title'] . '</a></strong><br />';
							$content .= substr(strip_tags($post['post_content'],'<a>'),0, 250) . ' (<a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . __('More', 'globalsitesearch') . '</a>)';
							$content .= '</td>';
						$content .= '</tr>';
					}
				}
				//=================================//
			if ( count( $posts ) > 0 ) {
				$content .= '</table>';
				$content .= '</div>';
				$content .= $navigation_content;
			}
			if ( count( $posts ) == 0 ) {
				$content .= '<p>';
				$content .= '<center>';
				$content .= __('Nothing found for search term(s).', 'globalsitesearch');
				$content .= '</center>';
				$content .= '</p>';
			}
		} else {
			$content = __('Invalid page.', 'globalsitesearch');
		}
	}
	return $content;
}

function global_site_search_search_form_output($content, $phrase) {
	global $wpdb, $current_site, $global_site_search_base;
	if ( !empty( $phrase ) ) {
		$content .= '<form action="' . $current_site->path . $global_site_search_base . '/' . urlencode( $phrase ) . '/" method="post">';
	} else {
		$content .= '<form action="' . $current_site->path . $global_site_search_base . '/" method="post">';
	}
		$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
		$content .= '<tr>';
		    $content .= '<td style="font-size:12px; text-align:left;" width="80%">';
				$content .= '<input name="phrase" style="width: 100%;" type="text" value="' . $phrase . '">';
			$content .= '</td>';
			$content .= '<td style="font-size:12px; text-align:right;" width="20%">';
				$content .= '<input name="Submit" value="' . __('Search', 'globalsitesearch') . '" type="submit">';
			$content .= '</td>';
		$content .= '</tr>';
		$content .= '</table>';
	$content .= '</form>';
	return $content;
}

function global_site_search_landing_navigation_output($content, $per_page, $page, $phrase, $next){
	global $wpdb, $current_site, $global_site_search_base;
	$author_id = $wpdb->get_var("SELECT ID FROM " . $wpdb->base_prefix . "users WHERE user_login = '" . $phrase . "'");
	if ( is_numeric( $author_id ) && $author_id != 0 ) {
		$author_search = " OR post_author = '" . $author_id . "'";
	}
	$post_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "site_posts WHERE ( post_title LIKE '%" . $phrase . "%' OR post_content LIKE '%" . $phrase . "%'" . $author_search . " ) AND blog_public = 1 ORDER BY site_post_id DESC");
	$post_count = $post_count - 1;

	//generate page div
	//============================================================================//
	$total_pages = global_site_search_roundup($post_count / $per_page, 0);
	$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
	$content .= '<tr>';
	$showing_low = ($page * $per_page) - ($per_page - 1);
	if ($total_pages == $page){
		//last page...
		//$showing_high = $post_count - (($total_pages - 1) * $per_page);
		$showing_high = $post_count;
	} else {
		$showing_high = $page * $per_page;
	}

    $content .= '<td style="font-size:12px; text-align:left;" width="50%">';
	if ($post_count > $per_page){
	//============================================================================//
		if ($page == '' || $page == '1'){
			//$content .= __('Previous');
		} else {
		$previous_page = $page - 1;
		$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $global_site_search_base . '/' . urlencode( $phrase ) . '/' . $previous_page . '/">&laquo; ' . __('Previous', 'globalsitesearch') . '</a>';
		}
	//============================================================================//
	}
	$content .= '</td>';
    $content .= '<td style="font-size:12px; text-align:right;" width="50%">';
	if ($post_count > $per_page){
	//============================================================================//
		if ( $next != 'no' ) {
			if ($page == $total_pages){
				//$content .= __('Next');
			} else {
				if ($total_pages == 1){
					//$content .= __('Next');
				} else {
					$next_page = $page + 1;
				$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $global_site_search_base . '/' . urlencode( $phrase ) . '/' . $next_page . '/">' . __('Next', 'globalsitesearch') . ' &raquo;</a>';
				}
			}
		}
	//============================================================================//
	}
    $content .= '</td>';
	$content .= '</tr>';
    $content .= '</table>';
	return $content;
}

//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//

//------------------------------------------------------------------------//
//---Support Functions----------------------------------------------------//
//------------------------------------------------------------------------//

function global_site_search_roundup($value, $dp){
    return ceil($value*pow(10, $dp))/pow(10, $dp);
}

?>