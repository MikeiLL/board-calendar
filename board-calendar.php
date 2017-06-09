<?php
/*
   Plugin Name: Americare Basic Custom Post Types
   Plugin URI: http://wordpress.org/extend/plugins/americare-simple-post-types/
   Version: 0.1
   Author: LexWebDev
   Description: Create and display the basic post types for total-child-americare. using parent theme Total.
   Text Domain: americare-baisc-cpt
   License: GPLv3
  */
  
require_once(__DIR__ . '/AC_Post_Type_Helpers.php');
require_once(__DIR__ . '/services.php');
require_once(__DIR__ . '/testimonials.php');
require_once(__DIR__ . '/intl_landing_pages.php');

add_filter('archive_template', array('Americare_CPT_Loader', 'get_custom_template'), 9999);
add_filter( 'wpex_template_parts', array('Americare_CPT_Loader', 'custom_template_parts' ));
add_filter( 'wpex_services_single_blocks',  array('Americare_CPT_Loader', 'cpt_single_blocks') );
add_filter( 'wpex_image_sizes', array('Americare_CPT_Loader', 'cpt_image_sizes' ), 9999);
add_action('init', array('Americare_CPT_Loader', 'wpex_filter_hooks'));
add_action('init', array('Americare_CPT_Loader', 'meta_boxes'));
add_action('save_post', array('Americare_CPT_Loader', 'meta_save'));
add_action( 'services_category_edit_form_fields', array('Americare_CPT_Loader', 'edit_tax_meta_fields'), 10, 2 );
add_action( 'services_category_add_form_fields', array('Americare_CPT_Loader', 'add_tax_meta_fields'), 10, 2 );
add_action( 'created_services_category', array('Americare_CPT_Loader', 'save_taxonomy_meta'), 10, 2 );
add_action( 'edited_services_category', array('Americare_CPT_Loader', 'save_taxonomy_meta'), 10, 2 );
add_filter( 'manage_services_posts_columns', array('Americare_CPT_Loader', 'add_featured_field_columns') );
add_filter( 'manage_services_posts_custom_column', array('Americare_CPT_Loader', 'add_featured_field_column_contents'), 10, 3 );
add_filter( 'manage_edit-services_category_columns', array('Americare_CPT_Loader', 'add_tax_field_columns') );
add_filter( 'manage_services_category_custom_column', array('Americare_CPT_Loader', 'add_tax_field_column_contents'), 10, 3 );
//add_filter( 'pre_get_posts', array('Americare_CPT_Loader', 'americare_show_cpt_archives') );
add_action('init', array('Americare_CPT_Loader', 'add_rewrite_rules'));
//add_filter( 'category_description', array('Americare_CPT_Loader', 'trim_category_desc'), 10, 2 );


// Create our Service Areas page when plugin is activated
register_activation_hook( __FILE__, array('Americare_CPT_Loader', 'create_service_areas_page') );


abstract class Americare_CPT_Loader {

    // Define our custom post types
	private static $cpts = array(
			    'services',
			    'testimonials'
			);
			
	// Retrieve all categories for Services post type
	private static $cpt_categories;
	
	/**
	 * Create our Service Areas page
	 *
	 * @since 1.0.0
	 */	
	public static function create_service_areas_page(){
        flush_rewrite_rules();
        $page = get_page_by_path( 'service-areas' );
        $content_message = "noone will ever see this in the front end. it's just a placeholder for the slug—which should not be changed.";
        // If a page with this slug already exists, overwrite it.
        if ($page) {
            $post_id = wp_insert_post( $page->ID, array( 
                                        "post_title" => "Service Areas", 
                                        "post_type" => "page", 
                                        "post_content" => $content_message,
                                        "post_status" => "publish"
                                ) );
        } else {               
        $post_id = wp_insert_post( array( 
                                        "post_title" => "Service Areas", 
                                        "post_type" => "page", 
                                        "post_content" => $content_message,
                                        "post_status" => "publish"
                                ) );
        }
    }

    /**
	 * Pull in our archive template
	 *
	 * @since 1.0.0
	 * source: http://wordpress.stackexchange.com/a/89832/48604
	 */	
	public static function get_custom_template($template) {
			global $wp_query;
			// Loop through the CPTs we want archive pages for
			// and load template when we're on page for it.
			if (is_tax('services_category')):
			    $templates[] = 'services-category.php';
                $template = SELF::locate_plugin_template($templates);
                return $template;
			endif;
			foreach (SELF::$cpts as $type):
                if (is_post_type_archive($type)) {
                        $templates[] = 'archive-'.$type.'.php';
                        $template = SELF::locate_plugin_template($templates);
                        return $template;
                } elseif (is_singular($type)) {
                        $templates[] = $type.'.php';
                        $template = SELF::locate_plugin_template($templates);
                        return $template;
                } 
            endforeach;
            // just return the template we started with
            return $template;
	}
	
	/**
	 * Call whatever wpex hooks we want to use
	 *
	 * @since 1.0.0
	 */	
	public static function wpex_filter_hooks() {
			// Loop through the CPTs we want archive pages for
			// and load template when we're on page for it.
			SELF::$cpt_categories = get_terms( 'services_category');
			foreach (SELF::$cpt_categories as $cat):
			    // The final two parameters here are the number of parameters to send to the filter function (2)
			    // and the second parameter, $tax_id.
			    $tax_id = $cat->term_taxonomy_id;
                add_filter( 'wpex_services_single_blocks', array('AC_Post_Type_Helpers', 'related_posts' ), 40, 2, $tax_id);
            endforeach;
            
            //add_filter( 'wpex_post_layout_class', array('AC_Post_Type_Helpers', 'alter_post_series_layout'), 1, 2, 'services' );
            //add_filter( 'wpex_services_entry_thumbnail_args', array('AC_Post_Type_Helpers', 'service_archive_image_size' ));
            // just return the template we started with
            return $template;
	}
	
	/**
	 * Search theme for template, then use the plugin on if not found.
	 *
	 * @since 1.0.0
	 * source: http://wordpress.stackexchange.com/a/89832/48604
	 */	
	public static function locate_plugin_template($template_names, $load = false, $require_once = true ) {
    if (!is_array($template_names)) {
        return '';
    }
    $located = '';  
    foreach ( $template_names as $template_name ) {
        if ( !$template_name )
            continue;
        if ( file_exists(STYLESHEETPATH . '/post_types/' . $template_name)) {
            $located = STYLESHEETPATH . '/post_types/' . $template_name;
            break;
        } elseif ( file_exists(TEMPLATEPATH . '/' . $template_name) ) {
            $located = TEMPLATEPATH . '/' . $template_name;
            break;
        } elseif ( file_exists(__DIR__ . '/' . $template_name) ) {
            $located = __DIR__ . '/' . $template_name;
            break;
        }
    }
    if ( $load && $located != '' ) {
        load_template( $located, $require_once );
    }
    return $located;
	}
	
	/**
	 * Easy Custom Post Type Entry Override 
	 *
	 * @since 1.0.0
	 * source: https://wpexplorer-themes.com/total/snippets/easy-custom-post-type-entry-override/
	 */	
	public static function custom_template_parts( $parts ) {
         // mz_pr($parts);
        // Override the output for your 'books' post type
        // Now you can simply create a book-entry.php file in your child theme
        // and whatever you place there will display for the entry
        if ( 'services' == get_post_type() ) {
            $parts['cpt_entry'] = 'partials/cpt/services-entry';
        }
        else if ( 'health_programs' == get_post_type() ) {
            $parts['cpt_entry'] = 'partials/cpt/health_programs-entry';
        }
        else if ( 'behavorial_programs' == get_post_type() ) {
            $parts['cpt_entry'] = 'partials/cpt/behavorial_programs-entry';
        }

        // Return parts
        return $parts;

    }
    
    

    /**
     * Any custom post type post layout can be altered via the wpex_{post_type}_single_blocks filter
     * which returns an array of the "blocks" or parts for the layout
     * that will be loaded from partials/cpt/cpt-single-{block}.php
     *
     * Since 1.0
     * Source: https://wpexplorer-themes.com/total/snippets/cpt-single-blocks/
     *
     */
    public static function cpt_single_blocks( $blocks ) {

        // Remove the featured image from this post type
        unset( $blocks['media'] );

        // Return blocks
        return $blocks;

    }


    
    
    /**
	 * Add & remove image sizes from the "Image Sizes" panel
	 *
	 * @since 1.0.0
	 * source: https://wpexplorer-themes.com/total/snippets/addremove-image-sizes/
	 */	
    public static function cpt_image_sizes( $sizes ) {

        // Remove "blog_post_full" image size
        unset( $sizes['blog_post_full'] );
        
        // Add new image size "my_image_sizes"
        $sizes['cpt_image_size'] = array(
            'label'     => __( 'Image sizes for Slider Entry displays', 'wpex' ), // Label
            'width'     => 'cpt_image_size_width', // id for theme_mod width
            'height'    => 'cpt_image_size_height', // id for theme_mod height
            'crop'      => 'cpt_image_size_crop', // id for theme_mod crop
        );
        
        // Add new image size "my_image_sizes"
        $sizes['services_archive'] = array(
            'label'     => __( 'Image sizes for Archive Entry displays', 'wpex' ), // Label
            'width'     => 'archive_image_size_width', // id for theme_mod width
            'height'    => 'archive_image_size_height', // id for theme_mod height
            'crop'      => 'archive_image_size_crop', // id for theme_mod crop
        );

        // Return sizes
        return $sizes;

    }
    
    
    
    /**
	 * Create our meta_boxes for each post type
	 *
	 * @since 1.0.0
	 * source: http://smallenvelop.com/how-to-create-featured-posts-in-wordpress/
	 */	
	public static function meta_boxes(){
        add_action( 'add_meta_boxes', function(){
                add_meta_box( 'americare_featured_post_meta', __( 'Featured Service', 'total' ), array(__CLASS__, 'featured_meta_callback'), 'services', 'side' );
        });
    }
	
	/**
	 * Generate the Metabox output for Featured Post
	 *
	 * @since 1.0.0
	 * source: http://smallenvelop.com/how-to-create-featured-posts-in-wordpress/
	 */	
    public static function featured_meta_callback( $post ) {
        $featured = get_post_meta( $post->ID );
        ?>
 
        <p>
            <div class="sm-row-content">
                <label for="featured-meta-checkbox">
                    <input type="checkbox" name="featured-meta-checkbox" id="featured-meta-checkbox" value="yes" <?php if ( isset ( $featured['featured-meta-checkbox'] ) ) checked( $featured['featured-meta-checkbox'][0], 'yes' ); ?> />
                    <?php _e( 'Include in Slider', 'total' )?>
                </label>
        
            </div>
        </p>
        <p>
            <div class="sm-row-content">
                <label for="grouped-meta-checkbox">
                    <input type="checkbox" name="grouped-meta-checkbox" id="grouped-meta-checkbox" value="yes" <?php if ( isset ( $featured['grouped-meta-checkbox'] ) ) checked( $featured['grouped-meta-checkbox'][0], 'yes' ); ?> />
                    <?php _e( 'Include on Services Page', 'total' )?>
                </label>
        
            </div>
        </p>
 
        <?php
    }
    
    /**
     * Save the custom meta input
     *
	 * @since 1.0.0
	 * source: http://smallenvelop.com/how-to-create-featured-posts-in-wordpress/
     */
    public static function meta_save( $post_id ) {
 
        // Check save status
        $is_autosave = wp_is_post_autosave( $post_id );
        $is_revision = wp_is_post_revision( $post_id );
        $is_valid_nonce = ( isset( $_POST[ 'ac_featured_nonce' ] ) && wp_verify_nonce( $_POST[ 'ac_featured_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
 
        // Exit script depending on save status
        if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
            return;
        }
 
         // Check for input and saves
        if( isset( $_POST[ 'featured-meta-checkbox' ] ) ) {
            update_post_meta( $post_id, 'featured-meta-checkbox', 'yes' );
        } else {
            update_post_meta( $post_id, 'featured-meta-checkbox', '' );
        }
        if( isset( $_POST[ 'grouped-meta-checkbox' ] ) ) {
            update_post_meta( $post_id, 'grouped-meta-checkbox', 'yes' );
        } else {
            update_post_meta( $post_id, 'grouped-meta-checkbox', '' );
        }
 
    }
    
    
    /**
     * Add Featured checkbox to add category page
     *
	 * @since 1.0.0
	 * source: https://section214.com/2016/01/adding-custom-meta-fields-to-taxonomies/
     */
    public static function add_tax_meta_fields( $taxonomy ) {
        ?>
        <div class="form-field term-group">
             <label for="featured-meta-checkbox">
                    <input type="checkbox" name="featured-meta-checkbox" id="featured-meta-checkbox" value="yes" />
                    <?php _e( 'Include in Slider', 'total' ); ?>
                </label>
        </div>
        <?php
    }
    
    /**
     * Add Featured checkbox to edit category page
     *
	 * @since 1.0.0
	 * source: https://section214.com/2016/01/adding-custom-meta-fields-to-taxonomies/
     */
    public static function edit_tax_meta_fields( $term, $taxonomy ) {
        $featured = get_term_meta( $term->term_id, 'featured-meta-checkbox', true );
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="featured-meta-checkbox"><?php _e( 'Featured', 'total' ); ?></label>
            </th>
            <td>
               <label for="featured-meta-checkbox"> 
                    <input type="checkbox" name="featured-meta-checkbox" id="featured-meta-checkbox" value="yes" <?php if ( isset ( $featured ) ) checked( $featured, 'yes' ); ?> />
                    <?php _e( 'Include in Slider', 'total' ); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    /**
     * Save "Featured" setting when services_category is created or edited
     *
	 * @since 1.0.0
	 * source: https://section214.com/2016/01/adding-custom-meta-fields-to-taxonomies/
     */
    public static function save_taxonomy_meta( $term_id, $tag_id ) {
            $is_autosave = wp_is_post_autosave( $post_id );
            $is_revision = wp_is_post_revision( $post_id );
            $is_valid_nonce = ( isset( $_POST[ 'ac_featured_nonce' ] ) && wp_verify_nonce( $_POST[ 'ac_featured_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
 
            // Exit script depending on save status
            if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
                return;
            }

             // Check for input and saves
            if( isset( $_POST[ 'featured-meta-checkbox' ] ) ) {
                update_term_meta( $term_id, 'featured-meta-checkbox', 'yes' );
                // mz_pr(get_term_meta( $term_id, 'featured-meta-checkbox', true ));
            } else {
                update_term_meta( $term_id, 'featured-meta-checkbox', '' );
            }
    }


    /**
     * Add columns to display Featured status
     *
	 * @since 1.0.0
	 * source: https://section214.com/2016/01/adding-custom-meta-fields-to-taxonomies/
     */
    public static function add_featured_field_columns( $columns ) {
        $columns['featured-meta-checkbox'] = __( 'Featured', 'total' );

        return $columns;
    }
    

    /**
     * Insert Featured status into columns
     *
	 * @since 1.0.0
	 * source: https://section214.com/2016/01/adding-custom-meta-fields-to-taxonomies/
     */
    public static function add_featured_field_column_contents( $column, $post_id ) {
    switch ( $column ) {

        case 'featured-meta-checkbox' :
            $terms = get_post_meta( $post_id , 'featured-meta-checkbox' , '' , ',' , '' );
            if ( $terms[0] == 'yes' )
                echo 'Yes';
            break;


        }
    }
    
    /**
     * Add columns to display Featured status
     *
	 * @since 1.0.0
	 * source: https://section214.com/2016/01/adding-custom-meta-fields-to-taxonomies/
     */
    public static function add_tax_field_columns( $columns ) {
        $columns['featured-meta-checkbox'] = __( 'Featured', 'total' );

        return $columns;
    }
    

    /**
     * Insert Featured status into columns
     *
	 * @since 1.0.0
	 * source: https://section214.com/2016/01/adding-custom-meta-fields-to-taxonomies/
     */
    public static function add_tax_field_column_contents( $content, $column_name, $term_id ) {
        switch( $column_name ) {
            case 'featured-meta-checkbox' :
                $content = get_term_meta( $term_id, 'featured-meta-checkbox', true );
                break;
        }

        return $content;
    }
    
    /*
     * Add services cpt to category archives array when in category archive pages
     *
	 * @since 1.0.0
	 *
	 * Not Using, But here for reference
     */
    public static function americare_show_cpt_archives( $query ) {
        if( is_category() || is_tag() && $query->is_archive() ) {
            $query->set( 'post_type', array(
             'post', 'nav_menu_item', 'services'
             ));
            return $query;
        }
    }
    
    /*
     * Load a page for Service Areas to show each service_area
     *
	 * @since 1.0.0
     */
    public static function add_rewrite_rules () {
        add_rewrite_rule( '^service_areas/?$', 'index.php?pagename=service-areas', 'top' );
    }
    
}

?>