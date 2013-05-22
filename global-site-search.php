<?php
/*
Plugin Name: Global Site Search
Plugin URI: http://premium.wpmudev.org/project/global-site-search
Description: A magnificent plugin that allows global search across all blogs on your WordPress Multisite / BuddyPress install with ease!
Author: Barry (Incsub)
Version: 3.0.1
Author URI: http://premium.wpmudev.org
WDP ID: 102
Network: true


Copyright 2013 Incsub (http://incsub.com)

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

class global_site_search {

	//------------------------------------------------------------------------//
	//---Config---------------------------------------------------------------//
	//------------------------------------------------------------------------//
	var $build = 4;

	var $db;

	var $global_site_search_base = 'site-search'; //domain.tld/BASE/ Ex: domain.tld/user/

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		//------------------------------------------------------------------------//
		//---Hook-----------------------------------------------------------------//
		//------------------------------------------------------------------------//

		if($this->db->blogid == 1 || $this->db->blogid == 0) {
			if( get_option('gss_installed', 0) < $this->build || get_option('gss_installed', 0) == 'yes' ) {
				add_action('init', array( &$this, 'initialise_plugin') );
				//$this->initialise_plugin();
			}

			// Add the rewrites
			add_action('generate_rewrite_rules', array(&$this, 'add_rewrite'));
			add_filter('query_vars', array(&$this, 'add_queryvars'));

			add_filter('the_content', array(&$this, 'global_site_search_output'), 20);
			//add_filter('the_title', array(&$this, 'global_site_search_title_output'), 99, 2);

		}

		add_action( 'wpmu_options', array(&$this, 'global_site_search_site_admin_options') );
		add_action( 'update_wpmu_options', array(&$this, 'global_site_search_site_admin_options_process') );

		add_action( 'plugins_loaded', array(&$this, 'global_site_search_site_load_textdomain') );

	}

	function global_site_search() {
		$this->__construct();
	}

	//------------------------------------------------------------------------//
	//---Functions------------------------------------------------------------//
	//------------------------------------------------------------------------//

	function initialise_plugin() {
		$this->global_site_search_flush_rules();

		$this->global_site_search_page_setup();

		update_option('gss_installed', $this->build);
	}

	function global_site_search_flush_rules() {
    	global $wp_rewrite;

        $wp_rewrite->flush_rules();
	}

	function add_queryvars($vars) {
		// This function add the namespace (if it hasn't already been added) and the
		// eventperiod queryvars to the list that WordPress is looking for.
		// Note: Namespace provides a means to do a quick check to see if we should be doing anything

		if(!in_array('namespace',$vars)) $vars[] = 'namespace';
		if(!in_array('search',$vars)) $vars[] = 'search';
		if(!in_array('paged',$vars)) $vars[] = 'paged';
		if(!in_array('type',$vars)) $vars[] = 'type';

		return $vars;
	}

	function add_rewrite( $wp_rewrite ) {

		// This function adds in the api rewrite rules
		// Note the addition of the namespace variable so that we know these are vent based
		// calls
		$new_rules = array();

		$new_rules[$this->global_site_search_base . '/(.+)/page/?([0-9]{1,})'] = 'index.php?namespace=gss&search=' . $wp_rewrite->preg_index(1) . '&paged=' . $wp_rewrite->preg_index(2) . '&type=search&pagename=' . $this->global_site_search_base;
		$new_rules[$this->global_site_search_base . '/(.+)'] = 'index.php?namespace=gss&search=' . $wp_rewrite->preg_index(1) . '&type=search&pagename=' . $this->global_site_search_base;
		$new_rules[$this->global_site_search_base . ''] = 'index.php?namespace=gss&type=search&pagename=' . $this->global_site_search_base;

		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;

		return $wp_rewrite;

	}

	function global_site_search_page_setup() {
		global $wpdb, $user_ID;

		if ( get_site_option('global_site_search_page_setup') != 'complete' && is_super_admin() ) {

			$page_id = get_site_option('global_site_search_page');
			if(empty($page_id)) {
				// a page hasn't been set - so check if there is already one with the base name
				$page_id = $this->db->get_var("SELECT ID FROM {$this->db->posts} WHERE post_name = '" . $this->global_site_search_base . "' AND post_type = 'page'");
				if ( empty( $page_id ) ) {
					// Doesn't exist so create the page
					$post = array(	"post_author"		=>	$user_ID,
									"post_date"			=>	current_time( 'mysql' ),
									"post_date_gmt"		=>	current_time( 'mysql' ),
									"post_content"		=>	'',
									"post_title"		=>	__('Site Search', 'globalsitesearch'),
									"post_excerpt"		=>	'',
									"post_status"		=>	'publish',
									"comment_status"	=>	'closed',
									"ping_status"		=>	'closed',
									"post_password"		=>	'',
									"post_name"			=>	$this->global_site_search_base,
									"to_ping"			=>	'',
									"pinged"			=>	'',
									"post_modified"		=>	current_time( 'mysql' ),
									"post_modified_gmt"	=>	current_time( 'mysql' ),
									"post_content_filtered"	=>	'',
									"post_parent"			=>	0,
									"menu_order"			=>	0,
									"post_type"				=>	'page',
									"comment_count"			=>	0
								);
					$page_id = wp_insert_post( $post );

				}
				update_site_option( 'global_site_search_page', $page_id );
			}

			update_site_option('global_site_search_page_setup', 'complete');
		}
	}

	function global_site_search_site_load_textdomain() {
		// Load the text-domain
		$locale = apply_filters( 'globalsitesearch_locale', get_locale() );
		$mofile = plugin_basename(dirname(__FILE__) . "/languages/globalsitesearch-$locale.mo");

		if ( file_exists( $mofile ) )
			load_plugin_textdomain( 'globalsitesearch', false, $mofile );
	}

	function global_site_search_site_admin_options() {

		$global_site_search_per_page = get_site_option('global_site_search_per_page', '10');
		$global_site_search_background_color = get_site_option('global_site_search_background_color', '#F2F2EA');
		$global_site_search_alternate_background_color = get_site_option('global_site_search_alternate_background_color', '#FFFFFF');
		$global_site_search_border_color = get_site_option('global_site_search_border_color', '#CFD0CB');
		$global_site_search_post_type = get_site_option('global_site_search_post_type', 'post');

		?>
			<h3><?php _e('Site Search') ?></h3>
			<table class="form-table">
	            <tr valign="top">
	                <th width="33%" scope="row"><?php _e('Listing Per Page', 'globalsitesearch') ?></th>
	                <td>
					<select name="global_site_search_per_page" id="global_site_search_per_page">
					   <option value="5" <?php selected( $global_site_search_per_page, '5' ); ?> ><?php _e('5', 'globalsitesearch'); ?></option>
					   <option value="10" <?php selected( $global_site_search_per_page, '10' ); ?> ><?php _e('10', 'globalsitesearch'); ?></option>
					   <option value="15" <?php selected( $global_site_search_per_page, '15' ); ?> ><?php _e('15', 'globalsitesearch'); ?></option>
					   <option value="20" <?php selected( $global_site_search_per_page, '20' ); ?> ><?php _e('20', 'globalsitesearch'); ?></option>
					   <option value="25" <?php selected( $global_site_search_per_page, '25' ); ?> ><?php _e('25', 'globalsitesearch'); ?></option>
					   <option value="30" <?php selected( $global_site_search_per_page, '30' ); ?> ><?php _e('30', 'globalsitesearch'); ?></option>
					   <option value="35" <?php selected( $global_site_search_per_page, '35' ); ?> ><?php _e('35', 'globalsitesearch'); ?></option>
					   <option value="40" <?php selected( $global_site_search_per_page, '40' ); ?> ><?php _e('40', 'globalsitesearch'); ?></option>
					   <option value="45" <?php selected( $global_site_search_per_page, '45' ); ?> ><?php _e('45', 'globalsitesearch'); ?></option>
					   <option value="50" <?php selected( $global_site_search_per_page, '50' ); ?> ><?php _e('50', 'globalsitesearch'); ?></option>
					</select></td>
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

				<tr valign="top">
		                <th width="33%" scope="row"><?php _e('List Post Type', 'globalsitesearch') ?></th>
		                <td>
						<select name="global_site_search_post_type" id="global_site_search_post_type">
						   <option value="all" <?php selected( $global_site_search_post_type, 'all' ); ?> ><?php _e('all', 'globalsitesearch'); ?></option>
							<?php
							$post_types = $this->global_site_search_get_post_types();
							if(!empty($post_types)) {
								foreach($post_types as $r) {
									?>
									<option value="<?php echo $r; ?>" <?php selected( $global_site_search_post_type, $r ); ?> ><?php _e($r, 'globalsitesearch'); ?></option>
									<?php
								}
							}
							?>
						</select></td>
		        </tr>
			</table>
		<?php
	}

	function global_site_search_get_post_types() {

		$sql = "SELECT post_type FROM " . $this->db->base_prefix . "network_posts GROUP BY post_type";

		$results = $this->db->get_col( $sql );

		return $results;
	}

	function global_site_search_site_admin_options_process() {

		update_site_option( 'global_site_search_per_page' , $_POST['global_site_search_per_page']);
		update_site_option( 'global_site_search_background_color' , trim( $_POST['global_site_search_background_color'] ));
		update_site_option( 'global_site_search_alternate_background_color' , trim( $_POST['global_site_search_alternate_background_color'] ));
		update_site_option( 'global_site_search_border_color' , trim( $_POST['global_site_search_border_color'] ));
		update_site_option( 'global_site_search_post_type', $_POST['global_site_search_post_type'] );

	}

	//------------------------------------------------------------------------//
	//---Output Functions-----------------------------------------------------//
	//------------------------------------------------------------------------//

	function global_site_search_title_output($title, $post_ID = '') {
		global $wpdb, $current_site, $post, $global_site_search_base;

		global $wp_query;

		if ( isset($wp_query->query_vars['namespace']) && $wp_query->query_vars['namespace'] == 'gss' && $wp_query->query_vars['type'] == 'search' ) {

			if( isset($wp_query->query_vars['paged']) && $wp_query->query_vars['paged'] > 1) {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $this->global_site_search_base . '/">' . __('Site Search', 'globalsitesearch') . '</a> &raquo; ' . '<a href="http://' . $current_site->domain . $current_site->path . $this->global_site_search_base . '/' . urlencode((isset($wp_query->query_vars['search'])) ? $wp_query->query_vars['search'] : '') .  '/page/' . $wp_query->query_vars['paged'] . '/">' . $wp_query->query_vars['paged']. '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $this->global_site_search_base . '/">' . __('Site Search', 'globalsitesearch') . '</a>';
			}

		}

		return $title;
	}

	function global_site_search_output($content) {
		global $wpdb, $current_site, $post, $global_site_search_base, $members_directory_base;

		global $network_query, $network_post;

		global $wp_query;

		if ( isset($wp_query->query_vars['namespace']) && $wp_query->query_vars['namespace'] == 'gss' && $wp_query->query_vars['type'] == 'search' ) {

			// We are on a search results page

			$global_site_search_per_page = get_site_option('global_site_search_per_page', '10');
			$global_site_search_background_color = get_site_option('global_site_search_background_color', '#F2F2EA');
			$global_site_search_alternate_background_color = get_site_option('global_site_search_alternate_background_color', '#FFFFFF');
			$global_site_search_border_color = get_site_option('global_site_search_border_color', '#CFD0CB');

			$global_site_search_post_type = get_site_option('global_site_search_post_type', 'post');

			//=====================================//
			$parameters = array();

			// Set the page number
			if( !isset($wp_query->query_vars['paged']) || $wp_query->query_vars['paged'] <= 1) {
				$page = 1;
				$start = 0;
			} else {
				$page = $wp_query->query_vars['paged'];
				$math = $wp_query->query_vars['paged'] - 1;
				$math = $global_site_search_per_page * $math;
				$start = $math;
			}

			$phrase = (isset($wp_query->query_vars['search'])) ? urldecode($wp_query->query_vars['search']) : '';
			if(empty($phrase)) {
				if(isset($_POST['phrase'])) {
					$phrase = urldecode($_POST['phrase']);
				}
			}

			$theauthor = get_user_by( 'login', $phrase );
			if(is_object($theauthor)) {
				$author_id = $theauthor->ID;
			}

			if ( isset( $author_id ) && is_numeric( $author_id ) && $author_id != 0 ) {
				$parameters['author'] = $author_id;
			} else {
				$parameters['s'] = $phrase;
			}

			if($global_site_search_post_type != 'all') {
				$parameters['post_type'] = $global_site_search_post_type;
			} else {
				$post_types = $this->global_site_search_get_post_types();
				$parameters['post_type'] = $post_types;
			}

			// Add in the start and end numbers
			$parameters['posts_per_page'] = intval( $global_site_search_per_page );
			$parameters['paged'] = intval( $page );

			//=====================================//
			$search_form_content = $this->global_site_search_search_form_output('', $phrase);

			$content .= $search_form_content;
			$content .= '<br />';

			if(!empty($phrase)) {
				$network_query_posts = network_query_posts( $parameters );

				//found_posts
				if( network_have_posts() && isset($GLOBALS['network_query']->found_posts) && $GLOBALS['network_query']->found_posts > intval( $global_site_search_per_page ) ) {
					$next = 'yes';
					$navigation_content = $this->new_pagination( $GLOBALS['network_query'], $current_site->path . $this->global_site_search_base . '/' . urlencode($phrase) );
				}

				if ( network_have_posts() ) {
					$content .= (isset($navigation_content)) ? $navigation_content : '';

					$content .= '<div style="float:left; width:100%">';
					$content .= '<table border="0" width="100%" bgcolor="">';
					$content .= '<tr>';
					$content .= '<td style="background-color:' . $global_site_search_background_color . '; border-bottom-style:solid; border-bottom-color:' . $global_site_search_border_color . '; border-bottom-width:1px; font-size:12px;" width="10%"> </td>';
					$content .= '<td style="background-color:' . $global_site_search_background_color . '; border-bottom-style:solid; border-bottom-color:' . $global_site_search_border_color . '; border-bottom-width:1px; font-size:12px;" width="90%"><center><strong>' .  __('Posts', 'globalsitesearch') . '</strong></center></td>';
					$content .= '</tr>';

					// Search results

					$avatar_default = get_option('avatar_default');
					$tic_toc = 'toc';

					while( network_have_posts()) {
						network_the_post();

						//=============================//
						$author_id = network_get_the_author_id();
						$the_author = get_user_by( 'id', $author_id );

						if(!$the_author) {
							$post_author_display_name = __('Unknown', 'globalsitesearch');
						} else {
							$post_author_display_name = $the_author->display_name;
						}

						$tic_toc = ($tic_toc == 'toc') ? 'tic' : 'toc';
						$bg_color = ($tic_toc == 'tic') ? $global_site_search_alternate_background_color : $global_site_search_background_color;

						//=============================//
						$content .= '<tr>';
							$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px; text-align: center;" valign="top" width="10%"><a style="text-decoration:none;" href="' . network_get_permalink() . '">' . get_avatar( $author_id, 32, $avatar_default ) . '</a></td>';
							$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px; vertical-align: top;" width="90%" valign="top">';
							if ( function_exists('members_directory_site_admin_options') ) {
								$post_author_nicename = $the_author->user_nicename;
								$content .= '<strong><a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $post_author_nicename . '/">' . $post_author_display_name . '</a> ' . __(' wrote', 'globalsitesearch') . ': </strong> ';
							} else {
								$content .= '<strong>' . $post_author_display_name . __(' wrote', 'globalsitesearch') . ': </strong> ';
							}
							$content .= '<strong><a style="text-decoration:none;" href="' . network_get_permalink() . '">' . network_get_the_title() . '</a></strong><br />';
							$the_content = network_get_the_content();
							$content .= substr(strip_tags( $the_content ),0, 250) . ' (<a href="' . network_get_permalink() . '">' . __('More', 'globalsitesearch') . '</a>)';
							$content .= '</td>';
						$content .= '</tr>';

					}


					$content .= '</table>';
					$content .= '</div>';
					$content .= (isset($navigation_content)) ? $navigation_content : '';
				} else {
					$content .= '<p>';
					$content .= '<center>';
					$content .= __('Nothing found for search term(s).', 'globalsitesearch');
					$content .= '</center>';
					$content .= '</p>';
				}

			}

		}

		return $content;
	}

	function new_pagination( $wp_query, $mainlink = '' ) {

		if(empty($wp_query->query_vars['paged'])) {
			$paged = 1;
		} else {
			$paged = $wp_query->query_vars['paged'];
		}

		if((int) $wp_query->max_num_pages > 1) {

			// we can draw the pages
			$html = '';

			$html .= "<div class='gssnav'>";

			$list_navigation = paginate_links( array(
				'base' => trailingslashit($mainlink) . '%_%',
				'format' => 'page/%#%',
				'total' => $wp_query->max_num_pages,
				'current' => $paged,
				'prev_next' => true
			));

			$html .= $list_navigation;

			$html .= "</div>";

			return $html;
		}
	}

	function global_site_search_search_form_output($content, $phrase) {

		global $current_site, $global_site_search_base;

		$content .= '<form action="' . $current_site->path . $this->global_site_search_base . '/" method="post">';
			$content .= '<table border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
			$content .= '<tr>';
			    $content .= '<td style="font-size:12px; text-align:left;" width="80%">';
					$content .= '<input name="phrase" style="width: 100%;" type="text" value="' . $phrase . '" />';
				$content .= '</td>';
				$content .= '<td style="font-size:12px; text-align:right;" width="20%">';
					$content .= '<input name="Submit" value="' . __('Search', 'globalsitesearch') . '" type="submit" />';
				$content .= '</td>';
			$content .= '</tr>';
			$content .= '</table>';
		$content .= '</form>';
		return $content;
	}

}

$global_site_search = new global_site_search();


/**
 * Global Site Search class.
 *
 */
class Global_Site_Search_Widget extends WP_Widget {
  /**
   * Widget setup.
   */
  function Global_Site_Search_Widget() {
    /* Widget settings. */
    $widget_ops = array( 'classname' => 'global-site-search', 'description' => __('Global Site Search Widget', 'globalsitesearch') );

    /* Widget control settings. */
    $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'global-site-search-widget' );

    /* Create the widget. */
    $this->WP_Widget( 'global-site-search-widget', __('Global Site Search Widget', 'globalsitesearch'), $widget_ops, $control_ops );
  }

  /**
   * How to display the widget on the screen.
   */
  function widget( $args, $instance ) {

	global $global_site_search, $wp_query;

    extract( $args );

    /* Our variables from the widget settings. */
    $title = apply_filters('widget_title', $instance['title'] );

    /* Before widget (defined by themes). */
    echo $before_widget;

    /* Display the widget title if one was input (before and after defined by themes). */
    if ( $title )
      echo $before_title . $title . $after_title;

    $phrase = '';
	if ( isset($wp_query->query_vars['namespace']) && $wp_query->query_vars['namespace'] == 'gss' && $wp_query->query_vars['type'] == 'search' ) {
		$phrase = (isset($wp_query->query_vars['search'])) ? urldecode($wp_query->query_vars['search']) : '';
		if(empty($phrase)) {
			if(isset($_POST['phrase'])) {
				$phrase = urldecode($_POST['phrase']);
			}
		}
	}

    echo $global_site_search->global_site_search_search_form_output('', $phrase);

    /* After widget (defined by themes). */
    echo $after_widget;
  }

  /**
   * Update the widget settings.
   */
  function update( $new_instance, $old_instance ) {
    $instance = $old_instance;

    /* Strip tags for title and name to remove HTML (important for text inputs). */
    $instance['title'] = strip_tags( $new_instance['title'] );

    return $instance;
  }

  /**
   * Displays the widget settings controls on the widget panel.
   * Make use of the get_field_id() and get_field_name() function
   * when creating your form elements. This handles the confusing stuff.
   */
  function form( $instance ) {
    /* Set up some default widget settings. */
    $defaults = array( 'title' => __('Global Site Search', 'globalsitesearch') );
    $instance = wp_parse_args( (array) $instance, $defaults ); ?>

    <!-- Widget Title: Text Input -->
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?>
      <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" /></label>
    </p>
    <?php
  }
}

function global_site_search_roundup($value, $dp){
    return ceil($value*pow(10, $dp))/pow(10, $dp);
}

/**
 * Register our widget.
 *
 */
function global_site_search_load_widgets() {
    register_widget( 'Global_Site_Search_Widget' );
}

add_action( 'widgets_init', 'global_site_search_load_widgets' );
