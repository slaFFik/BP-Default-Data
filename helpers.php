<?php

/**
 * Get plugin admin area root page: settings.php for WPMS and tool.php for WP.
 * @return string
 */
function bpdd_get_root_admin_page() {
	return is_multisite() ? 'settings.php' : 'tools.php';
}

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

function bpdd_delete_import_records() {
	bp_delete_option( 'bpdd_import_users' );
	bp_delete_option( 'bpdd_import_groups' );
}
