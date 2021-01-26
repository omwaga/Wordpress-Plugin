<?php

/**
* Plugin Name: IRES PLUGIN
* Plugin URI: http://indepthresearch.org/
* Description: Ires plugin description of what your plugin does here.
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
        'menu_icon'     =>   IMAGES . 'event.svg',
        'has_archive'   =>   true,
        'rewrite'       =>   true,
        'supports'      =>   $supports
    );
 
    register_post_type( 'event', $args );
}

function uep_add_event_info_metabox() {
    add_meta_box(
        'uep-event-info-metabox',
        __( 'Course Schedule', 'uep' ),
        'uep_render_event_info_metabox',
        'event',
        'side',
        'core'
    );
}
add_action( 'add_meta_boxes', 'uep_add_event_info_metabox' );

add_action( 'init', 'uep_custom_post_type' );

function uep_render_event_info_metabox( $post ) {

    // generate a nonce field
    wp_nonce_field( basename( __FILE__ ), 'uep-event-info-nonce' );
 
    // get previously saved meta values (if any)
    $event_start_date = get_post_meta( $post->ID, 'event-start-date', true );
    $event_end_date = get_post_meta( $post->ID, 'event-end-date', true );
    $event_venue = get_post_meta( $post->ID, 'event-venue', true );
 
    // if there is previously saved value then retrieve it, else set it to the current time
    $event_start_date = ! empty( $event_start_date ) ? $event_start_date : time();
 
    //we assume that if the end date is not present, event ends on the same day
    $event_end_date = ! empty( $event_end_date ) ? $event_end_date : $event_start_date;
     ?>
 
<label for="uep-event-start-date"><?php _e( 'Course Start Date:', 'uep' ); ?></label>
        <input class="widefat uep-event-date-input" id="uep-event-start-date" type="text" name="uep-event-start-date" placeholder="Format: February 18, 2014" value="<?php echo date( 'F d, Y', $event_start_date ); ?>" />
 
<label for="uep-event-end-date"><?php _e( 'Course End Date:', 'uep' ); ?></label>
        <input class="widefat uep-event-date-input" id="uep-event-end-date" type="text" name="uep-event-end-date" placeholder="Format: February 18, 2014" value="<?php echo date( 'F d, Y', $event_end_date ); ?>" />

        <label for="uep-event-venue"><?php _e( 'Course Location:', 'uep' ); ?></label>
        <input class="widefat" id="uep-event-venue" type="text" name="uep-event-venue" placeholder="eg. Nairobi" value="<?php echo $event_venue; ?>" />
 
    <?php } ?>
 
}

<?php
function uep_admin_script_style( $hook ) {
    global $post_type;
 
    if ( ( 'post.php' == $hook || 'post-new.php' == $hook ) && ( 'event' == $post_type ) ) {
        wp_enqueue_script(
            'upcoming-events',
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

function uep_save_event_info( $post_id ) {
 
    // checking if the post being saved is an 'event',
    // if not, then return
    if ( 'event' != $_POST['post_type'] ) {
        return;
    }
 
    // checking for the 'save' status
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST['uep-event-info-nonce'] ) && ( wp_verify_nonce( $_POST['uep-event-info-nonce'], basename( __FILE__ ) ) ) ) ? true : false;
 
    // exit depending on the save status or if the nonce is not valid
    if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
        return;
    }
 
    // checking for the values and performing necessary actions
    if ( isset( $_POST['uep-event-start-date'] ) ) {
        update_post_meta( $post_id, 'event-start-date', strtotime( $_POST['uep-event-start-date'] ) );
    }
 
    if ( isset( $_POST['uep-event-end-date'] ) ) {
        update_post_meta( $post_id, 'event-end-date', strtotime( $_POST['uep-event-end-date'] ) );
    }
 
    if ( isset( $_POST['uep-event-venue'] ) ) {
        update_post_meta( $post_id, 'event-venue', sanitize_text_field( $_POST['uep-event-venue'] ) );
    }
}
add_action( 'save_post', 'uep_save_event_info' );

function uep_custom_columns_head( $defaults ) {
    unset( $defaults['date'] );
 
    $defaults['event_start_date'] = __( 'Start Date', 'uep' );
    $defaults['event_end_date'] = __( 'End Date', 'uep' );
    $defaults['event_venue'] = __( 'Venue', 'uep' );
 
    return $defaults;
}
add_filter( 'manage_edit-event_columns', 'uep_custom_columns_head', 10 );

function uep_custom_columns_content( $column_name, $post_id ) {
 
    if ( 'event_start_date' == $column_name ) {
        $start_date = get_post_meta( $post_id, 'event-start-date', true );
        echo date( 'F d, Y', $start_date );
    }
 
    if ( 'event_end_date' == $column_name ) {
        $end_date = get_post_meta( $post_id, 'event-end-date', true );
        echo date( 'F d, Y', $end_date );
    }
 
    if ( 'event_venue' == $column_name ) {
        $venue = get_post_meta( $post_id, 'event-venue', true );
        echo $venue;
    }
}
add_action( 'manage_event_posts_custom_column', 'uep_custom_columns_content', 10, 2 );