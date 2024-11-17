<?php
/**
 * Plugin Name: LearnDash Mark as Complete
 * Plugin URI: 
 * Description: Adds a custom metabox for managing course completion by users in LearnDash.
 * Version: 1.0.0
 * Author: Jeff Balicki
 * Author URI: http://jeffbalicki.com
 * License: GPL2
 * Text Domain: learndash-mark-as-complete
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (is_plugin_active('sfwd-lms/sfwd_lms.php')) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    include_once WP_PLUGIN_DIR . '/sfwd-lms/includes/settings/class-ld-settings-metaboxes.php';
	include_once WP_PLUGIN_DIR . '/sfwd-lms/includes/class-ldlms-post-types.php';
    include 'inc/LearnDash_Settings_Metabox_Course_Users_Management.php';
} else {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>This plugin requires LearnDash LMS to be installed and active.</p></div>';
    });
}


add_action('plugins_loaded', 'learndash_mark_as_complete_init', 20); 

function learndash_mark_as_complete_init() {
	add_action('init', 'users_status_init');
    add_action( 'wp_ajax_mark_as_complete', 'mark_as_complete_callback' );
}


function course_admin_script() {
    global $post_type;
    if( 'sfwd-courses' == $post_type ) {
        wp_enqueue_style('course-dataTables-styles', '//cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css' );
        wp_enqueue_script('course-dataTables-script', '//cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js' );
        wp_enqueue_script('course-admin-script', plugin_dir_url( __FILE__ ) . '/src/course-admin.js' );
        wp_enqueue_style('course-admin-styles', plugin_dir_url( __FILE__ ) . '/src/course-admin.css' );
    }
}

add_action('admin_enqueue_scripts', 'course_admin_script');


add_filter(
    'learndash_header_data',
    function( $header_data, $menu_tab_key, $admin_tab_sets ) {
		
		global $pagenow;
		if (( $pagenow == 'post.php' ) && (get_post_type() == 'sfwd-courses')) {
			$header_data['tabs'] = array_merge(
				$header_data['tabs'],
				array(
					array(
						'id'                  => 'course-complete-settings',
						'name'                => esc_html__( 'Mark as Completed', 'learndash' ),
						'metaboxes'           => [  'learndash-course-users-management' ],
						'showDocumentSidebar' => 'false',
					),
				)
			);
		}
        return $header_data;
    },
    30,
    3
);


function mark_as_complete_callback() {
	check_ajax_referer( 'mark_as_complete_nonce', 'nonce' );

    $selected_user_id = intval( $_POST['user_id'] );
    $selected_course_id = intval( $_POST['course_id'] );
    learndash_user_course_complete_all_steps( $selected_user_id, $selected_course_id );
    wp_send_json( array( 'success' => true ) );
}


//Update user online status
function users_status_init(){
    $logged_in_users = get_transient('users_status');
    if ( ! $logged_in_users ) {
        $logged_in_users = array();
    }
    $user = wp_get_current_user();

    if ( !isset($logged_in_users[$user->ID]['last']) || $logged_in_users[$user->ID]['last'] <= time()-900 ){
        $logged_in_users[$user->ID] = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'last' => time(),
        );
        set_transient('users_status', $logged_in_users, 0); 
    }
}

//Check if a user has been online in the last 15 minutes
function is_user_online($id){	
	$logged_in_users = get_transient('users_status'); //Get the active users from the transient.
	return isset($logged_in_users[$id]['last']) && $logged_in_users[$id]['last'] > time()-900; //Return boolean if the user has been online in the last 900 seconds (15 minutes).
}

//Check when a user was last online.
function user_last_online($id){
	$logged_in_users = get_transient('users_status'); //Get the active users from the transient.
	//Determine if the user has ever been logged in (and return their last active date if so).
	if ( isset($logged_in_users[$id]['last']) ){
		$date = new DateTime('@'.$logged_in_users[$id]['last']);
		$date->setTimezone(new DateTimeZone('America/Toronto'));
		return 	$date->format('Y-m-d H:i:s');
	} else {
		return false;
	}
}

