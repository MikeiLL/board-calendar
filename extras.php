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

/**
 * Filters the page title (<title> element) on a semesterly archive page to
 * always display as "{Semester} {Year} $post_type_title". Without this filter
 * the archives may simply display the year as the title.
 *
 * This method is intended to be used with the wp_title hook. As such, the $title,
 * $sep, and $seplocation parameters are to maintain consistency with wp_title().
 *
 * This function should not be added as a filter directly. Instead, a wrapper
 * function should be created with the signature
 * ( $title, $sep = '&raquo;', $seplocation = 'right' ) that makes a call to
 * this function specifying $post_type and $post_type_title. The wrapper function
 * should then be added as a filter by calling
 * add_filter( 'wp_title', 'my_semesterly_archive_title_filter_wrapper', 10, 3);
 *
 * Because this is a filter, don't forget to return the results of the call to
 * this function from your wrapper!
 *
 * @param string $title Title of the page.
 * @param mixed $post_type A string or array of strings specifying which post types this filter should apply to.
 * @param string $post_type_title The text to display in the title for the post type. See the documentation for this function on how the title is formatted for a better idea of where this will appear.
 * @param string $sep (optional) How to separate the various items within the page title. Default is '»'.
 * @param string $seplocation (optional) Direction to display title, 'right'.
 */
function semesterly_archive_title_filter( $title, $post_type, $post_type_title, $sep = '&raquo;', $seplocation = 'right' ) {
	global $wp_query;
	// Only filter the title if this is the archive page for the post type
	if ( $wp_query->query['post_type'] == $post_type && $wp_query->is_post_type_archive ) {
		// Set the title as "{semester} {year} $post_type_title"
		$semester = ucfirst( get_query_var( 'semester' ) );
		$year = get_query_var( 'year' );
		$title = "$semester $year $post_type_title";
		// Return the separator on the correct side
		if ( 'right' == $seplocation )
			return "$title $sep ";
		return " $sep $title";
	}
	// The title will be set to the value returned by this filter,
	// so we must return the title even if we did nothing to it.
	return $title;
}

?>
