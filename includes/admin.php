<?php
/**
 * BP-Registration-Options Admin Settings Pages
 *
 * @package BP-Registration-Options
 */


/**
 * set $bp_member_requests global
 */
add_action( 'init', 'wds_bp_registration_options_member_requests');
function wds_bp_registration_options_member_requests(){
	if( is_admin() ) {
		global $wpdb, $bp, $wds_bp_member_requests;
		$rs = $wpdb->get_results( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->base_prefix . 'users WHERE user_status IN (2,69)' , '') );
		$wds_bp_member_requests = count( $rs );
	}
}

/**
 * form submissions
 */
add_action( 'admin_init', 'wds_bp_registration_options_form_actions');
function wds_bp_registration_options_form_actions(){
	if(is_admin()){
		global $wpdb, $bp, $wds_bp_member_requests;
		//settings save
		if ( isset( $_POST['Save'] ) ) {
			check_admin_referer('bp_reg_options_check');//nonce WP security check
			$bp_moderate = '';
			if ( isset( $_POST['bp_moderate'] ) )
				$bp_moderate = $_POST['bp_moderate'];
			update_option('bprwg_moderate', $bp_moderate);

			$privacy_network = '';
			if ( isset( $_POST['privacy_network'] ) )
				$privacy_network = $_POST['privacy_network'];
			update_option('bprwg_privacy_network', $privacy_network);

			$activate_message = sanitize_text_field( $_POST['activate_message'] );
			update_option('bprwg_activate_message', $activate_message);

			$approved_message = sanitize_text_field( $_POST['approved_message'] );
			update_option('bprwg_approved_message', $approved_message);

			$denied_message = sanitize_text_field( $_POST['denied_message'] );
			update_option('bprwg_denied_message', $denied_message);

			do_action( 'bp_registration_options_general_settings_form_save' );
		}

		if ( isset( $_POST['reset_messages'] ) ) {
			check_admin_referer('bp_reg_options_check');//nonce WP security check

			delete_option('bprwg_activate_message');
			delete_option('bprwg_approved_message');
			delete_option('bprwg_denied_message');
		}

		//request submissions
		if ( isset( $_POST['Moderate'] ) ) {
			check_admin_referer('bp_reg_options_check');
			$action = $_POST['Moderate'];
			$checked_members = $_POST['bp_member_check'];
			if ( is_array( $checked_members ) ) {
				//grab message
				if ( $action == "Deny" ) {
					$subject = 'Membership Denied'; //Don't localize. Used in message that goes out to users
					$message = get_option('bprwg_denied_message');
				} elseif ( $action == "Approve" ) {
					$subject = 'Membership Approved'; //Don't localize. Used in message that goes out to users
					$message = get_option('bprwg_approved_message');
				}
				//loop all checked members
				$count = count( $checked_members );
				for ( $i = 0; $i < $count; ++$i ) {
					$user_id = (int) $checked_members[$i];

					//Grab our userdata object while we still have a user.
					$user = get_userdata( $user_id );
					if ( $action == "Deny" || $action == "Ban") {
						if ( is_multisite() ) {
							wpmu_delete_user( $user_id );
						}
						wp_delete_user( $user_id );
					} elseif ( $action == "Approve" ) {
						$sql = 'UPDATE ' . $wpdb->base_prefix . 'users SET user_status = 0 WHERE ID = %d';
						$wpdb->query( $wpdb->prepare( $sql, $user_id ) );
						$sql = 'UPDATE ' .$wpdb->base_prefix.'bp_activity SET hide_sitewide = 0 WHERE user_id = %d';
						$wpdb->query( $wpdb->prepare( $sql, $user_id ) );
					}
					//only send out message if one exists
					if ( $subject && $message ) {
						$user_name = $user->user_login;
						$user_email = $user->user_email;
						$email = str_replace( '[username]', $user_name, $message );
						wp_mail( $user_email, $subject, $email );
					}
				}
			}
			//reset global
			$rs = $wpdb->get_results( $wpdb->prepare( 'SELECT ID FROM '.$wpdb->base_prefix.'users WHERE user_status IN (2,69)', '' ) );
			$wds_bp_member_requests = count( $rs );
		}
	}
}



/**
 * set admin message to show count of member requests.
 */
add_action('admin_notices', 'wds_bp_registration_options_admin_messages');
function wds_bp_registration_options_admin_messages(){
	global $wds_bp_member_requests;
	if ( $wds_bp_member_requests > 0 && isset( $_GET['page'] ) != 'bp_registration_options_member_requests' && current_user_can('add_users') ) {
		$s = '';
		if ( $wds_bp_member_requests != 1 ) {
			$s = 's';
		}
		echo '<div class="error"><p>' . sprintf( __( 'You have %s new member request%s that need to be approved or denied. Please %s click here%s to take action', 'bp-registration-options' ),
			'<a href="' . admin_url( '/admin.php?page=bp_registration_options_member_requests' ) . '"><strong>' . $wds_bp_member_requests,
			$s . '</strong></a>',
			'<a href="' . admin_url('/admin.php?page=bp_registration_options_member_requests') . '">',
			'</a>'
			 ) . '</p></div>';
	}
}



/**
 * Plugin Menu
 */
add_action( 'admin_menu', 'wds_bp_registration_options_plugin_menu' );
function wds_bp_registration_options_plugin_menu() {
	global $wds_bp_member_requests,$blog_id;
	if ( $blog_id == 1 ) {
	  $minimum_cap = 'manage_options';
	  add_menu_page( __( 'BP Registration', 'bp-registration-options' ), __( 'BP Registration', 'bp-registration-options' ), $minimum_cap, 'bp_registration_options', 'bp_registration_options_settings', plugins_url( 'bp-registration-options/images/webdevstudios-16x16.png' ) );

	  $count = '<span class="update-plugins count-'.$wds_bp_member_requests.'"><span class="plugin-count">'.$wds_bp_member_requests.'</span></span>';

	  add_submenu_page( __( 'bp_registration_options', 'bp-registration-options' ), __( 'Member Requests ', 'bp-registration-options') . $count, __( 'Member Requests ', 'bp-registration-options' ) . $count, $minimum_cap, 'bp_registration_options_member_requests', 'bp_registration_options_member_requests' );

	  /*add_submenu_page( 'bp_registration_options', 'Help / Support', 'Help / Support', $minimum_cap, 'bp_registration_options_help_support', 'bp_registration_options_help_support' );*/
	}
}

/**
 * Tabs on the top of each admin.php?page=
 */
function wds_bp_registration_options_tab_menu($page = ''){
	global $wds_bp_member_requests;
	?>
	<div id="icon-buddypress" class="icon32"></div>
	<h2 class="nav-tab-wrapper">
	<?php _e( 'BP Registration Options', 'bp-registration-options' ); ?>
	<a class="nav-tab<?php if ( !$page ) echo ' nav-tab-active';?>" href="admin.php?page=bp_registration_options"><?php _e( 'General Settings', 'bp-registration-options' ); ?></a>
	<a class="nav-tab<?php if ( $page == 'requests' ) echo ' nav-tab-active';?>" href="admin.php?page=bp_registration_options_member_requests"><?php _e( 'Member Requests', 'bp-registration-options' ); ?> (<?php echo $wds_bp_member_requests;?>)</a>

	<!--<a class="nav-tab<?php //if ( $page == 'help' ) echo ' nav-tab-active';?>" href="admin.php?page=bp_registration_options_help_support">Help/Support</a>-->
	</h2>
<?php }

/**
 * BP-Registration-Options main settings page output.
 */
function bp_registration_options_settings() {
	//DEFAULT VALUES
	$bp_moderate = get_option('bprwg_moderate');
	$privacy_network = get_option('bprwg_privacy_network');
	$activate_message = get_option('bprwg_activate_message');
	if ( !$activate_message ) {
		$activate_message = __( 'Your membership account is awaiting approval by the site administrator. You will not be able to fully interact with the social aspects of this website until your account is approved. Once approved or denied you will receive an email notice.', 'bp-registration-options' );
		update_option('bprwg_activate_message', $activate_message);
	}
	$approved_message = get_option('bprwg_approved_message');
	if ( !$approved_message ) {
		$approved_message = sprintf( __('Hi [username], your member account on %s has been approved! You can now login and start interacting with the rest of the community...', 'bp-registration-options' ), get_bloginfo('url') );
		update_option('bprwg_approved_message', $approved_message);
	}
	$denied_message = get_option('bprwg_denied_message');
	if ( !$denied_message ) {
		$denied_message = sprintf( __('Hi [username], we regret to inform you that your member account on %s has been denied...', 'bp-registration-options' ), get_bloginfo("url") );
		update_option('bprwg_denied_message', $denied_message);
	}
	//FORM
	?>
	<div class="wrap" >
		<?php wds_bp_registration_options_tab_menu();?>
		<form method="post">
		<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('bp_reg_options_check'); ?>
		<p><input type="checkbox" id="bp_moderate" name="bp_moderate" value="1" <?php checked( $bp_moderate, '1' ); ?>/>&nbsp;<label for="bp_moderate"><strong><?php _e( 'Moderate New Members', 'bp-registration-options' ); ?></strong> (<?php _e( 'Every new member will have to be approved by an administrator before they can interact with BuddyPress components.', 'bp-registration-options' ); ?>)</label></p>
		<p><input type="checkbox" id="privacy_network" name="privacy_network" value="1" <?php checked( $privacy_network, '1' ); ?>/> <label for="privacy_network"><?php _e( 'Only registered or approved members can view BuddyPress pages (Private Network).', 'bp-registration-options' ); ?></label></p>
		<table>
			<tr>
				<td align="right" valign="top"><?php _e( 'Activate & Profile Alert Message:', 'bp-registration-options' ); ?></td>
				<td><textarea name="activate_message" style="width:500px;height:100px;"><?php echo stripslashes($activate_message);?></textarea></td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php _e( 'Account Approved Email:', 'bp-registration-options' ); ?></td>
				<td><textarea name="approved_message" style="width:500px;height:100px;"><?php echo stripslashes($approved_message);?></textarea></td>
			</tr>
			<tr>
				<td align="right" valign="top"><?php _e( 'Account Denied Email:', 'bp-registration-options' ); ?></td>
				<td><textarea name="denied_message" style="width:500px;height:100px;"><?php echo stripslashes($denied_message);?></textarea></td>
			</tr>
			<tr>
				<td></td>
				<td align="right">
					<table width="100%">
					<tr>
						<td><?php _e( 'Short Code Key: [username]', 'bp-registration-options' ); ?></td>
						<td align="right"><input type="submit" id="reset_messages" name="reset_messages" class="button button-secondary" value="<?php esc_attr_e( 'Reset Messages', 'bp-registration-options' ); ?>" /></td>
					</tr>
					</table>
				</td>
			</tr>
		</table>
		<?php do_action('bp_registration_options_general_settings_form');?>
		<input type="submit" class="button button-primary" name="Save" value="<?php esc_attr_e( 'Save Options', 'bp-registration-options' ); ?>" />
		</form>
	</div>
	<?php bp_registration_options_admin_footer();
}



/**
 * New member requests ui.
 */
function bp_registration_options_member_requests() {
	global $wpdb, $bp, $wds_bp_member_requests;
	?>
	<div class="wrap">
		<?php wds_bp_registration_options_tab_menu('requests');

		if ( $wds_bp_member_requests > 0 ) {
			$page = ( isset( $_GET["p"] ) ) ? $_GET["p"] : 1 ;
			$total_pages = ceil($wds_bp_member_requests / 20);
			$start_from = ($page-1) * 20;
			$sql = 'select ID from ' .$wpdb->base_prefix.'users where user_status in (2,69) order by user_registered LIMIT %d, 20';
			$rs = $wpdb->get_results( $wpdb->prepare( $sql , $start_from) );?>
			<form method="post" name="bprwg">
			<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('bp_reg_options_check'); ?>

			<?php

			/*
			Developers. Please return a multidimensional array in the following format.

			add_filter( 'bpro_request_columns', 'bpro_myfilter' );
			function bpro_myfilter( $fields ) {
				return $fields = array(
					array(
						'heading' => 'Column name 1',
						'content' => 'Column content 1'
					),
					array(
						'heading' => 'Column name 2',
						'content' => 'Column content 2'
					),
					array(
						'heading' => 'Column name 3',
						'content' => 'Column content 3'
					)
				);
			}
			 */
			$extra_fields = apply_filters( 'bpro_request_columns', array() );
			if ( !empty( $extra_fields ) ) {
				$headings = wp_list_pluck( $extra_fields, 'heading' );
				$content = wp_list_pluck( $extra_fields, 'content' );
			}
			?>

			<p><?php _e( 'Please approve or deny the following new members:', 'bp-registration-options' ); ?></p>

			<table class="widefat">
			<thead>
				<tr>
					<th id="cb" class="manage-column column-cb check-column" scope="col">
						<input type="checkbox" id="bp_checkall_top" name="checkall" />
					</th>
					<th><?php _e( 'Photo', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Name', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Email', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Created', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Additional Data', 'bp-registration-options' ); ?></th>
					<?php
					if ( !empty( $headings ) ) {
						foreach( $headings as $heading ) {
							echo '<th>' . $heading . '</th>';
						}
					}
					?>
				</tr>
			</thead>
			<?php $odd = true;

			foreach( $rs as $r ) {
				$user_id = $r->ID;
				$author = new BP_Core_User( $user_id );
				$userpic = $author->avatar_mini;
				$userlink = $author->user_url;
				$username = $author->fullname;
				$user = get_userdata( $user_id );
				$useremail = $user->user_email;
				$userregistered = $user->user_registered;
				$userip = get_user_meta( $user_id, 'bprwg_ip_address', true);
				if ( $odd ) {
					echo '<tr class="alternate">';
					$odd = false;
				} else {
					echo '<tr>';
					$odd = true;
				}
				?>
					<th class="check-column" scope="row"><input type="checkbox" class="bpro_checkbox" id="bp_member_check_<?php echo $user_id; ?>" name="bp_member_check[]" value="<?php echo $user_id; ?>"  /></th>
					<td><a target="_blank" href="<?php echo $userlink; ?>"><?php echo $userpic?></a></td>
					<td><strong><a target="_blank" href="<?php echo $userlink; ?>"><?php echo $username?></a></strong></td>
					<td><a href="mailto:<?php echo $useremail;?>"><?php echo $useremail;?></a></td>
					<td><?php echo $userregistered;?></td>
					<td>
						<div class="alignleft">
						<?php echo '<img height="50" src="http://api.hostip.info/flag.php?ip=' . $userip . '" / >' ?>
						</div>
						<div class="alignright">
							<?php
							$response = wp_remote_get( 'http://api.hostip.info/get_html.php?ip=' . $userip );
							if ( !is_wp_error( $response ) ) {
								$data = $response['body'];
								$data = str_replace('City:', '<br>' . __( 'City:', 'bp-registration-options' ), $data);
								$data = str_replace('IP:', '<br>' . __( 'IP:', 'bp-registration-options' ), $data);
								echo $data;
							} else {
								echo $userip;
							}
							?>
						</div>
					</td>
					<?php
					if ( !empty( $content ) ) {
						foreach( $content as $td ) {
							echo '<td>' . $td . '</td>';
						}
					}
					?>
				</tr>
			<?php } ?>
			<tfoot>
				<tr>
					<th class="manage-column column-cb check-column" scope="col"><input type="checkbox" id="bp_checkall_bottom" name="checkall" /></th>
					<th><?php _e( 'Photo', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Name', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Email', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Created', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Additional Data', 'bp-registration-options' ); ?></th>
					<?php
					if ( !empty( $headings ) ) {
						foreach( $headings as $heading ) {
							echo '<th>' . $heading . '</th>';
						}
					}
					?>
				</tr>
			</tfoot>
			</table>

			<p><input type="submit" class="button button-primary" name="Moderate" value="<?php esc_attr_e( 'Approve', 'bp-registration-options' ); ?>" />
			&nbsp;
			<input type="submit" class="button button-secondary" name="Moderate" value="<?php esc_attr_e( 'Deny', 'bp-registration-options' ); ?>" id="bpro_deny" />
			&nbsp;
			<input type="submit" class="button button-secondary" name="Moderate" value="<?php esc_attr_e( 'Ban', 'bp-registration-options' ); ?>" id="bpro_ban" disabled /></p>

			<?php //Don't translate since it's only temporary ?>
			<p>Coming soon: If you Ban a member they will not receive an email and will not be able to try to join again.</p>

			<?php if ( $total_pages > 1 ) {
				echo '<h3>';
				for ( $i=1; $i<=$total_pages; $i++ ) {
					echo "<a href='" . add_query_arg( 'p', $i ) . "'>" . $i . "</a> ";
				}
				echo '</h3>';
			}

			do_action( 'bp_registration_options_member_request_form' ); ?>

			</form>
		<?php } else {
			echo '<p><strong>' . __( 'No new members to approve.', 'bp-registration-options' ) . '</strong></p>';
		} ?>
	</div>
	<?php bp_registration_options_admin_footer();
}

function bp_registration_options_help_support(){ ?>
	<div class="wrap">
		<?php wds_bp_registration_options_tab_menu('help');?>
	</div>
	<?php bp_registration_options_admin_footer();
}

/**
 * Display our footer content
 * @return string html for the footer output
 */
function bp_registration_options_admin_footer() { ?>
	<p style="margin-top: 50px;"><?php _e( 'BuddyPress Registration Options plugin created by', 'bp-registration-options' ); ?> <a target="_blank" href="http://webdevstudios.com">WebDevStudios.com</a></p>
		<table>
			<tr>
				<td>
					<table>
						<tr>
							<td><a target="_blank" href="http://webdevstudios.com"><img width="50" src="<?php echo plugins_url( 'bp-registration-options/images/WDS-150x150.png' );?>" /></a></td>
							<td><strong><?php _e( 'Follow', 'bp-registration-options' ); ?> WebDevStudios!</strong><br />
							<a target="_blank" href="https://plus.google.com/108871619014334838112"><img src="<?php echo plugins_url( 'bp-registration-options/images/google-icon.png' );?>" /></a>
							<a target="_blank" href="http://twitter.com/webdevstudios"><img src="<?php echo plugins_url( 'bp-registration-options/images/twitter-icon.png' );?>" /></a>
							<a target="_blank" href="http://facebook.com/webdevstudios"><img src="<?php echo plugins_url( 'bp-registration-options/images/facebook-icon.png' );?>" /></a>
							<td>
						</tr>
					</table>
				</td>
				<td>
					<table>
						<tr>
							<td><a target="_blank" href="http://webdevstudios.com/team/brian-messenlehner/"><img src="https://lh3.googleusercontent.com/-eCNkGgNdWx8/AAAAAAAAAAI/AAAAAAAAAGQ/kjKbI1XZv3Y/photo.jpg?sz=50" /></a></td>
							<td><strong><?php _e( 'Follow', 'bp-registration-options' ); ?> Brian Messenlehner!</strong><br />
							<a target="_blank" href="https://plus.google.com/117578069784985312197"><img src="<?php echo plugins_url( 'bp-registration-options/images/google-icon.png' );?>" /></a>
							<a target="_blank" href="http://twitter.com/bmess"><img src="<?php echo plugins_url( 'bp-registration-options/images/twitter-icon.png' );?>" /></a>
							<a target="_blank" href="http://facebook.com/bmess"><img src="<?php echo plugins_url( 'bp-registration-options/images/facebook-icon.png' );?>" /></a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
<?php
}

add_action( 'admin_footer', 'bp_registration_options_js' );
function bp_registration_options_js() { ?>
	<script language="javascript">
		(function($) {
			//Handle our checkboxes
			var checkboxes = $('.bpro_checkbox');
			$('#bp_checkall_top,#bp_checkall_bottom').on('click',function(){
				if ( $(this).attr('checked')) {
					$(checkboxes).each(function(){
						if ( $(this).prop('checked',false) ){
							$(this).prop('checked',true);
						}
					});
				} else {
					$(checkboxes).each(function(){
						$(this).prop('checked',false);
					});
				}
			});
			//Confirm/cancel on deny/ban.
			$('#bpro_deny').on('click',function(){
				return confirm("<?php _e( 'Are you sure you want to deny and delete the checked member(s)?', 'bp-registration-options' ); ?>");
			});
			$('#bpro_ban').on('click',function(){
				return confirm("<?php _e( 'Are you sure you want to ban and delete the checked member(s)?', 'bp-registration-options' ); ?>");
			});
			$('#reset_messages').on('click',function(){
				return confirm("<?php _e( 'Are you sure you want to reset to the default messages?', 'bp-registration-options' ); ?>");
			});
		})(jQuery);
	</script>
<?php
}
