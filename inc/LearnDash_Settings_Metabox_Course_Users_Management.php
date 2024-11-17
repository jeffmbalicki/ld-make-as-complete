<?php


	class LearnDash_Settings_Metabox_Course_Users_Management extends LearnDash_Settings_Metabox {

		public function __construct() {
			$this->settings_screen_id = 'sfwd-courses';
			$this->settings_metabox_key = 'learndash-course-users-management';
			$this->settings_section_label = sprintf(
				esc_html_x( '%s Complete Management', 'placeholder: management', 'learndash' ),
				learndash_get_custom_label( 'course' )
			);
	
			parent::__construct();
		}

		
		protected function show_settings_metabox_fields( $metabox = null ) {
			if ( ( is_object( $metabox ) ) && ( is_a( $metabox, 'LearnDash_Settings_Metabox' ) ) && ( $metabox->settings_metabox_key === $this->settings_metabox_key ) && ( $metabox->settings_screen_id === $this->settings_screen_id ) ) {
				if ( ( isset( $metabox->post ) ) && ( is_a( $metabox->post, 'WP_Post' ) ) ) {
					$course_id = $metabox->post->ID;
				} else {
					$course_id = get_the_ID();
				}
		
				if ( ( ! empty( $course_id ) ) && ( get_post_type( $course_id ) === learndash_get_post_type_slug( 'course' ) ) ) {
					$users = learndash_get_course_users_access_from_meta( $course_id );
					echo '<table style="table-layout:fixed;" id="markcomplete" width="100%"> <thead><tr><th>Enrolment Date</th><th>User Account</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Last Online</th><th>Mark Complete</th></tr></thead>';
					foreach($users as $user_id){
						$user_info = get_userdata($user_id);
						$course_status = learndash_course_status( $course_id ,$user_id);
						if( $course_status != "Completed" ){
							$course_enrolled_since = ld_course_access_from( $course_id, $user_id );
							$course_enrolled_since = learndash_adjust_date_time_display( $course_enrolled_since, 'Y-m-d H:i:s' );
							$online = "";
							if(is_user_online( $user_id )){
								$online = "Online";
							}else{
								$online = user_last_online($user_id);
							}
		
							echo  '<tr><td>'.  $course_enrolled_since .'</td><td> <a href="'.get_edit_user_link( $user_id ).'">'.$user_info->user_login.'</a> </td><td><a href="mailto:'.$user_info->user_email .'">'.$user_info->user_email .'</a></td><td>'.$user_info->first_name.'</td><td>'.$user_info->last_name.'</td><td>'. $online .'</td><td ><button class="mark_as_complete components-button  is-primary" data-user-id="'.$user_id.'" data-course-id="'.$course_id.'" >Mark Complete</button></td></tr>';
						}
					}
					echo "</table>";
				}
			}
		}

		
		public function save_post_meta_box( $post_id = 0, $saved_post = null, $update = null, $settings_field_updates = null ) {
			if ( ( isset( $_POST['learndash_course_users_nonce'] ) ) && ( wp_verify_nonce( $_POST['learndash_course_users_nonce'], 'learndash_course_users_nonce_' . $post_id ) ) ) { 
				if ( ( isset( $_POST['learndash_course_users'] ) ) && ( isset( $_POST['learndash_course_users'][ $post_id ] ) ) && ( ! empty( $_POST['learndash_course_users'][ $post_id ] ) ) && isset( $_POST[ 'learndash_course_users-' . $post_id . '-changed' ] ) && ( ! empty( $_POST[ 'learndash_course_users-' . $post_id . '-changed' ] ) ) ) {
					$course_users = (array) json_decode( stripslashes( $_POST['learndash_course_users'][ $post_id ] ) ); 
					learndash_set_users_for_course( $post_id, $course_users );
				}
			}
		}

	}



	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Users_Management'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Course_Users_Management' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Course_Users_Management'] = LearnDash_Settings_Metabox_Course_Users_Management::add_metabox_instance();
			}
			return $metaboxes;
		},
		50,
		1
	);
