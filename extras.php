<?php
namespace mZoo\BoardCal\Extras;

/**
 * Returns the post type for the queried data on the current page.
 * This is useful for loading template parts for custom post types
 * before entering the loop and outputting those posts.
 *
 * @return string The post type of the queried data for the current page.
 */
function get_post_type_outside_loop() {
	global $wp_query;
	if ( isset( $wp_query->query['post_type'] ) )
		return $wp_query->query['post_type'];
	return '';
}

?>
