<?php

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

		bp_update_user_last_activity( $user_id, bpdd_get_random_date( 5 ) );

		bp_update_user_meta( $user_id, 'notification_messages_new_message', 'no' );
		bp_update_user_meta( $user_id, 'notification_friends_friendship_request', 'no' );
		bp_update_user_meta( $user_id, 'notification_friends_friendship_accepted', 'no' );

		$users[] = $user_id;
	}

	if ( ! empty( $users ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bpdd_imported_user_ids', $users );
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

	// Firstly, import profile groups.
	foreach ( $xprofile_structure as $group_type => $group_data ) {
		$group_id = xprofile_insert_field_group( array(
			                                         'name'        => $group_data['name'],
			                                         'description' => $group_data['desc'],
		                                         ) );
		$groups[] = $group_id;

		// Then import fields.
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

	// Now import profile fields data for all fields for each user.
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

	if ( ! empty( $groups ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bpdd_imported_user_xprofile_ids', $groups );
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

	if ( ! empty( $messages ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bpdd_imported_user_messages_ids', $messages );
	}

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

	// Use currently available users from DB if no default were specified.
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

		groups_update_groupmeta( $cur, 'last_activity', bpdd_get_random_date( 10 ) );

		// create forums if Forum Component is active
		if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() ) {
			groups_new_group_forum( $cur, $group['name'], $group['description'] );
		}

		$group_ids[] = $cur;
	}

	if ( ! empty( $group_ids ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bpdd_imported_group_ids', $group_ids );
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
		$user_ids = bpdd_get_random_users_ids( mt_rand( 2, 15 ) );

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
