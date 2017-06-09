<?php

use Roots\Sage\Extras;

/**
 * Creates the Board and Floor Minutes post types.
 * This file also registers functions for filtering these
 * post types' archives by semester.
 *
 * This file also hooks a function to set any menu item with the title
 * "Meeting Minutes" as the current menu item on the minutes archive page.
 *
 * The combined Board and Floor Minutes archive can be found at
 * {home_url}/minutes
 * (This assumes that permalinks are "pretty" and set to be by post name)
 *
 * The Board Minutes post type is registered as "board_minutes".
 * The Floor Minutes post type is registered as "floor_minutes".
 *
 * This file follows the coding standards detailed here:
 * http://codex.wordpress.org/WordPress_Coding_Standards
 * from https://github.com/HBC-Rochester/Website/tree/master/wp-content/themes/cif/semesterly-post-types
 */

/**
 * Registers the Board and Floor Minutes post types.
 */
function register_minutes_post_type() {
	/**
	 * Register a custom post type for board meeting minutes.
	 * See http://codex.wordpress.org/Function_Reference/register_post_type
	 * for more information on how this works.
	 */
	$board_minute_labels = array(
		'name'				 => 'Board Minutes',
		'singular_name'		 => 'Board Minutes',
		'add_new_item'		 => 'Add New Board Minutes',
		'edit_item'			 => 'Edit Board Minutes',
		'new_item'			 => 'New Board Minutes',
		'view_item'			 => 'View Board Minutes',
		'search_items'		 => 'Search Board Minutes',
		'not_found'			 => 'No board minutes found.',
		'not_found_in_trash' => 'No board minutes found in Trash.',
	);

	$board_minute_args = array(
		'labels'		=> $board_minute_labels,
		'description'	=> 'Minutes from HBC board meetings.',
		'public'		=> true,
		'menu_position'	=> 5, // Appears below Posts in the admin sidebar
		'has_archive'	=> true,
	);

	register_post_type( 'board_minutes', $board_minute_args );

//TODO Move me to Extras or specific to CPTs script
  function remove_title_box()
  {
    remove_post_type_support('board_minutes', 'title');
    remove_post_type_support('floor_minutes', 'title');
    remove_post_type_support('board_agenda', 'title');
    remove_post_type_support('general_agenda', 'title');
  }
  add_action("admin_init", "remove_title_box");

  function brnsville_register_meta_boxes($postType) {
    $types = array('board_minutes', 'floor_minutes');
    if(in_array($postType, $types)){
      add_meta_box(
          'brnsville-minutes-instruction',
          'Title Generated Automatically',
          brnsville_minutes_instruction,
          $postType,
          'advanced',
          'high'
      );
    }
  }

	function brnsville_minutes_instruction() {
	  echo 'Set Date of Meeting in "Published on" field to the right and Title will be generated automatically.';
	}
  add_action( 'add_meta_boxes', 'brnsville_register_meta_boxes' );

  // Move all "advanced" metaboxes above the default editor
  add_action('edit_form_after_title', function() {
      global $post, $wp_meta_boxes;
      do_meta_boxes(get_current_screen(), 'advanced', $post);
      unset($wp_meta_boxes[get_post_type($post)]['advanced']);
  });

	/**
	 * Register a custom post type for floor meeting minutes.
	 * See http://codex.wordpress.org/Function_Reference/register_post_type
	 * for more information on how this works.
	 */
	$floor_minute_labels = array(
		'name'				 => 'Floor Minutes',
		'singular_name'		 => 'Floor Minutes',
		'add_new_item'		 => 'Add New Floor Minutes',
		'edit_item'			 => 'Edit Floor Minutes',
		'new_item'			 => 'New Floor Minutes',
		'view_item'			 => 'View Floor Minutes',
		'search_items'		 => 'Search Floor Minutes',
		'not_found'			 => 'No floor minutes found.',
		'not_found_in_trash' => 'No floor minutes found in Trash.',
	);

	$floor_minute_args = array(
		'labels'		=> $floor_minute_labels,
		'description'	=> 'Minutes from HBC floor meetings.',
		'public'		=> true,
		'menu_position'	=> 5, // Appears below Posts in the admin sidebar
		'has_archive'	=> true,
	);

	register_post_type( 'floor_minutes', $floor_minute_args );
}
add_action( 'init', 'register_minutes_post_type' );



/**
 * Generates the post title for meeting minutes posts.
 *
 * The generated post title is in the format "Board/Floor Minutes for {date}".
 *
 * This function is called just before post data is saved because the
 * time of publication is required for generating the meeting minute titles.
 *
 * @param $post_id The id of the post which was just saved.
 */
function generate_minutes_post_title( $data ) {
	// Only generate the title for board/floor minutes
	if ( ! ( 'board_minutes' == $data['post_type'] || 'floor_minutes' == $data['post_type'] ) )
		return $data;

	// Get a human readable meeting date string (Month dd{st/nd/rd/th}, YYYY)
	$date = new DateTime($data['post_date_gmt']);
	$meeting_date_string = $date->format( 'F jS, Y' );

	// Create the new title in the format "Board/Floor Minutes for {date}"
	if ( 'board_minutes' == $data['post_type'] )
		$new_title = 'Board';
	else if ( 'floor_minutes' == $data['post_type'] )
		$new_title = 'Floor';

	$new_title .= ' Minutes for ' . $meeting_date_string;

	$data['post_title'] = $new_title;
	$data['post_name'] = sanitize_title( $meeting_date_string );

	return $data;
}
add_action( 'wp_insert_post_data', 'generate_minutes_post_title' );


/**
 * Filters the HTML title on the combined minutes archive page to always display
 * as "{Semester} {Year} Meeting Minutes".
 *
 * Without this filter the archives may simply display the year as the title.
 *
 * @param string $title Title of the page.
 * @param string $sep (optional) How to separate the various items within the page title. Default is '»'.
 * @param string $seplocation (optional) Direction to display title, 'right'.
 */
function minutes_semesterly_archive_title_filter( $title, $sep = '&raquo;', $seplocation = 'right' ) {
	$title = semesterly_archive_title_filter( $title, 'floor_minutes', 'Meeting Minutes', $sep, $seplocation );
	return semesterly_archive_title_filter( $title, 'board_minutes', 'Meeting Minutes', $sep, $seplocation );
}
add_filter( 'wp_title', 'minutes_semesterly_archive_title_filter', 10, 3 );



/**
 * Allows WordPress to recognize the semesterly URL structure of the
 * combined Floor and Board Minutes post types' archive page.
 *
 * All URL rewrites are documented inside this function's contents.
 *
 * When changing these rewrite rules, the .htaccess file will need to be updated.
 * This can be done by clicking the "Save" button on the Permalinks settings page
 * in WordPress, or by calling flush_rewrite_rules(), which should only
 * be called once. Do NOT make a call to flush_rewrite_rules() on every page load!
 * It's unnecessary and impacts performance.
 *
 * @param WP_Rewrite $wp_rewrite The global WP_Rewrite instance for managing rewrite rules.
 */
function add_minutes_url_rewrite_rules( $wp_rewrite ) {
	$wp_rewrite->rules = array(

		/**
		 * Meeting minutes archive URL structure.
		 * Shows minutes for the latest semester.
		 */
		'minutes/?$' => $wp_rewrite->index . '?post_type=board_minutes',

		/**
		 * Meeting minutes archive URL structure.
		 * minutes/{string}/{year}/
		 * Example: minutes/spring/2013/
		 */
		'minutes/?([^/]*)/([0-9]{4})/?$' => $wp_rewrite->index . '?post_type=board_minutes&semester=' . $wp_rewrite->preg_index(1) . '&year=' . $wp_rewrite->preg_index(2),

	) + $wp_rewrite->rules;
}
add_filter( 'generate_rewrite_rules', 'add_minutes_url_rewrite_rules' );




/**
 * Applies the current-menu-item class to any menu item with
 * the title "Meeting Minutes" in a WordPress menu if the current page
 * is a meeting minutes archive.
 *
 * @param array $class The classes to apply to the menu item.
 * @param object $menu_item The menu item object.
 * @return array An array of classes to apply to the menu item.
 */
function add_minutes_current_menu_item_class( $classes = array(), $menu_item = false ) {
    if ( 'board_minutes' == Extras\get_post_type_outside_loop() && 'Meeting Minutes' == $menu_item->title && ! in_array( 'current-menu-item', $classes ) )
        $classes[] = 'current-menu-item';

    return $classes;
}
add_filter( 'nav_menu_css_class', 'add_minutes_current_menu_item_class', 10, 2 );


