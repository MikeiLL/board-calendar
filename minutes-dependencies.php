<?php
/**
 * Contains functions that provide common functionality for semesterly post types.
 *
 * This file follows the coding standards detailed here:
 * http://codex.wordpress.org/WordPress_Coding_Standards
 */
/**
 * Creates an SQL WHERE clause for retriving posts that occurred during a
 * spring semester. The year of the spring semester comes from calling
 * get_query_var( 'year' ).
 *
 * This function is intended to be used with the posts_where filter.
 * Usage: add_filter( 'posts_where', 'filter_posts_by_spring' );
 *
 * A post is deemed to be from the spring semester if it occured between
 * January 1st and July 1st, inclusive.
 *
 * @param string $where The WHERE clause for the current query.
 * @return string A WHERE clause which limits the post date to the spring semester.
 */
function filter_posts_by_spring( $where ) {
	global $wpdb;
	// Escape the year query var for safe use in SQL
	$year = $wpdb->escape( get_query_var( 'year' ) );
	$where .= " AND post_date >= '$year-01-01'";
	$where .= " AND post_date <= '$year-07-01'";

	return $where;
}
/**
 * Creates an SQL WHERE clause for retriving posts that occurred during a
 * fall semester. The year of the fall semester comes from calling
 * get_query_var( 'year' ).
 *
 * This function is intended to be used with the posts_where filter.
 * Usage: add_filter( 'posts_where', 'filter_posts_by_fall' );
 *
 * A post is deemed to be from the fall semester if it occured between
 * July 2nd and December 31st, inclusive.
 *
 * @param string $where The WHERE clause for the current query.
 * @return string A WHERE clause which limits the post date to the fall semester.
 */
function filter_posts_by_fall( $where ) {
	global $wpdb;
	// Escape the year query var for safe use in SQL
	$year = $wpdb->escape( get_query_var( 'year' ) );
	$where .= " AND post_date >= '$year-07-02'";
	$where .= " AND post_date <= '$year-12-31'";
	return $where;
}
