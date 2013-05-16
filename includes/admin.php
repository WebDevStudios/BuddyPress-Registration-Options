<?php
/**
 * BP-Registration-Options Admin Settings Pages
 *
 * @package BP-Registration-Options
 */
  

/**
 * set $bp_member_requests global
 *
 * 
 *	
 */
add_action( 'init', 'wds_bp_registration_options_member_requests');
function wds_bp_registration_options_member_requests(){
	if(is_admin()){
		global $wpdb, $bp, $wds_bp_member_requests;
		$rs = $wpdb->get_results( $wpdb->prepare( 'Select ID from '.$wpdb->base_prefix.'users where user_status in (2,69)' , '') );
		$wds_bp_member_requests = count( $rs );
	}
}
  
/**
 * form submissions
 *
 * 
 *	
 */
add_action( 'admin_init', 'wds_bp_registration_options_form_actions');
function wds_bp_registration_options_form_actions(){
	if(is_admin()){
		global $wpdb, $bp, $wds_bp_member_requests;
		//settings save
		if ( isset( $_POST['Save'] ) ) {
			check_admin_referer('bp_reg_options_check');//nonce WP security check
			$bp_moderate = '';
			if (isset( $_POST['bp_moderate'] ) )
				$bp_moderate=$_POST['bp_moderate'];
			update_option('bprwg_moderate', $bp_moderate);
			$privacy_network = '';
			if (isset( $_POST['privacy_network'] ) )
				$privacy_network = $_POST['privacy_network'];
			update_option('bprwg_privacy_network', $privacy_network);
			$activate_message=$_POST['activate_message'];
			update_option('bprwg_activate_message', $activate_message);
			$approved_message=$_POST['approved_message'];
			update_option('bprwg_approved_message', $approved_message);
			$denied_message=$_POST['denied_message'];
			update_option('bprwg_denied_message', $denied_message);
			do_action('bp_registration_options_general_settings_form_save');
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
					$subject = 'Membership Denied';
					$message = get_option('bprwg_denied_message');
				} elseif ( $action == "Approve" ) {
					$subject = 'Membership Approved';
					$message = get_option('bprwg_approved_message');
				}
				//loop all checked members
				for ( $i = 0; $i < count( $checked_members ); ++$i ) {
					$user_id = (int)$checked_members[$i];
					if ( $action == "Deny" || $action == "Ban") {
						if ( is_multisite() ) {
							wpmu_delete_user( $user_id );
						}
						wp_delete_user( $user_id );	
					} elseif ( $action == "Approve" ) {
						$sql='update '.$wpdb->base_prefix.'users set user_status=0 where ID=%d';
						$wpdb->query($wpdb->prepare($sql, $user_id));
						$sql='update ' .$wpdb->base_prefix.'bp_activity set hide_sitewide=0 where user_id=%d';
						$wpdb->query($wpdb->prepare($sql, $user_id));
					}
					//only send out message if one exists
					if ( $subject && $message ) {
						$user = get_userdata($user_id);
						$user_name = $user->user_login;
						$user_email = $user->user_email;
						$email = str_replace( '[username]', $user_name, $message );
						wp_mail( $user_email, $subject, $email );
					}
				}
			}
			//reset global
			$rs = $wpdb->get_results( $wpdb->prepare( 'Select ID from '.$wpdb->base_prefix.'users where user_status in (2,69)', '' ) );
			$wds_bp_member_requests = count( $rs );
		}
	}
}



/**
 * set admin message to show count of member requests.
 *
 * 
 *	
 */
add_action('admin_notices', 'wds_bp_registration_options_admin_messages');
function wds_bp_registration_options_admin_messages(){
	global $wds_bp_member_requests;
	if ( $wds_bp_member_requests > 0 && isset( $_GET['page'] ) != 'bp_registration_options_member_requests' && current_user_can('add_users')) {
		$s = '';
		if ( $wds_bp_member_requests != 1 ) {
			$s = 's';
		}
		echo '<div class="error"><p>You have <a href="'.admin_url('/admin.php?page=bp_registration_options_member_requests').'"><strong>'.$wds_bp_member_requests.' new member request'.$s.'</strong></a> that need to be approved or denied. Please <a href="'.admin_url('/admin.php?page=bp_registration_options_member_requests').'">click here</a> to take action.</p></div>';
	}
}

 

/**
 * Plugin Menu
 *
 * 
 *	
 */
add_action( 'admin_menu', 'wds_bp_registration_options_plugin_menu' );
function wds_bp_registration_options_plugin_menu() {
	global $wds_bp_member_requests,$blog_id;
	if ( $blog_id == 1 ) {
	  $minimum_role = 'administrator';
	  add_menu_page( 'BP Registration', 'BP Registration', $minimum_role, 'bp_registration_options', 'bp_registration_options_settings', plugins_url( 'bp-registration-options/images/webdevstudios-16x16.png' ) );
	  
	  $count = '<span class="update-plugins count-'.$wds_bp_member_requests.'"><span class="plugin-count">'.$wds_bp_member_requests.'</span></span>';
	  
	  add_submenu_page( 'bp_registration_options', 'Member Requests '.$count, 'Member Requests '.$count, $minimum_role, 'bp_registration_options_member_requests', 'bp_registration_options_member_requests' );
	  
	  /*add_submenu_page( 'bp_registration_options', 'Help / Support', 'Help / Support', $minimum_role, 'bp_registration_options_help_support', 'bp_registration_options_help_support' );*/
	}
}


/**
 * Tabs on the top of each admin.php?page= 
 *
 * 
 *	
 */
function wds_bp_registration_options_tab_menu($page = ''){
	global $wds_bp_member_requests;
	?>
    <div id="icon-buddypress" class="icon32"></div>
    <h2 class="nav-tab-wrapper">
    BP Registration Options
    <a class="nav-tab<?php if ( !$page ) echo ' nav-tab-active';?>" href="admin.php?page=bp_registration_options">General Settings</a>
    <a class="nav-tab<?php if ( $page == 'requests' ) echo ' nav-tab-active';?>" href="admin.php?page=bp_registration_options_member_requests">Member Requests (<?php echo $wds_bp_member_requests;?>)</a>
    <!--<a class="nav-tab<?php if ( $page == 'help' ) echo ' nav-tab-active';?>" href="admin.php?page=bp_registration_options_help_support">Help/Support</a>-->
    </h2><br />
<?php }





/**
 * BP-Registration-Options main settings page output. 
 *
 * 
 *	
 */
function bp_registration_options_settings() {
	//DEFAULT VALUES
	$bp_moderate = get_option('bprwg_moderate');
	$privacy_network = get_option('bprwg_privacy_network');
	$activate_message = get_option('bprwg_activate_message');
	if ( !$activate_message ) {
		$activate_message = "Your membership account is awaiting approval by the site administrator. You will not be able to fully interact with the social aspects of this website until your account is approved. Once approved or denied you will receive an email notice.";
		update_option('bprwg_activate_message', $activate_message);
	}
	$approved_message = get_option('bprwg_approved_message');
	if ( !$approved_message ) {
		$approved_message = "Hi [username],\n\nYour member account on ".get_bloginfo("url")." has been approved! You can now login and start interacting with the rest of the community...";
		update_option('bprwg_approved_message', $approved_message);
	}
	$denied_message = get_option('bprwg_denied_message');
	if ( !$denied_message ) {
		$denied_message = "Hi [username],\n\nWe regret to inform you that your member account on ".get_bloginfo("url")." has been denied...";
		update_option('bprwg_denied_message', $denied_message);
	}
	//FORM
	?>
    <div class="wrap" >
		<?php wds_bp_registration_options_tab_menu();?>
        <form method="post">
        <?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('bp_reg_options_check'); ?>
        <p><input type="checkbox" id="bp_moderate" name="bp_moderate" value="1" <?php if($bp_moderate=="1"){?>checked<?php }?>/>&nbsp;<strong>Moderate New Members</strong> (Every new member will have to be approved by an administrator before they can interact with BuddyPress components.)</p>
        <p><input type="checkbox" id="privacy_network" name="privacy_network" value="1" <?php if($privacy_network=="1"){?>checked<?php }?>/> Only registered or approved members can view BuddyPress pages (Private Network).</p>
        <table>
            <tr>
           		<td align="right" valign="top">Activate & Profile Alert Message:</td>
            	<td><textarea name="activate_message" style="width:500px;height:100px;"><?php echo stripslashes($activate_message);?></textarea></td>
            </tr>
            <tr>
           		<td align="right" valign="top">Account Approved Email:</td>
            	<td><textarea name="approved_message" style="width:500px;height:100px;"><?php echo stripslashes($approved_message);?></textarea></td>
            </tr>
            <tr>
           		<td align="right" valign="top">Account Denied Email:</td>
            	<td><textarea name="denied_message" style="width:500px;height:100px;"><?php echo stripslashes($denied_message);?></textarea></td>
            </tr>
            <tr>
            	<td></td>
                <td align="right">
                	<table width="100%">
                    <tr>
                    	<td>Short Code Key: [username]</td>
                        <td align="right"><input type="submit" name="reset_messages" value="Reset Messages" onclick="return confirm('Are you sure you want to reset to the default messages?');" /></td>
                    </tr>
                    </table>
                </td>
            </tr>
		</table>
        <?php do_action('bp_registration_options_general_settings_form');?>
        <input type="submit" name="Save" value="Save Options" />
        </form>
	</div>
    <?php bp_registration_options_admin_footer();
}



/**
 * New member requests ui. 
 *
 * 
 *	
 */
function bp_registration_options_member_requests() {
	global $wpdb, $bp, $wds_bp_member_requests;
	?>
    <div class="wrap" >
		<?php wds_bp_registration_options_tab_menu('requests');
		if ( $wds_bp_member_requests > 0 ) { 
			if (isset($_GET["p"])) { $page  = $_GET["p"]; } else { $page=1; };
			$total_pages = ceil($wds_bp_member_requests / 20);
			$start_from = ($page-1) * 20;
			$sql = 'select ID from ' .$wpdb->base_prefix.'users where user_status in (2,69) order by user_registered LIMIT %d, 20';
			$rs = $wpdb->get_results( $wpdb->prepare( $sql , $start_from) );?>
            <form method="post" name="bprwg">
            <?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('bp_reg_options_check'); ?>
            Please approve or deny the following new members:
            <script LANGUAGE="JavaScript">
			function bprwg_checkall(field){
				if(document.getElementById('bp_checkall').checked == true){
					checkAll(field)
				}else{
					uncheckAll(field)
				}
			}
			function checkAll(field){
			for (i = 0; i < field.length; i++)
				field[i].checked = true ;
			}
			function uncheckAll(field){
			for (i = 0; i < field.length; i++)
				field[i].checked = false ;
			}
			</script>
            <table cellpadding="3" cellspacing="3">
            <tr>
            	<td><input type="checkbox" id="bp_checkall" onclick="bprwg_checkall(document.bprwg.bp_member_check);" name="checkall" /></td>
                <td><strong>Photo</strong></td>
                <td><strong>Name</strong></td>
            	<td><strong>Email</strong></td>
                <td><strong>Created</strong></td>
                <td><strong>Additional Data</strong></td>
            </tr>
			<?php
			$bgc = '';
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
				if ( $bgc == '' ) {
					$bgc = '#eeeeee';
				} else {
					$bgc = '';
				}?>
				<tr <?php if ( $bgc ) {?>style="background:<?php echo $bgc;?> !important;"<?php } ?>>
					<td valign="top"><input type="checkbox" id="bp_member_check" name="bp_member_check[]" value="<?php echo $user_id; ?>"  /></td>
                    <td valign="top"><a target="_blank" href="<?php echo $userlink; ?>"><?php echo $userpic?></a></td>
					<td valign="top"><strong><a target="_blank" href="<?php echo $userlink; ?>"><?php echo $username?></a></strong></td>
					<td valign="top"><a href="mailto:<?php echo $useremail;?>"><?php echo $useremail;?></a></td>
					<td valign="top"><?php echo $userregistered;?></td>
					<td valign="top">
						<table>
                        <tr>
                        <td valign="top">
						<?php echo '<img height="50" src="http://api.hostip.info/flag.php?ip=' . $userip . '" / >' ?>
                        </td>
                        <td valign="top">
							<?php
                            $response = wp_remote_get( 'http://api.hostip.info/get_html.php?ip=' . $userip );
                            if(!is_wp_error( $response ) ) {
                                 $data = $response['body'];
								 $data = str_replace("City:","<br>City:",$data);
								 $data = str_replace("IP:","<br>IP:",$data);
								 echo $data;
                            }else{
								echo $userip;
							}
                            ?>
                        </td>
                        </tr>
                        </table>
                    </td>
				</tr>
			<?php } ?>
			</table>

            <input type="submit" name="Moderate" value="Approve" />
            <input type="submit" name="Moderate" value="Deny" onclick="return confirm('Are you sure you want to deny and delete the checked member(s)?');" />
            <input type="submit" name="Moderate" value="Ban" onclick="return confirm('Are you sure you want to ban and delete the checked member(s)?');" />

            <p>*If you Ban a member they will not receive an email and will not be able to try to join again.</p>
            
            <?php if ( $total_pages > 1 ) { 
				echo '<h3>';
				for ($i=1; $i<=$total_pages; $i++) {
    				echo "<a href='".add_query_arg( 'p', $i )."'>".$i."</a> ";
				}
				echo '</h3>';
			}
			
			do_action('bp_registration_options_member_request_form');?>
            
			</form>
		<?php }else{
			echo "No new members to approve.";
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
function bp_registration_options_admin_footer(){
	  ?>
      <p>BuddyPress Registration Options plugin created by <a target="_blank" href="http://webdevstudios.com">WebDevStudios.com</a></p>
	  <table>
	  <tr>
	  <td>
		  <table>
		  <tr>
			  <td><a target="_blank" href="http://webdevstudios.com"><img width="50" src="<?php echo plugins_url( 'bp-registration-options/images/WDS-150x150.png' );?>" /></a></td>
			  <td><strong>Follow WebDevStudios!</strong><br />
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
			  <td><strong>Follow Brian Messenlehner!</strong><br />
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
