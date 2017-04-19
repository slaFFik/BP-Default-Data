<?php
/**
 * Plugin Name: BuddyPress Default Data
 * Plugin URI:  http://ovirium.com
 * Description: Plugin will create lots of users, groups, topics, activity items, profile data - useful for testing purpose.
 * Author:      slaFFik
 * Version:     1.1.2
 * Author URI:  https://ovirium.com
 * Text Domain: bp-default-data
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BPDD_VERSION', '1.1.2' );

/**
 * Make the plugin translatable.
 */
function bpdd_load_plugin_textdomain() {
	load_plugin_textdomain( 'bp-default-data', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'bpdd_load_plugin_textdomain' );

/**
 * Load the plugin admin area registration hook.
 */
function bpdd_init() {
	add_action( bp_core_admin_hook(), 'bpdd_admin_page', 99 );
}

add_action( 'bp_init', 'bpdd_init' );

/**
 * Register admin area page link and its handler.
 */
function bpdd_admin_page() {
	if ( ! is_super_admin() ) {
		return;
	}

	add_submenu_page(
		is_multisite() ? 'settings.php' : 'tools.php',
		__( 'BuddyPress Default Data', 'bp-default-data' ),
		__( 'BP Default Data', 'bp-default-data' ),
		'manage_options',
		'bpdd-setup',
		'bpdd_admin_page_content'
	);
}

/**
 * Display the admin area page.
 */
function bpdd_admin_page_content() { ?>
	<div class="wrap" id="bp-default-data-page">
		<style type="text/css">
			ul li.users {
				border-bottom: 1px solid #EEEEEE;
				margin: 0 0 10px;
				padding: 5px 0
			}

			ul li.users ul, ul li.groups ul {
				margin: 5px 0 0 20px
			}

			#message ul.results li {
				list-style: disc;
				margin-left: 25px
			}
		</style>
		<h2><?php _e( 'BuddyPress Default Data', 'bp-default-data' ); ?> <sup>v<?php echo BPDD_VERSION ?></sup></h2>

		<?php
		if ( ! empty( $_POST['bpdd-admin-clear'] ) ) {
			bpdd_clear_db();
			echo '<div id="message" class="updated fade"><p>' . __( 'Everything was deleted', 'bp-default-data' ) . '</p></div>';
		}

		if ( isset( $_POST['bpdd-admin-submit'] ) ) {
			// default values
			$users    = false;
			$imported = array();

			// Check nonce before we do anything
			check_admin_referer( 'bpdd-admin' );

			// Import users
			if ( isset( $_POST['bpdd']['import-users'] ) ) {
				$users             = bpdd_import_users();
				$imported['users'] = sprintf( __( '%s new users', 'bp-default-data' ), number_format_i18n( count( $users ) ) );

				if ( isset( $_POST['bpdd']['import-profile'] ) ) {
					$profile             = bpdd_import_users_profile();
					$imported['profile'] = sprintf( __( '%s profile entries', 'bp-default-data' ), number_format_i18n( $profile ) );
				}

				if ( isset( $_POST['bpdd']['import-friends'] ) ) {
					$friends             = bpdd_import_users_friends();
					$imported['friends'] = sprintf( __( '%s friends connections', 'bp-default-data' ), number_format_i18n( $friends ) );
				}

				if ( isset( $_POST['bpdd']['import-messages'] ) ) {
					$messages             = bpdd_import_users_messages();
					$imported['messages'] = sprintf( __( '%s private messages', 'bp-default-data' ), number_format_i18n( count( $messages ) ) );
				}

				if ( isset( $_POST['bpdd']['import-activity'] ) ) {
					$activity             = bpdd_import_users_activity();
					$imported['activity'] = sprintf( __( '%s personal activity items', 'bp-default-data' ), number_format_i18n( $activity ) );
				}
			}

			// Import groups
			if ( isset( $_POST['bpdd']['import-groups'] ) ) {
				$groups             = bpdd_import_groups( $users );
				$imported['groups'] = sprintf( __( '%s new groups', 'bp-default-data' ), number_format_i18n( count( $groups ) ) );

				if ( isset( $_POST['bpdd']['import-g-members'] ) ) {
					$g_members             = bpdd_import_groups_members( $groups );
					$imported['g_members'] = sprintf( __( '%s groups members (1 user can be in several groups)', 'bp-default-data' ), number_format_i18n( count( $g_members ) ) );
				}

				//if ( isset( $_POST['bpdd']['import-forums'] ) ) {
				//	$forums             = bpdd_import_groups_forums( $groups );
				//	$imported['forums'] = sprintf( __( '%s groups forum topics', 'bp-default-data' ), number_format_i18n( count( $forums ) ) );
				//}

				if ( isset( $_POST['bpdd']['import-g-activity'] ) ) {
					$g_activity             = bpdd_import_groups_activity();
					$imported['g_activity'] = sprintf( __( '%s groups activity items', 'bp-default-data' ), number_format_i18n( $g_activity ) );
				}
			}
			?>

			<div id="message" class="updated fade">
				<p>
					<?php
					_e( 'Data was successfully imported', 'bp-default-data' );
					if ( count( $imported ) > 0 ) {
						echo ':<ul class="results"><li>';
						echo implode( '</li><li>', $imported );
						echo '</li></ul>';
					} ?>
				</p>
			</div>

			<?php
		} ?>

		<form action="" method="post" id="bpdd-admin-form">
			<script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery('#import-profile, #import-friends, #import-activity, #import-messages').click(function () {
                        if (jQuery(this).attr('checked') === 'checked')
                            jQuery('#import-users').attr('checked', 'checked');
                    });
                    jQuery('#import-users').click(function () {
                        if (jQuery(this).attr('checked') !== 'checked')
                            jQuery('#import-profile, #import-friends, #import-activity, #import-messages').removeAttr('checked');
                    });

                    jQuery('#import-forums, #import-g-members, #import-g-activity').click(function () {
                        if (jQuery(this).attr('checked') === 'checked')
                            jQuery('#import-groups').attr('checked', 'checked');
                    });
                    jQuery('#import-groups').click(function () {
                        if (jQuery(this).attr('checked') !== 'checked')
                            jQuery('#import-forums, #import-g-members, #import-g-activity').removeAttr('checked');
                    });

                    jQuery("#bpdd-admin-clear").click(function () {
                        if (confirm('<?php echo esc_js( __( 'Are you sure you want to delete all users (except one with ID=1), groups, messages, activities, forum topics etc?', 'bp-default-data' ) ); ?>')) {
                            return true;
                        }

                        return false;
                    });
                });
			</script>

			<p><?php _e( 'Please do not import users twice as this will cause lots of errors (believe me). Just do clearing.', 'bp-default-data' ); ?></p>

			<p><?php _e( 'Please do not mess importing users and their data with groups. Importing is rather heavy process, so please finish with members first and then work with groups.', 'bp-default-data' ); ?></p>

			<ul class="items">
				<li class="users">
					<label for="import-users">
						<input type="checkbox" name="bpdd[import-users]" id="import-users" value="1"/>
						&nbsp; <?php _e( 'Do you want to import users?', 'bp-default-data' ); ?>
					</label>
					<ul>

						<?php if ( bp_is_active( 'xprofile' ) ) : ?>
							<li>
								<label for="import-profile">
									<input type="checkbox" name="bpdd[import-profile]" id="import-profile" value="1"/>
									&nbsp; <?php _e( 'Do you want to import users profile data (profile groups and fields will be created)?', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

						<?php if ( bp_is_active( 'friends' ) ) : ?>
							<li>
								<label for="import-friends">
									<input type="checkbox" name="bpdd[import-friends]" id="import-friends" value="1"/>
									&nbsp; <?php _e( 'Do you want to create some friends connections between imported users?', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

						<?php if ( bp_is_active( 'activity' ) ) : ?>
							<li>
								<label for="import-activity">
									<input type="checkbox" name="bpdd[import-activity]" id="import-activity" value="1"/>
									&nbsp; <?php _e( 'Do you want to import activity posts for users?', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

						<?php if ( bp_is_active( 'messages' ) ) : ?>
							<li>
								<label for="import-messages">
									<input type="checkbox" name="bpdd[import-messages]" id="import-messages" value="1"/>
									&nbsp; <?php _e( 'Do you want to import private messages between users?', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

					</ul>
				</li>

				<?php if ( bp_is_active( 'groups' ) ) : ?>
					<li class="groups">
						<label for="import-groups">
							<input type="checkbox" name="bpdd[import-groups]" id="import-groups" value="1"/>
							&nbsp; <?php _e( 'Do you want to import groups?', 'bp-default-data' ); ?></label>
						<ul>

							<li>
								<label for="import-g-members">
									<input type="checkbox" name="bpdd[import-g-members]" id="import-g-members" value="1"/>
									&nbsp; <?php _e( 'Do you want to import group members? Import users before doing this.', 'bp-default-data' ); ?>
								</label>
							</li>

							<?php if ( bp_is_active( 'activity' ) ) : ?>
								<li>
									<label for="import-g-activity">
										<input type="checkbox" name="bpdd[import-g-activity]" id="import-g-activity" value="1"/>
										&nbsp; <?php _e( 'Do you want to import group activity posts?', 'bp-default-data' ); ?>
									</label>
								</li>
							<?php endif; ?>

							<?php if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() ) : ?>
								<li>
									<label for="import-forums">
										<input type="checkbox" disabled name="bpdd[import-forums]" id="import-forums" value="1"/>
										&nbsp; <?php _e( 'Do you want to import groups\' forum topics and posts?', 'bp-default-data' ); ?>
									</label>
								</li>

							<?php else: ?>
								<li>
									<?php _e( 'You can\'t import anything forums-related, because Forum Component is not installed correctly. Please recheck your settings.', 'bp-default-data' ); ?>
								</li>
							<?php endif; ?>

						</ul>
					</li>
				<?php endif; ?>

			</ul>
			<!-- .items -->

			<p class="submit">
				<input class="button-primary" type="submit" name="bpdd-admin-submit" id="bpdd-admin-submit"
				       value="<?php esc_attr_e( 'Import Selected Data', 'bp-default-data' ); ?>"/>
				<input class="button" type="submit" name="bpdd-admin-clear" id="bpdd-admin-clear"
				       value="<?php esc_attr_e( 'Clear BuddyPress Data', 'bp-default-data' ); ?>"/>
			</p>

			<fieldset style="border: 2px solid #ccc;padding: 0 10px;margin-bottom: 10px">
				<legend style="font-weight: bold;"><?php _e( 'Important Information', 'bp-default-data' ); ?></legend>
				<p><?php _e( 'All users have the same password: <code>1234567890</code>', 'bp-default-data' ); ?></p>

				<p><?php _e( 'Friends connections don\'t produce notifications, while messages importing do.', 'bp-default-data' ); ?></p>

				<p><?php _e( 'xProfile data importing doesn\'t produce activity feed records.', 'bp-default-data' ); ?></p>
			</fieldset>

			<p class="description"><?php _e( 'Many thanks to <a href="http://imdb.com" target="_blank">IMDB.com</a> for movies titles (groups names), <a href="http://en.wikipedia.org" target="_blank">Wikipedia.org</a> (users names), <a href="http://en.wikipedia.org/wiki/Lorem_ipsum" target="_blank">Lorem Ipsum</a> (messages and forum posts), <a href="http://www.cs.virginia.edu/~robins/quotes.html">Dr. Gabriel Robins</a> and <a href="http://en.proverbia.net/shortfamousquotes.asp">Proverbia</a> (for the lists of quotes), <a href="http://www.youtube.com/">YouTube</a> and <a href="http://vimeo.com/">Vimeo</a> (for videos).', 'bp-default-data' ); ?></p>

			<?php wp_nonce_field( 'bpdd-admin' ); ?>

		</form>
		<!-- #bpdd-admin-form -->
	</div><!-- .wrap -->
	<?php
}

/**
 *  Importer engine - USERS
 */
function bpdd_import_users() {
	$users = array();

	$users_data = require_once( dirname( __FILE__ ) . '/data/users.php' );

	foreach ( $users_data as $user ) {
		$user_id = wp_insert_user( array(
			                           'user_login'      => $user['login'],
			                           'user_pass'       => $user['pass'],
			                           'display_name'    => $user['display_name'],
			                           'user_email'      => $user['email'],
			                           'user_registered' => bpdd_get_random_date( 45, 1 ),
		                           ) );

		if ( bp_is_active( 'xprofile' ) ) {
			xprofile_set_field_data( 1, $user_id, $user['display_name'] );
		}
		$name = explode( ' ', $user['display_name'] );
		update_user_meta( $user_id, 'first_name', $name[0] );
		update_user_meta( $user_id, 'last_name', isset( $name[1] ) ? $name[1] : '' );

		// BuddyPress 1.9+
		if ( function_exists( 'bp_update_user_last_activity' ) ) {
			bp_update_user_last_activity( $user_id, bpdd_get_random_date( 5 ) );
		} else {
			// BuddyPress 1.8.x and below
			bp_update_user_meta( $user_id, 'last_activity', bpdd_get_random_date( 5 ) );
		}

		bp_update_user_meta( $user_id, 'notification_messages_new_message', 'no' );
		bp_update_user_meta( $user_id, 'notification_friends_friendship_request', 'no' );
		bp_update_user_meta( $user_id, 'notification_friends_friendship_accepted', 'no' );

		$users[] = $user_id;
	}

	return $users;
}

/**
 * Import extended profile fields.
 *
 * @return int
 */
function bpdd_import_users_profile() {
	$count = 0;

	if ( ! bp_is_active( 'xprofile' ) ) {
		return $count;
	}

	$data = array();

	$xprofile_structure = require_once( dirname( __FILE__ ) . '/data/xprofile_structure.php' );

	// first import profile groups
	foreach ( $xprofile_structure as $group_type => $group_data ) {
		$group_id = xprofile_insert_field_group( array(
			                                         'name'        => $group_data['name'],
			                                         'description' => $group_data['desc'],
		                                         ) );

		// then import fields
		foreach ( $group_data['fields'] as $field_type => $field_data ) {
			$field_id = xprofile_insert_field( array(
				                                   'field_group_id' => $group_id,
				                                   'parent_id'      => 0,
				                                   'type'           => $field_type,
				                                   'name'           => $field_data['name'],
				                                   'description'    => $field_data['desc'],
				                                   'is_required'    => $field_data['required'],
				                                   'order_by'       => 'custom',
			                                   ) );

			if ( $field_id ) {
				bp_xprofile_update_field_meta( $field_id, 'default_visibility', $field_data['default-visibility'] );

				bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', $field_data['allow-custom-visibility'] );

				$data[ $field_id ]['type'] = $field_type;

				// finally import options
				if ( ! empty( $field_data['options'] ) ) {
					foreach ( $field_data['options'] as $option ) {
						$option_id = xprofile_insert_field( array(
							                                    'field_group_id'    => $group_id,
							                                    'parent_id'         => $field_id,
							                                    'type'              => 'option',
							                                    'name'              => $option['name'],
							                                    'can_delete'        => true,
							                                    'is_default_option' => $option['is_default_option'],
							                                    'option_order'      => $option['option_order'],
						                                    ) );

						$data[ $field_id ]['options'][ $option_id ] = $option['name'];
					}
				} else {
					$data[ $field_id ]['options'] = array();
				}
			}
		}
	}

	$xprofile_data = require_once( dirname( __FILE__ ) . '/data/xprofile_data.php' );
	$users         = bpdd_get_random_users_ids( 0 );

	// now import profile fields data for all fields for each user
	foreach ( $users as $user_id ) {
		foreach ( $data as $field_id => $field_data ) {
			switch ( $field_data['type'] ) {
				case 'datebox':
				case 'textarea':
				case 'number':
				case 'textbox':
				case 'url':
				case 'selectbox':
				case 'radio':
					if ( xprofile_set_field_data( $field_id, $user_id, $xprofile_data[ $field_data['type'] ][ array_rand( $xprofile_data[ $field_data['type'] ] ) ] ) ) {
						$count ++;
					}
					break;

				case 'checkbox':
				case 'multiselectbox':
					if ( xprofile_set_field_data( $field_id, $user_id, explode( ',', $xprofile_data[ $field_data['type'] ][ array_rand( $xprofile_data[ $field_data['type'] ] ) ] ) ) ) {
						$count ++;
					}
					break;
			}
		}
	}

	return $count;
}

/**
 * Import private messages between users.
 *
 * @return array
 */
function bpdd_import_users_messages() {
	$messages = array();

	if ( ! bp_is_active( 'messages' ) ) {
		return $messages;
	}

	/** @var $messages_subjects array */
	/** @var $messages_content array */
	require( dirname( __FILE__ ) . '/data/messages.php' );


	// first level messages
	for ( $i = 0; $i < 33; $i ++ ) {
		$messages[] = messages_new_message( array(
			                                    'sender_id'  => bpdd_get_random_users_ids( 1, 'string' ),
			                                    'recipients' => bpdd_get_random_users_ids( 1, 'array' ),
			                                    'subject'    => $messages_subjects[ array_rand( $messages_subjects ) ],
			                                    'content'    => $messages_content[ array_rand( $messages_content ) ],
			                                    'date_sent'  => bpdd_get_random_date( 15, 5 ),
		                                    ) );
	}

	for ( $i = 0; $i < 33; $i ++ ) {
		$messages[] = messages_new_message( array(
			                                    'sender_id'  => bpdd_get_random_users_ids( 1, 'string' ),
			                                    'recipients' => bpdd_get_random_users_ids( 2, 'array' ),
			                                    'subject'    => $messages_subjects[ array_rand( $messages_subjects ) ],
			                                    'content'    => $messages_content[ array_rand( $messages_content ) ],
			                                    'date_sent'  => bpdd_get_random_date( 13, 3 ),
		                                    ) );
	}

	for ( $i = 0; $i < 33; $i ++ ) {
		$messages[] = messages_new_message( array(
			                                    'sender_id'  => bpdd_get_random_users_ids( 1, 'string' ),
			                                    'recipients' => bpdd_get_random_users_ids( 3, 'array' ),
			                                    'subject'    => $messages_subjects[ array_rand( $messages_subjects ) ],
			                                    'content'    => $messages_content[ array_rand( $messages_content ) ],
			                                    'date_sent'  => bpdd_get_random_date( 10 ),
		                                    ) );
	}

	$messages[] = messages_new_message( array(
		                                    'sender_id'  => bpdd_get_random_users_ids( 1, 'string' ),
		                                    'recipients' => bpdd_get_random_users_ids( 5, 'array' ),
		                                    'subject'    => $messages_subjects[ array_rand( $messages_subjects ) ],
		                                    'content'    => $messages_content[ array_rand( $messages_content ) ],
		                                    'date_sent'  => bpdd_get_random_date( 5 ),
	                                    ) );

	return $messages;
}

/**
 * Import Activity - aka "status updates".
 *
 * @return int Number of activity records that were inserted into the database.
 */
function bpdd_import_users_activity() {
	$count = 0;

	if ( ! bp_is_active( 'activity' ) ) {
		return $count;
	}

	$users = bpdd_get_random_users_ids( 0 );

	/** @var $activity array */
	require( dirname( __FILE__ ) . '/data/activity.php' );

	for ( $i = 0; $i < 75; $i ++ ) {
		$user    = $users[ array_rand( $users ) ];
		$content = $activity[ array_rand( $activity ) ];

		if ( $bp_activity_id = bp_activity_post_update( array(
			                                                'user_id' => $user,
			                                                'content' => $content,
		                                                ) )
		) {
			$bp_activity                = new BP_Activity_Activity( $bp_activity_id );
			$bp_activity->date_recorded = bpdd_get_random_date( 44 );
			if ( $bp_activity->save() ) {
				$count ++;
			}
		}
	}

	return $count;
}

/**
 * Get random users from the DB and generate friends connections.
 *
 * @return int
 */
function bpdd_import_users_friends() {
	$count = 0;

	if ( ! bp_is_active( 'friends' ) ) {
		return $count;
	}

	$users = bpdd_get_random_users_ids( 50 );

	add_filter( 'bp_core_current_time', 'bpdd_friends_add_friend_date_fix' );

	for ( $i = 0; $i < 100; $i ++ ) {
		$user_one = $users[ array_rand( $users ) ];
		$user_two = $users[ array_rand( $users ) ];

		// Make them friends if possible.
		if ( friends_add_friend( $user_one, $user_two, true ) ) {
			$count ++;
		}
	}

	remove_filter( 'bp_core_current_time', 'bpdd_friends_add_friend_date_fix' );

	return $count;
}

/**
 *  Importer engine - GROUPS
 *
 * @param bool|array $users Users list we want to work with. Get random if empty.
 *
 * @return array
 */
function bpdd_import_groups( $users = false ) {
	$groups    = array();
	$group_ids = array();

	if ( ! bp_is_active( 'groups' ) ) {
		return $group_ids;
	}

	if ( empty( $users ) ) {
		$users = get_users();
	}

	require( dirname( __FILE__ ) . '/data/groups.php' );

	foreach ( $groups as $group ) {
		$creator_id = is_object( $users[ array_rand( $users ) ] ) ? $users[ array_rand( $users ) ]->ID : $users[ array_rand( $users ) ];
		$cur        = groups_create_group( array(
			                                   'creator_id'   => $creator_id,
			                                   'name'         => $group['name'],
			                                   'description'  => $group['description'],
			                                   'slug'         => groups_check_slug( sanitize_title( esc_attr( $group['name'] ) ) ),
			                                   'status'       => $group['status'],
			                                   'date_created' => bpdd_get_random_date( 30, 5 ),
			                                   'enable_forum' => $group['enable_forum']
		                                   ) );

		groups_update_groupmeta( $cur, 'total_member_count', 1 );
		groups_update_groupmeta( $cur, 'last_activity', bpdd_get_random_date( 10 ) );

		// create forums if Forum Component is active
		if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() ) {
			groups_new_group_forum( $cur, $group['name'], $group['description'] );
		}

		$group_ids[] = $cur;
	}

	return $group_ids;
}

/**
 * Import groups activity - aka "status updates".
 *
 * @return int
 */
function bpdd_import_groups_activity() {
	$count = 0;

	if ( ! bp_is_active( 'groups' ) || ! bp_is_active( 'activity' ) ) {
		return $count;
	}

	$users  = bpdd_get_random_users_ids( 0 );
	$groups = bpdd_get_random_groups_ids( 0 );

	/** @var $activity array */
	require( dirname( __FILE__ ) . '/data/activity.php' );

	for ( $i = 0; $i < 150; $i ++ ) {
		$user_id  = $users[ array_rand( $users ) ];
		$group_id = $groups[ array_rand( $groups ) ];
		$content  = $activity[ array_rand( $activity ) ];

		if ( ! groups_is_user_member( $user_id, $group_id ) ) {
			continue;
		}

		if ( $bp_activity_id = groups_post_update( array(
			                                           'user_id'  => $user_id,
			                                           'group_id' => $group_id,
			                                           'content'  => $content,
		                                           ) )
		) {
			$bp_activity                = new BP_Activity_Activity( $bp_activity_id );
			$bp_activity->date_recorded = bpdd_get_random_date( 29 );
			if ( $bp_activity->save() ) {
				$count ++;
			}
		}
	}

	return $count;
}

/**
 * Import groups members.
 *
 * @param bool $groups We can import random groups or work with a predefined list.
 *
 * @return array
 */
function bpdd_import_groups_members( $groups = false ) {
	$members = array();

	if ( ! bp_is_active( 'groups' ) ) {
		return $members;
	}

	if ( ! $groups ) {
		$groups = bpdd_get_random_groups_ids( 0 );
	}

	add_filter( 'bp_after_activity_add_parse_args', 'bpdd_groups_join_group_date_fix' );

	foreach ( $groups as $group_id ) {
		$user_ids = bpdd_get_random_users_ids( rand( 2, 15 ) );

		foreach ( $user_ids as $user_id ) {

			if ( groups_join_group( $group_id, $user_id ) ) {
				$members[] = $group_id;
			}
		}
	}

	remove_filter( 'bp_after_activity_add_parse_args', 'bpdd_groups_join_group_date_fix' );

	return $members;
}

/**
 * Give ability to import forums and topics for groups.
 * Not used currently.
 *
 * @param array $groups
 *
 * @return bool
 */
function bpdd_import_groups_forums(
	/** @noinspection PhpUnusedParameterInspection */
	$groups
) {
	return true;
}

/*******************************
 *********** Helpers ***********
 *******************************/

/**
 * Delete all imported information (will be hugely rewritten).
 */
function bpdd_clear_db() {
	global $wpdb;

	$prefix = bp_core_get_table_prefix();

	if ( bp_is_active( 'activity' ) ) {
		$sqls[] = "TRUNCATE TABLE {$prefix}bp_activity;";
		$sqls[] = "TRUNCATE TABLE {$prefix}bp_activity_meta;";
	}

	if ( bp_is_active( 'groups' ) ) {
		$sqls[] = "TRUNCATE TABLE {$prefix}bp_groups;";
		$sqls[] = "TRUNCATE TABLE {$prefix}bp_groups_members;";
		$sqls[] = "TRUNCATE TABLE {$prefix}bp_groups_groupmeta;";
	}

	if ( bp_is_active( 'messages' ) ) {
		$sqls[] = "TRUNCATE TABLE {$prefix}bp_messages_recipients;";
		$sqls[] = "TRUNCATE TABLE {$prefix}bp_messages_messages;";
	}

	if ( bp_is_active( 'friends' ) ) {
		$sqls[] = "TRUNCATE TABLE {$prefix}bp_friends;";
	}

	if ( bp_is_active( 'xprofile' ) ) {
		$sqls[] = "DELETE FROM {$prefix}bp_xprofile_data WHERE user_id > 1 OR field_id > 1;";
		$sqls[] = "DELETE FROM {$prefix}bp_xprofile_fields WHERE id > 1;";
		$sqls[] = "DELETE FROM {$prefix}bp_xprofile_groups WHERE id > 1;";
		$sqls[] = "DELETE FROM {$prefix}bp_xprofile_meta WHERE object_id > 1;";
	}

	if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() ) {
		$sqls[] = "TRUNCATE TABLE {$prefix}bb_posts;";
		$sqls[] = "DELETE FROM {$prefix}bb_forums WHERE forum_id > 1;";
	}

	$sqls[] = "TRUNCATE TABLE {$prefix}bp_notifications;";
	$sqls[] = "DELETE FROM {$wpdb->prefix}users WHERE ID > 1;";
	$sqls[] = "DELETE FROM {$wpdb->prefix}usermeta WHERE user_id > 1;";
	$sqls[] = "DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key = 'total_friend_count';";
	$sqls[] = "DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key = 'bp_latest_update';";
	$sqls[] = "DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key = 'last_activity';";

	foreach ( $sqls as $sql ) {
		$wpdb->query( $sql );
	}
}

/**
 * Fix the date issue, when all joined_group events took place at the same time.
 *
 * @param array $args Arguments that are passed to bp_activity_add().
 *
 * @return array
 */
function bpdd_groups_join_group_date_fix( $args ) {
	if (
		$args['type'] === 'joined_group' &&
		$args['component'] === 'groups'
	) {
		$args['recorded_time'] = bpdd_get_random_date( 25, 1 );
	}

	return $args;
}

/**
 * Fix the date issue, when all friends connections are done at the same time.
 *
 * @param string $current_time Default BuddyPress current timestamp.
 *
 * @return string
 */
function bpdd_friends_add_friend_date_fix( $current_time ) {
	return bpdd_get_random_date( 43 );
}

/**
 * Get the array (or a string) of group IDs.
 *
 * @param int $count If you need all, use 0.
 * @param string $output
 *
 * @return array|string Default is array.
 */
function bpdd_get_random_groups_ids( $count = 1, $output = 'array' ) {
	global $wpdb;
	$limit = '';

	if ( $count > 0 ) {
		$limit = ' LIMIT ' . $count;
	}

	$groups = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}bp_groups ORDER BY rand() {$limit}" );

	/*
	 * Convert to integers, because get_col() returns array of strings.
	 */
	$groups = array_map( 'intval', $groups );

	if ( $output === 'string' ) {
		return implode( ',', $groups );
	}

	return $groups;
}

/**
 * Get the array (or a string) of user IDs.
 *
 * @param int $count If you need all, use 0.
 * @param string $output
 *
 * @return array|string Default is array.
 */
function bpdd_get_random_users_ids( $count = 1, $output = 'array' ) {
	global $wpdb;
	$limit = '';

	if ( $count > 0 ) {
		$limit = ' LIMIT ' . $count;
	}

	$users = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} ORDER BY rand() {$limit}" );

	/*
	 * Convert to integers, because get_col() returns array of strings.
	 */
	$users = array_map( 'intval', $users );

	if ( $output === 'string' ) {
		return implode( ',', $users );
	}

	return $users;
}

/**
 * Get a random date between some days in history.
 * If [30;5] is specified - that means a random date between 30 and 5 days from now.
 *
 * @param int $days_from
 * @param int $days_to
 *
 * @return string
 */
function bpdd_get_random_date( $days_from = 30, $days_to = 0 ) {
	// $days_from should always be less than $days_to
	if ( $days_to > $days_from ) {
		$days_to = $days_from - 1;
	}

	$date_from = new DateTime( 'now - ' . $days_from . ' days' );
	$date_to   = new DateTime( 'now - ' . $days_to . ' days' );

	return date( 'Y-m-d H:i:s', mt_rand( $date_from->getTimestamp(), $date_to->getTimestamp() ) );
}
