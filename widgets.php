<?php

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

		global_site_search_form();

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

add_action( 'widgets_init', 'global_site_search_load_widgets' );
function global_site_search_load_widgets() {
	if ( in_array( get_current_blog_id(), global_site_search_get_allowed_blogs() ) ) {
		register_widget( 'Global_Site_Search_Widget' );
	}
}