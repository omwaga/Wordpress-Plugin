<?php

/**
* Plugin Name: IRES PLUGIN
* Plugin URI: http://indepthresearch.org/
* Description: Ires plugin description of what the plugin does here.
* Version: 1.0.0
* Author: IRES
* Author URI: http://indepthresearch.org/
* License: GPL2
*/

define( 'ROOT', plugins_url( '', __FILE__ ) );
define( 'IMAGES', ROOT . '/img/' );
define( 'STYLES', ROOT . '/css/' );
define( 'SCRIPTS', ROOT . '/js/' );

function uep_custom_post_type() {
    $labels = array(
        'name'                  =>   __( 'Courses', 'uep' ),
        'singular_name'         =>   __( 'Course', 'uep' ),
        'menu_name'             => __( 'IRES PLUGIN', 'text_domain' ),
		'name_admin_bar'        => __( 'IRES PLUGIN', 'text_domain' ),
        'add_new_item'          =>   __( 'Add New Course', 'uep' ),
        'all_items'             =>   __( 'All Courses', 'uep' ),
        'edit_item'             =>   __( 'Edit Course', 'uep' ),
        'new_item'              =>   __( 'New Course', 'uep' ),
        'view_item'             =>   __( 'View Course', 'uep' ),
        'not_found'             =>   __( 'No Courses Found', 'uep' ),
        'not_found_in_trash'    =>   __( 'No Courses Found in Trash', 'uep' )
    );
 
    $supports = array(
        'title',
        'editor',
        'excerpt'
    );
 
    $args = array(
        'label'         =>   __( 'Courses', 'uep' ),
        'labels'        =>   $labels,
        'description'   =>   __( 'A list of upcoming courses', 'uep' ),
        'public'        =>   true,
        'show_in_menu'  =>   true,
        'menu_icon'     =>   IMAGES . 'course.png',
        'has_archive'   =>   true,
        'rewrite'       =>   true,
        'supports'      =>   $supports
    );
 
    register_post_type( 'course', $args );
}

function uep_add_course_info_metabox() {
    add_meta_box(
        'uep-course-info-metabox',
        __( 'Course Schedule', 'uep' ),
        'uep_render_course_info_metabox',
        'course',
        'side',
        'core'
    );
}
add_action( 'add_meta_boxes', 'uep_add_course_info_metabox' );

add_action( 'init', 'uep_custom_post_type' );

function uep_render_course_info_metabox( $post ) {

    // generate a nonce field
    wp_nonce_field( basename( __FILE__ ), 'uep-course-info-nonce' );
 
    // get previously saved meta values (if any)
    $course_start_date = get_post_meta( $post->ID, 'course-start-date', true );
    $course_end_date = get_post_meta( $post->ID, 'course-end-date', true );
    $course_venue = get_post_meta( $post->ID, 'course-venue', true );
 
    // if there is previously saved value then retrieve it, else set it to the current time
    $course_start_date = ! empty( $course_start_date ) ? $course_start_date : time();
 
    //we assume that if the end date is not present, course ends on the same day
    $course_end_date = ! empty( $course_end_date ) ? $course_end_date : $course_start_date;
     ?>
 
<label for="uep-course-start-date"><?php _e( 'Course Start Date:', 'uep' ); ?></label>
        <input class="widefat uep-course-date-input" id="uep-course-start-date" type="text" name="uep-course-start-date" placeholder="Format: February 18, 2020" value="<?php echo date( 'F d, Y', $course_start_date ); ?>" />
 
<label for="uep-course-end-date"><?php _e( 'Course End Date:', 'uep' ); ?></label>
        <input class="widefat uep-course-date-input" id="uep-course-end-date" type="text" name="uep-course-end-date" placeholder="Format: February 18, 2020" value="<?php echo date( 'F d, Y', $course_end_date ); ?>" />

        <label for="uep-course-venue"><?php _e( 'Course Location:', 'uep' ); ?></label>
        <input class="widefat" id="uep-course-venue" type="text" name="uep-course-venue" placeholder="eg. Nairobi" value="<?php echo $course_venue; ?>" />
 
    <?php } ?>
 
}

<?php
function uep_admin_script_style( $hook ) {
    global $post_type;
 
    if ( ( 'post.php' == $hook || 'post-new.php' == $hook ) && ( 'course' == $post_type ) ) {
        wp_enqueue_script(
            'upcoming-courses',
            SCRIPTS . 'script.js',
            array( 'jquery', 'jquery-ui-datepicker' ),
            '1.0',
            true
        );
 
        wp_enqueue_style(
            'jquery-ui-calendar',
            STYLES . 'jquery-ui-1.10.4.custom.min.css',
            false,
            '1.10.4',
            'all'
        );
    }
}
add_action( 'admin_enqueue_scripts', 'uep_admin_script_style' );

function uep_save_course_info( $post_id ) {
 
    // checking if the post being saved is an 'course',
    // if not, then return
    if ( 'course' != $_POST['post_type'] ) {
        return;
    }
 
    // checking for the 'save' status
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST['uep-course-info-nonce'] ) && ( wp_verify_nonce( $_POST['uep-course-info-nonce'], basename( __FILE__ ) ) ) ) ? true : false;
 
    // exit depending on the save status or if the nonce is not valid
    if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
        return;
    }
 
    // checking for the values and performing necessary actions
    if ( isset( $_POST['uep-course-start-date'] ) ) {
        update_post_meta( $post_id, 'course-start-date', strtotime( $_POST['uep-course-start-date'] ) );
    }
 
    if ( isset( $_POST['uep-course-end-date'] ) ) {
        update_post_meta( $post_id, 'course-end-date', strtotime( $_POST['uep-course-end-date'] ) );
    }
 
    if ( isset( $_POST['uep-course-venue'] ) ) {
        update_post_meta( $post_id, 'course-venue', sanitize_text_field( $_POST['uep-course-venue'] ) );
    }
}
add_action( 'save_post', 'uep_save_course_info' );

function uep_custom_columns_head( $defaults ) {
    unset( $defaults['date'] );
 
    $defaults['course_start_date'] = __( 'Start Date', 'uep' );
    $defaults['course_end_date'] = __( 'End Date', 'uep' );
    $defaults['course_venue'] = __( 'Venue', 'uep' );
 
    return $defaults;
}
add_filter( 'manage_edit-course_columns', 'uep_custom_columns_head', 10 );

function uep_custom_columns_content( $column_name, $post_id ) {
 
    if ( 'course_start_date' == $column_name ) {
        $start_date = get_post_meta( $post_id, 'course-start-date', true );
        echo date( 'F d, Y', $start_date );
    }
 
    if ( 'course_end_date' == $column_name ) {
        $end_date = get_post_meta( $post_id, 'course-end-date', true );
        echo date( 'F d, Y', $end_date );
    }
 
    if ( 'course_venue' == $column_name ) {
        $venue = get_post_meta( $post_id, 'course-venue', true );
        echo $venue;
    }
}
add_action( 'manage_course_posts_custom_column', 'uep_custom_columns_content', 10, 2 );


//Course Categories section
function wporg_register_taxonomy_category() {
     $labels = array(
         'name'              => _x( 'Categories', 'taxonomy general name' ),
         'singular_name'     => _x( 'Category', 'taxonomy singular name' ),
         'search_items'      => __( 'Search Course Categories' ),
         'all_items'         => __( 'All Course Categories' ),
         'parent_item'       => __( 'Parent Category' ),
         'parent_item_colon' => __( 'Parent Category:' ),
         'edit_item'         => __( 'Edit Course Category' ),
         'update_item'       => __( 'Update Course Category' ),
         'add_new_item'      => __( 'Add New Course Category' ),
         'new_item_name'     => __( 'New Course Category Name' ),
         'menu_name'         => __( 'Course Categories' ),
     );
     $args   = array(
         'hierarchical'      => true, // make it hierarchical (like categories)
         'labels'            => $labels,
         'show_ui'           => true,
         'show_admin_column' => true,
         'query_var'         => true,
         'rewrite'           => [ 'slug' => 'course_category' ],
     );
     register_taxonomy( 'category', [ 'course' ], $args );
}
add_action( 'init', 'wporg_register_taxonomy_category' );