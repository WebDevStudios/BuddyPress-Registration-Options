<?php
/**
 * BP-Registration-Options Core Initialization
 *
 * @package BP-Registration-Options
 */
 
 
 
/**
 * set $bp_moderate & $bprwg_privacy_network globals and filter off bp buttons
 *
 *
 */
add_action( 'init', 'wds_bp_registration_options_core_init' );
function wds_bp_registration_options_core_init(){
	global $bp_moderate, $bprwg_privacy_network, $wpdb, $bp, $user_ID, $blog_id;
	if ( !is_admin() ) {
		if ( is_multisite() ) { 
			$blogid = $blog_id;
			switch_to_blog(1);
		}
		$bprwg_privacy_network = get_option('bprwg_privacy_network');
		$bp_moderate = get_option('bprwg_moderate');
		if ( is_multisite() ) { 
			switch_to_blog($blogid);
		}
		//non approved members and non logged in members can not view any buddypress pages
		if ( $bprwg_privacy_network == 1 ) {
			//redirect non logged in users to registration page, if register page is not set then kill it
			if ( $bp->current_component && $user_ID == 0 && $bp->current_component != 'register' && $bp->current_component != 'activate' ) {
				if ( $bp->pages->register->slug ) {
					wp_redirect( site_url().'/'.$bp->pages->register->slug );
					exit();
				} else {
					exit();
				}
			//if logged in and not approved then redirect to their profile page
			} elseif ( $bp->current_component && $user_ID > 0 && ( $bp->displayed_user->userdata == '' || $bp->displayed_user->userdata != '' && $bp->displayed_user->id != $user_ID ) ) {
				$user = get_userdata($user_ID);
				if ( $user->user_status == 69 ) {
					wp_redirect( $bp->loggedin_user->domain );
					exit;
				}
			}
		}
		//non approved members can still view bp pages	
		if ( $bp_moderate == 1 && $user_ID > 0 ) {
			$user = get_userdata($user_ID);
			if ( $user->user_status == 69 ) {
				//hide friend buttons
				add_filter( 'bp_get_add_friend_button', '__return_false' );
				add_filter( 'bp_get_send_public_message_button', '__return_false' );
				add_filter( 'bp_get_send_message_button', '__return_false' );
				//hide group buttons
				add_filter( 'bp_user_can_create_groups', '__return_false' );
				add_filter( 'bp_get_group_join_button', '__return_false' );
				//hide activity comment buttons
				add_filter( 'bp_activity_can_comment_reply', '__return_false' );
				add_filter( 'bp_activity_can_comment', '__return_false' );
				add_filter( 'bp_acomment_name', '__return_false' );
				//redirect messages page back to profile (dont want blocked members contacting other members) 
				add_filter( 'bp_get_options_nav_invite', '__return_false' );
				add_filter( 'bp_get_options_nav_compose', '__return_false' );
				if ( $bp->current_component == 'messages' ) {
					wp_redirect( $bp->loggedin_user->domain );
					exit();
				}
			//set global to false
			} else {
				$bp_moderate = false;
			}
		}
	}
}

/**
 * Hide any bp buttons & form via css because of no filters
 *
 *
 */
add_action( 'wp_head', 'wds_bp_registration_options_wp_head' );
function wds_bp_registration_options_wp_head(){
	global $bp_moderate;
	if ( $bp_moderate ) {
		?>
        <style>
			#whats-new-form,#new-topic-button,#post-topic-reply,#new-topic-post {display:none !important;}
			.activity-meta,.acomment-options,.group-button {display:none !important;}        
        </style>
        <?php
	}
}


/**
 * Disables activity post form
 *
 *
 */
add_action('bp_before_activity_post_form','wds_bp_before_activity_post_form', 0);
function wds_bp_before_activity_post_form(){
	global $bp_moderate;
	if ( $bp_moderate ) ob_start();
}
add_action('bp_after_activity_post_form','wds_bp_after_activity_post_form', 0);
function wds_bp_after_activity_post_form(){
	global $bp_moderate;
	if ( $bp_moderate ) ob_end_clean();
}

/**
 * Disables activity comment buttons/forms
 *
 *
 */
add_action('bp_activity_entry_content','wds_bp_activity_entry_content', 0);
function wds_bp_activity_entry_content(){
	global $bp_moderate;
	if ( $bp_moderate ) ob_start();
}
add_action('bp_before_activity_entry_comments','wds_bp_before_activity_entry_comments', 0);
function wds_bp_before_activity_entry_comments(){
	global $bp_moderate;
	if ( $bp_moderate ) { 
		ob_end_clean();
		echo '</div>';//needs this div from betweek the two hooks
	}
}

/**
 * Disables forums new topic form (groups page)
 *
 *
 */
add_action('bp_before_group_forum_post_new','wds_bp_before_group_forum_post_new', 0);
function wds_bp_before_group_forum_post_new(){
	global $bp_moderate;
	if ( $bp_moderate ) ob_start();
}
add_action('bp_after_group_forum_post_new','wds_bp_after_group_forum_post_new', 0);
function wds_bp_after_group_forum_post_new(){
	global $bp_moderate;
	if ( $bp_moderate ) ob_end_clean();
}

/**
 * Disables forums reply form
 *
 *
 */
add_action('groups_forum_new_reply_before','wds_groups_forum_new_reply_before', 0);
function wds_groups_forum_new_reply_before(){
	global $bp_moderate;
	if ( $bp_moderate ) ob_start();
}
add_action('groups_forum_new_reply_after','wds_groups_forum_new_reply_after', 0);
function wds_groups_forum_new_reply_after(){
	global $bp_moderate;
	if ( $bp_moderate ) ob_end_clean();
}

/**
 * Disables forums new topic form (forums page)
 *
 *
 */
add_action('groups_forum_new_topic_before','wds_groups_forum_new_topic_before', 0);
function wds_groups_forum_new_topic_before(){
	global $bp_moderate;
	if ( $bp_moderate ) ob_start();
}
add_action('groups_forum_new_topic_after','wds_groups_forum_new_topic_after', 0);
function wds_groups_forum_new_topic_after(){
	global $bp_moderate;
	if ( $bp_moderate ) ob_end_clean();
}


/**
 * Hide any activity created by blocked user (if they somehow get around hidden form)
 *
 *
 */
add_action( 'bp_actions', 'wds_bp_registration_options_bp_actions', 50 );
function wds_bp_registration_options_bp_actions(){
	global $wpdb, $user_ID, $bp_moderate, $bp;
	if ( $bp_moderate ) {
		$sql = 'update '.$wpdb->base_prefix.'bp_activity set hide_sitewide=1 where user_id=%d';
		$wpdb->query( $wpdb->prepare( $sql, $user_ID) );
	}
}


/**
 * Show a custom message on the activation page and on users profile header. 
 *
 * 
 *	
 */
add_filter('bp_after_activate_content', 'wds_bp_registration_options_bp_after_activate_content');
add_filter('bp_before_member_header', 'wds_bp_registration_options_bp_before_member_header');
function wds_bp_registration_options_bp_after_activate_content(){
	global $bp_moderate, $user_ID, $blog_id;
	if ( is_multisite() ) { 
		$blogid = $blog_id;
		switch_to_blog(1);
	}
	if ( $bp_moderate && isset( $_GET['key'] ) || $bp_moderate && $user_ID > 0 ) {
		$activate_message = stripslashes(get_option('bprwg_activate_message'));
		echo '<div id="message" class="error"><p>'.$activate_message.'</p></div>';
	}
	if ( is_multisite() ) { 
		switch_to_blog($blogid);
	}
}


/**
 * Custom activation functionality
 *
 * 
 *	
 */
add_action( 'bp_core_activate_account', 'wds_bp_registration_options_bp_core_activate_account');
function wds_bp_registration_options_bp_core_activate_account($user_id){
	global $wpdb, $bp_moderate;
	if ( $bp_moderate &&  $user_id > 0) {
		if ( isset( $_GET['key'] ) ) {
			//Hide user created by new user on activation.
			$sql = 'update '.$wpdb->base_prefix.'users set user_status=69 where ID=%d';
			$wpdb->query( $wpdb->prepare( $sql, $user_id) );
			//Hide activity created by new user
			$sql = 'update '.$wpdb->base_prefix.'bp_activity set hide_sitewide=1 where user_id=%d';
			$wpdb->query( $wpdb->prepare ($sql, $user_id) );
			//save user ip address
			update_user_meta($user_id, 'bprwg_ip_address', $_SERVER['REMOTE_ADDR']);
			//email admin about new member request
			$user = get_userdata( $user_id );
			$user_name = $user->user_login;
			$user_email = $user->user_email;
			$mod_email = $user_name." (".$user_email.") would like to become a member of your website, to accept or reject their request please go to ".admin_url('/admin.php?page=bp_registration_options_member_requests')." \n\n";
			$admin_email = get_bloginfo( 'admin_email' );
			wp_mail( $admin_email, 'New Member Request', $mod_email );
		}
	}
}
function wds_bp_registration_options_bp_before_member_header(){
}
