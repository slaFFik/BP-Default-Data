<?php
/**
 * Plugin Name: BuddyPress Default Data
 * Plugin URI:  https://ovirium.com
 * Description: Create lots of users, groups, activity items, messages, profile data - useful for BuddyPress testing purpose.
 * Author:      slaFFik
 * Version:     1.3.0
 * Author URI:  https://ovirium.com
 * Text Domain: bp-default-data
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/vendor/bemailr/wp-requirements/wpr-loader.php';

define( 'BPDD_VERSION', '1.3.0' );

register_activation_hook( __FILE__, 'bpdd_schedule_ut_request' );
register_deactivation_hook( __FILE__, 'bpdd_unschedule_ut_request' );

/**
 * Adds a custom cron schedule for every 5 minutes.
 *
 * @since 1.3.0
 *
 * @param array $schedules An array of non-default cron schedules.
 *
 * @return array Filtered array of non-default cron schedules.
 */
function bpdd_register_weekly_cron_schedule( $schedules ) {

	if ( isset( $schedules['weekly'] ) ) {
		return $schedules;
	}

	$schedules['weekly'] = array(
		'interval' => WEEK_IN_SECONDS,
		'display'  => esc_html__( 'Once Weekly', 'bp-default-data' ),
	);

	return $schedules;
}

add_filter( 'cron_schedules', 'bpdd_register_weekly_cron_schedule' );

/**
 * Maybe schedule a request.
 *
 * @since 1.3.0
 */
function bpdd_schedule_ut_request() {

	if ( ! wp_next_scheduled( 'bpdd_ut_weekly_request' ) ) {
		wp_schedule_event( time() + wp_rand( 0, DAY_IN_SECONDS ), 'weekly', 'bpdd_ut_weekly_request' );
	}
}

/**
 * Unschedule the request.
 *
 * @since 1.3.0
 */
function bpdd_unschedule_ut_request() {

	wp_clear_scheduled_hook( 'bpdd_ut_weekly_request' );
}

/**
 * Load the plugin admin area registration hook.
 */
function bpdd_init() {

	if ( ! WP_Requirements::validate( __FILE__ ) ) {
		return;
	}

	require_once __DIR__ . '/helpers.php';

	add_action( bp_core_admin_hook(), 'bpdd_admin_page', 99 );
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bpdd_plugins_settings_link' );

	bpdd_schedule_ut_request();
}

add_action( 'bp_loaded', 'bpdd_init' );

/**
 * Make the plugin translatable.
 */
function bpdd_load_plugin_textdomain() {

	load_plugin_textdomain( 'bp-default-data' );
}

add_action( 'plugins_loaded', 'bpdd_load_plugin_textdomain' );

/**
 * Add the Settings link for the plugin on Plugins page in wp-admin area.
 *
 * @param array $links
 *
 * @return array
 */
function bpdd_plugins_settings_link( $links ) {

	$links['settings'] = '<a href="' . esc_url( bp_get_admin_url( bpdd_get_root_admin_page() . '?page=bpdd-setup' ) ) . '">' . esc_html__( 'Import Data', 'bp-default-data' ) . '</a>';

	return $links;
}

/**
 * Register admin area page link and its handler.
 */
function bpdd_admin_page() {

	if ( ! is_super_admin() ) {
		return;
	}

	add_submenu_page(
		bpdd_get_root_admin_page(),
		esc_html__( 'BP Default Data', 'bp-default-data' ),
		esc_html__( 'BP Default Data', 'bp-default-data' ),
		'manage_options',
		'bpdd-setup',
		'bpdd_admin_page_content'
	);
}

/**
 * Display the admin area page.
 */
function bpdd_admin_page_content() {
	?>

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
		<h1><?php esc_html_e( 'BuddyPress Default Data', 'bp-default-data' ); ?> <sup>v<?php echo BPDD_VERSION ?></sup></h1>

		<?php
		if ( ! empty( $_POST['bpdd-admin-clear'] ) ) {
			bpdd_clear_db();
			echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Everything created by this plugin was successfully deleted.', 'bp-default-data' ) . '</p></div>';
		}

		if ( isset( $_POST['bpdd-admin-submit'] ) ) {
			// Cound what we have just imported.
			$imported = array();

			// Check nonce before we do anything.
			check_admin_referer( 'bpdd-admin' );

			include_once __DIR__ . '/process.php';

			// Import users
			if ( isset( $_POST['bpdd']['import-users'] ) && ! bpdd_is_imported( 'users', 'users' ) ) {
				$users             = bpdd_import_users();
				$imported['users'] = sprintf( /* translators: formatted number. */
					esc_html__( '%s new users', 'bp-default-data' ),
					number_format_i18n( count( $users ) )
				);
				bpdd_update_import( 'users', 'users' );
			}

			if ( isset( $_POST['bpdd']['import-profile'] ) && ! bpdd_is_imported( 'users', 'xprofile' ) ) {
				$profile             = bpdd_import_users_profile();
				$imported['profile'] = sprintf( /* translators: formatted number. */
					esc_html__( '%s profile entries', 'bp-default-data' ),
					number_format_i18n( $profile )
				);
				bpdd_update_import( 'users', 'xprofile' );
			}

			if ( isset( $_POST['bpdd']['import-friends'] ) && ! bpdd_is_imported( 'users', 'friends' ) ) {
				$friends             = bpdd_import_users_friends();
				$imported['friends'] = sprintf( /* translators: formatted number. */
					esc_html__( '%s friends connections', 'bp-default-data' ),
					number_format_i18n( $friends )
				);
				bpdd_update_import( 'users', 'friends' );
			}

			if ( isset( $_POST['bpdd']['import-messages'] ) && ! bpdd_is_imported( 'users', 'messages' ) ) {
				$messages             = bpdd_import_users_messages();
				$imported['messages'] = sprintf( /* translators: formatted number. */
					esc_html__( '%s private messages', 'bp-default-data' ),
					number_format_i18n( count( $messages ) )
				);
				bpdd_update_import( 'users', 'messages' );
			}

			if ( isset( $_POST['bpdd']['import-activity'] ) && ! bpdd_is_imported( 'users', 'activity' ) ) {
				$activity             = bpdd_import_users_activity();
				$imported['activity'] = sprintf( /* translators: formatted number. */
					esc_html__( '%s personal activity items', 'bp-default-data' ),
					number_format_i18n( $activity )
				);
				bpdd_update_import( 'users', 'activity' );
			}

			// Import groups
			if ( isset( $_POST['bpdd']['import-groups'] ) && ! bpdd_is_imported( 'groups', 'groups' ) ) {
				$groups             = bpdd_import_groups();
				$imported['groups'] = sprintf( /* translators: formatted number. */
					esc_html__( '%s new groups', 'bp-default-data' ),
					number_format_i18n( count( $groups ) )
				/* translators: formatted number. */ );
				bpdd_update_import( 'groups', 'groups' );
			}
			if ( isset( $_POST['bpdd']['import-g-members'] ) && ! bpdd_is_imported( 'groups', 'members' ) ) {
				$g_members             = bpdd_import_groups_members();
				$imported['g_members'] = sprintf( /* translators: formatted number. */
					esc_html__( '%s groups members (1 user can be in several groups)', 'bp-default-data' ),
					number_format_i18n( count( $g_members ) )
				);
				bpdd_update_import( 'groups', 'members' );
			}

			//if ( isset( $_POST['bpdd']['import-forums'] ) && ! bpdd_is_imported( 'groups', 'forums' ) ) {
			//	$forums             = bpdd_import_groups_forums( $groups );
			//	$imported['forums'] = sprintf( __( '%s groups forum topics', 'bp-default-data' ), number_format_i18n( count( $forums ) ) );
			//  bpdd_update_import( 'groups', 'forums' );
			//}

			if ( isset( $_POST['bpdd']['import-g-activity'] ) && ! bpdd_is_imported( 'groups', 'activity' ) ) {
				$g_activity             = bpdd_import_groups_activity();
				$imported['g_activity'] = sprintf( /* translators: formatted number. */
					esc_html__( '%s groups activity items', 'bp-default-data' ),
					number_format_i18n( $g_activity )
				);
				bpdd_update_import( 'groups', 'activity' );
			}
			?>

			<div id="message" class="updated fade">
				<p>
					<?php
					esc_html_e( 'Data was successfully imported', 'bp-default-data' );
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
				jQuery( document ).ready( function( $ ) {
					$( '#import-profile, #import-friends, #import-activity, #import-messages' ).click( function() {
						if ( $( this ).attr( 'checked' ) === 'checked' && !$( '#import-users' ).attr( 'disabled' ) ) {
							$( '#import-users' ).attr( 'checked', 'checked' );
						}
					} );
					$( '#import-users' ).click( function() {
						if ( $( this ).attr( 'checked' ) !== 'checked' ) {
							$( '#import-profile, #import-friends, #import-activity, #import-messages' ).removeAttr( 'checked' );
						}
					} );

					$( '#import-forums, #import-g-members, #import-g-activity' ).click( function() {
						if ( $( this ).attr( 'checked' ) === 'checked' && !$( '#import-groups' ).attr( 'disabled' ) ) {
							$( '#import-groups' ).attr( 'checked', 'checked' );
						}
					} );
					$( '#import-groups' ).click( function() {
						if ( $( this ).attr( 'checked' ) !== 'checked' ) {
							$( '#import-forums, #import-g-members, #import-g-activity' ).removeAttr( 'checked' );
						}
					} );

					$( '#bpdd-admin-clear' ).click( function() {
						if ( confirm( '<?php echo esc_js( esc_html__( 'Are you sure you want to delete all *imported* content - users, groups, messages, activities, forum topics etc? Content, that was created by you and others, and not by this plugin, will not be deleted.', 'bp-default-data' ) ); ?>' ) ) {
							return true;
						}

						return false;
					} );

					$( '#usage-tracking' ).click( function() {
						var $checkbox = $( this );
						$.ajax( {
							 type: 'POST',
							 url: ajaxurl,
							 data: {
								 action: 'bpdd_ajax_usage_tracking_toggle',
							 },
							 beforeSend: function() {
								 $checkbox.attr( 'disabled', true );
							 },
						 } )
						 .always( function() {
							 $checkbox.removeAttr( 'disabled' );
						 } );
					} );
				} );
			</script>

			<p><?php esc_html_e( 'Please do not mess importing users and their data with groups on a slow server (or shared hosting). Importing is rather heavy process, so please finish with members first and then work with groups.', 'bp-default-data' ); ?></p>

			<h3><?php esc_html_e( 'What do you want to import?', 'bp-default-data' ); ?></h3>

			<ul class="items">
				<li class="users">
					<label for="import-users">
						<input type="checkbox" name="bpdd[import-users]" id="import-users" value="1" <?php bpdd_imported_disabled( 'users', 'users' ) ?>/>
						<?php esc_html_e( 'Users', 'bp-default-data' ); ?>

						<span class="description"><?php echo wp_kses( __( '- all imported users have the same password: <code>1234567890</code>', 'bp-default-data' ), array( 'code' => true ) ); ?></span>
					</label>

					<ul>
						<?php if ( bp_is_active( 'xprofile' ) ) : ?>
							<li>
								<label for="import-profile">
									<input type="checkbox" name="bpdd[import-profile]" id="import-profile"
										value="1" <?php bpdd_imported_disabled( 'users', 'xprofile' ) ?>/>
									<?php esc_html_e( 'Profile data (profile groups and fields with values, won\'t generate activity records)', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

						<?php if ( bp_is_active( 'friends' ) ) : ?>
							<li>
								<label for="import-friends">
									<input type="checkbox" name="bpdd[import-friends]" id="import-friends"
										value="1" <?php bpdd_imported_disabled( 'users', 'friends' ) ?>/>
									<?php esc_html_e( 'Friends connections', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

						<?php if ( bp_is_active( 'activity' ) ) : ?>
							<li>
								<label for="import-activity">
									<input type="checkbox" name="bpdd[import-activity]" id="import-activity"
										value="1" <?php bpdd_imported_disabled( 'users', 'activity' ) ?>/>
									<?php esc_html_e( 'Activity posts', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

						<?php if ( bp_is_active( 'messages' ) ) : ?>
							<li>
								<label for="import-messages">
									<input type="checkbox" name="bpdd[import-messages]" id="import-messages"
										value="1" <?php bpdd_imported_disabled( 'users', 'messages' ) ?>/>
									<?php esc_html_e( 'Private messages', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

					</ul>
				</li>

				<?php if ( bp_is_active( 'groups' ) ) : ?>
					<li class="groups">
						<label for="import-groups">
							<input type="checkbox" name="bpdd[import-groups]" id="import-groups"
								value="1" <?php bpdd_imported_disabled( 'groups', 'groups' ) ?>/>
							<?php esc_html_e( 'Groups', 'bp-default-data' ); ?></label>
						<ul>

							<li>
								<label for="import-g-members">
									<input type="checkbox" name="bpdd[import-g-members]" id="import-g-members"
										value="1" <?php bpdd_imported_disabled( 'groups', 'members' ) ?>/>
									<?php esc_html_e( 'Members', 'bp-default-data' ); ?>
								</label>
							</li>

							<?php if ( bp_is_active( 'activity' ) ) : ?>
								<li>
									<label for="import-g-activity">
										<input type="checkbox" name="bpdd[import-g-activity]" id="import-g-activity"
											value="1" <?php bpdd_imported_disabled( 'groups', 'activity' ) ?>/>
										<?php esc_html_e( 'Activity posts', 'bp-default-data' ); ?>
									</label>
								</li>
							<?php endif; ?>

							<?php if ( bp_is_active( 'forums' ) && function_exists( 'bp_forums_is_installed_correctly' ) && bp_forums_is_installed_correctly() ) : ?>
								<li>
									<label for="import-forums">
										<input type="checkbox" disabled name="bpdd[import-forums]" id="import-forums"
											value="1" <?php bpdd_imported_disabled( 'groups', 'forums' ) ?>/>
										<?php esc_html_e( 'Forum topics and posts', 'bp-default-data' ); ?>
									</label>
								</li>

							<?php else: ?>
								<li>
									<?php
									echo wp_kses(
										__( '<strong>Note:</strong> You can\'t import anything forums-related, because Forum Component is not installed correctly. Please recheck your settings.', 'bp-default-data' ),
										array(
											'strong' => true,
										)
									); ?>
								</li>
							<?php endif; ?>

						</ul>
					</li>
				<?php endif; ?>

			</ul>
			<!-- .items -->

			<p class="submit">
				<input class="button-primary" type="submit" name="bpdd-admin-submit" id="bpdd-admin-submit"
					value="<?php esc_attr_e( 'Import Selected Data', 'bp-default-data' ); ?>" />
				<input class="button" type="submit" name="bpdd-admin-clear" id="bpdd-admin-clear"
					value="<?php esc_attr_e( 'Clear BuddyPress Data', 'bp-default-data' ); ?>" />
			</p>

			<fieldset style="border: 2px solid #ccc;padding: 0 10px;margin-bottom: 10px">
				<legend style="font-weight: bold;"><?php esc_html_e( 'Usage Tracking', 'bp-default-data' ); ?></legend>
				<p><?php esc_html_e( 'I want to better understand how people are using this plugin, and for this I need some additional information from you.', 'bp-default-data' ); ?></p>
				<p><?php esc_html_e( 'Please allow me to collect this information: PHP version, WordPress version, BuddyPress version, list of activated BuddyPress components and its template pack, whether cover image uploads enabled for members and groups, list of selected imported data groups.', 'bp-default-data' ); ?></p>
				<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
					<p><code><?php echo wp_json_encode( bpdd_ut_request_data() ); ?></code></p>
				<?php endif; ?>
				<p>
					<label>
						<input type="checkbox" id="usage-tracking" <?php checked( true, (bool) bp_get_option( 'bpdd_usage_tracking_enabled', false ) ); ?>> <?php esc_html_e( 'Allow sending the data listed above, and only that.', 'bp-default-data' ); ?>
					</label>
				</p>
			</fieldset>

			<p class="description">
				Many thanks to <a href="https://imdb.com" target="_blank" rel="noopener noreferrer">IMDB.com</a> for movie titles (groups names),
				<a href="https://en.wikipedia.org" target="_blank" rel="noopener noreferrer">Wikipedia.org</a> (users names),
				<a href="https://en.wikipedia.org/wiki/Lorem_ipsum" target="_blank" rel="noopener noreferrer">Lorem Ipsum</a> (messages and forum posts),
				<a href="http://www.cs.virginia.edu/~robins/quotes.html" target="_blank" rel="noopener noreferrer">Dr. Gabriel Robins</a> and
				<a href="https://proverbia.net" target="_blank" rel="noopener noreferrer">Proverbia</a> (lists of quotes),
				<a href="https://www.youtube.com" target="_blank" rel="noopener noreferrer">YouTube</a> and <a href="https://vimeo.com" target="_blank" rel="noopener noreferrer">Vimeo</a> (videos),
				<a href="https://8biticon.com" target="_blank" rel="noopener noreferrer">8biticon.com</a> (avatars and plugin icon).
			</p>

			<?php wp_nonce_field( 'bpdd-admin' ); ?>

		</form>
		<!-- #bpdd-admin-form -->
	</div><!-- .wrap -->
	<?php
}

/**
 * AJAX: enable or disable the usage tracking status.
 *
 * @since 1.3.0
 */
function bpdd_ajax_usage_tracking_toggle() {

	if ( ! is_super_admin() ) {
		wp_send_json_error();
	}

	$status = (bool) bp_get_option( 'bpdd_usage_tracking_enabled', false );

	bp_update_option( 'bpdd_usage_tracking_enabled', ! $status );

	wp_send_json_success();
}

add_action( 'wp_ajax_bpdd_ajax_usage_tracking_toggle', 'bpdd_ajax_usage_tracking_toggle' );

/**
 * Send a usage tracking request, if allowed.
 *
 * @since 1.3.0
 */
function bpdd_send_ut_request() {

	// Whether we were granted a permission.
	if ( ! ( (bool) bp_get_option( 'bpdd_usage_tracking_enabled', false ) ) ) {
		return;
	}

	// Perform a non-blocking request.
	wp_remote_post(
		'https://api.ut.ovirium.com/track',
		array(
			'method'      => 'POST',
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => false,
			'headers'     => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'API-KEY'      => 'CDc6K8LemniOWIYLyZQUQKmEQwgLDKj6eY4BdUQVzq03UChCjqEXrvC5zDms',
			),
			'body'        => bpdd_ut_request_data(),
		)
	);
}

add_action( 'bpdd_ut_weekly_request', 'bpdd_send_ut_request' );

/**
 * All the data the is sent via request.
 *
 * @since 1.3.0
 *
 * @return array
 */
function bpdd_ut_request_data() {

	return array(
		'php_version'           => implode( '.', array( PHP_MAJOR_VERSION, PHP_MINOR_VERSION ) ),
		'wp_version'            => $GLOBALS['wp_version'],
		'bp_version'            => bp_get_version(),
		'bp_components'         => array_keys( buddypress()->active_components ),
		'bp_template_pack'      => bp_get_theme_compat_name(),
		'bp_covers_users'       => ! bp_disable_cover_image_uploads() ? 'enabled' : 'disabled',
		'bp_covers_groups'      => ! bp_disable_group_cover_image_uploads() ? 'enabled' : 'disabled',
		'bpdd_selected_imports' => array_filter(
			array_map(
				function ( $group_import ) {

					list( $group, $import ) = explode( '_', $group_import );

					return bpdd_is_imported( $group, $import ) ? $group_import : false;
				},
				array(
					'users_users',
					'users_xprofile',
					'users_friends',
					'users_activity',
					'users_messages',
					'groups_groups',
					'groups_members',
					'groups_activity',
					'groups_forum',
				)
			)
		),
	);
}
