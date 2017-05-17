<?php

/**
 * Get plugin admin area root page: settings.php for WPMS and tool.php for WP.
 * @return string
 */
function bpdd_get_root_admin_page() {
	return is_multisite() ? 'settings.php' : 'tools.php';
}

/**
 * Delete all imported information.
 */
function bpdd_clear_db() {
	global $wpdb;
	$bp = buddypress();

	/*
	 * Groups
	 */
	$groups = bp_get_option( 'bpdd_imported_group_ids' );
	if ( ! empty( $groups ) ) {
		foreach ( (array) $groups as $group_id ) {
			groups_delete_group( $group_id );
		}
	}

	/*
	 * Users and all their data.
	 */
	$users = bp_get_option( 'bpdd_imported_user_ids' );
	if ( ! empty( $users ) ) {
		$users_str = implode( ',', (array) $users );

		foreach ( (array) $users as $user_id ) {
			bp_core_delete_account( $user_id );
		}
	}

	/*
	 * PRIVATE MESSAGES are not deleted by BuddyPress when user is deleted, for some reason.
	 * This is a BuddyPress bug, and the fix below will stay here for some time
	 */
	$thread_ids = bp_get_option( 'bpdd_imported_user_messages_ids' );
	if ( ! empty( $thread_ids ) && ! empty( $users_str ) ) {
		$threads_str = implode( ',', (array) $thread_ids );

		// Finally, remove from the DB completely.
		foreach ( $thread_ids as $thread_id ) {
			// Get the message ids in order to delete their metas.
			$message_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT id FROM {$bp->messages->table_name_messages} 
				WHERE thread_id = %d",
				$thread_id ) );

			// Delete all the messages.
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$bp->messages->table_name_messages} 
				WHERE thread_id = %d",
				$thread_id ) );

			// Delete message meta.
			foreach ( $message_ids as $message_id ) {
				bp_messages_delete_meta( $message_id );
			}

			// Delete all the recipients.
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$bp->messages->table_name_recipients} 
				WHERE thread_id = %d",
				$thread_id ) );
		}
	}

	/*
	 * xProfile groups and fields, metas are not deleted - it's a BuddyPress bug.
	 */
	$xprofile_ids = bp_get_option( 'bpdd_imported_user_xprofile_ids' );
	foreach ( (array) $xprofile_ids as $xprofile_id ) {
		$group = new BP_XProfile_Group( $xprofile_id );
		$group->delete();
	}

	bpdd_delete_import_records();
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
 * @param string $output What to return: 'array' or 'string'. If string - comma separated.
 *
 * @return array|string Default is array.
 */
function bpdd_get_random_groups_ids( $count = 1, $output = 'array' ) {
	$groups_arr = (array) bp_get_option( 'bpdd_imported_group_ids' );

	if ( ! empty( $groups_arr ) ) {
		$total_groups = count( $groups_arr );
		if ( $count <= 0 || $count > $total_groups ) {
			$count = $total_groups;
		}

		// Get random groups.
		$random_keys = (array) array_rand( $groups_arr, $count );
		$groups      = array();
		foreach ( $groups_arr as $key => $value ) {
			if ( in_array( $key, $random_keys ) ) {
				$groups[] = $value;
			}
		}
	} else {
		global $wpdb;
		$bp = buddypress();

		$limit = '';
		if ( $count > 0 ) {
			$limit = 'LIMIT ' . (int) $count;
		}

		$groups = $wpdb->get_col( "SELECT id FROM {$bp->groups->table_name} ORDER BY rand() {$limit}" );
	}

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
 * @param string $output What to return: 'array' or 'string'. If string - comma separated.
 *
 * @return array|string Default is array.
 */
function bpdd_get_random_users_ids( $count = 1, $output = 'array' ) {
	$users_arr = (array) bp_get_option( 'bpdd_imported_user_ids' );

	if ( ! empty( $users_arr ) ) {
		$total_members = count( $users_arr );
		if ( $count <= 0 || $count > $total_members ) {
			$count = $total_members;
		}

		// Get random users.
		$random_keys = (array) array_rand( $users_arr, $count );
		$users       = array();
		foreach ( $users_arr as $key => $value ) {
			if ( in_array( $key, $random_keys ) ) {
				$users[] = $value;
			}
		}
	} else {
		// Get by default (if no users were imported) all currently registered users.
		$users = get_users( array(
			                    'fields' => 'ID',
		                    ) );
	}

	/*
	 * Convert to integers, because get_col() and get_users() return array of strings.
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

/**
 * Get the current timestamp, using current blog time settings.
 *
 * @return int
 */
function bpdd_get_time() {
	return (int) current_time( 'timestamp' );
}


/**
 * Check whether something was imported or not.
 *
 * @param string $group Possible values: users, groups
 * @param string $import What exactly was imported
 *
 * @return bool
 */
function bpdd_is_imported( $group, $import ) {
	$group  = sanitize_key( $group );
	$import = sanitize_key( $import );

	if ( ! in_array( $group, array( 'users', 'groups' ) ) ) {
		return false;
	}

	return array_key_exists( $import, (array) bp_get_option( 'bpdd_import_' . $group ) );
}

/**
 * Display a disabled attribute for inputs of the particular value was already imported.
 *
 * @param string $group
 * @param string $import
 */
function bpdd_imported_disabled( $group, $import ) {
	$group  = sanitize_key( $group );
	$import = sanitize_key( $import );

	if ( ! in_array( $group, array( 'users', 'groups' ) ) ) {
		echo '';
	}

	echo bpdd_is_imported( $group, $import ) ? 'disabled="disabled" checked="checked"' : '';
}

/**
 * Save when the importing was done.
 *
 * @param string $group
 * @param string $import
 *
 * @return bool
 */
function bpdd_update_import( $group, $import ) {
	$group  = sanitize_key( $group );
	$import = sanitize_key( $import );

	if ( ! in_array( $group, array( 'users', 'groups' ) ) ) {
		return false;
	}

	$values            = bp_get_option( 'bpdd_import_' . $group );
	$values[ $import ] = bpdd_get_time();

	return bp_update_option( 'bpdd_import_' . $group, $values );
}

/**
 * Remove all imported ids and the indication, that importing was done.
 */
function bpdd_delete_import_records() {
	bp_delete_option( 'bpdd_import_users' );
	bp_delete_option( 'bpdd_import_groups' );

	bp_delete_option( 'bpdd_imported_user_ids' );
	bp_delete_option( 'bpdd_imported_group_ids' );

	bp_delete_option( 'bpdd_imported_user_messages_ids' );
	bp_delete_option( 'bpdd_imported_user_xprofile_ids' );
}
