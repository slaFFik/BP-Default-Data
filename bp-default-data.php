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

require_once dirname( __FILE__ ) . '/vendor/bemailr/wp-requirements/wpr-loader.php';

define( 'BPDD_VERSION', '1.1.2' );

/**
 * Make the plugin translatable.
 */
function bpdd_load_plugin_textdomain() {
	load_plugin_textdomain( 'bp-default-data', false, basename( dirname( __FILE__ ) ) . '/languages/' );
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
	$links['settings'] = '<a href="' . bp_get_admin_url( bpdd_get_root_admin_page() . '?page=bpdd-setup' ) . '">' . __( 'Import Data', 'bp-default-data' ) . '</a>';

	return $links;
}

/**
 * Load the plugin admin area registration hook.
 */
function bpdd_init() {
	if ( ! WP_Requirements::validate( __FILE__ ) ) {
		return;
	}

	add_action( bp_core_admin_hook(), 'bpdd_admin_page', 99 );
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bpdd_plugins_settings_link' );

	require_once __DIR__ . '/helpers.php';
}

add_action( 'bp_loaded', 'bpdd_init' );

/**
 * Register admin area page link and its handler.
 */
function bpdd_admin_page() {
	if ( ! is_super_admin() ) {
		return;
	}

	add_submenu_page(
		bpdd_get_root_admin_page(),
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
			// Cound what we have just imported.
			$imported = array();

			// Check nonce before we do anything.
			check_admin_referer( 'bpdd-admin' );

			include_once __DIR__ . '/process.php';

			// Import users
			if ( isset( $_POST['bpdd']['import-users'] ) && ! bpdd_is_imported( 'users', 'users' ) ) {
				$users             = bpdd_import_users();
				$imported['users'] = sprintf( __( '%s new users', 'bp-default-data' ), number_format_i18n( count( $users ) ) );
				bpdd_update_import( 'users', 'users' );
			}

			if ( isset( $_POST['bpdd']['import-profile'] ) && ! bpdd_is_imported( 'users', 'xprofile' ) ) {
				$profile             = bpdd_import_users_profile();
				$imported['profile'] = sprintf( __( '%s profile entries', 'bp-default-data' ), number_format_i18n( $profile ) );
				bpdd_update_import( 'users', 'xprofile' );
			}

			if ( isset( $_POST['bpdd']['import-friends'] ) && ! bpdd_is_imported( 'users', 'friends' ) ) {
				$friends             = bpdd_import_users_friends();
				$imported['friends'] = sprintf( __( '%s friends connections', 'bp-default-data' ), number_format_i18n( $friends ) );
				bpdd_update_import( 'users', 'friends' );
			}

			if ( isset( $_POST['bpdd']['import-messages'] ) && ! bpdd_is_imported( 'users', 'messages' ) ) {
				$messages             = bpdd_import_users_messages();
				$imported['messages'] = sprintf( __( '%s private messages', 'bp-default-data' ), number_format_i18n( count( $messages ) ) );
				bpdd_update_import( 'users', 'messages' );
			}

			if ( isset( $_POST['bpdd']['import-activity'] ) && ! bpdd_is_imported( 'users', 'activity' ) ) {
				$activity             = bpdd_import_users_activity();
				$imported['activity'] = sprintf( __( '%s personal activity items', 'bp-default-data' ), number_format_i18n( $activity ) );
				bpdd_update_import( 'users', 'activity' );
			}

			// Import groups
			if ( isset( $_POST['bpdd']['import-groups'] ) && ! bpdd_is_imported( 'groups', 'groups' ) ) {
				$groups             = bpdd_import_groups();
				$imported['groups'] = sprintf( __( '%s new groups', 'bp-default-data' ), number_format_i18n( count( $groups ) ) );
				bpdd_update_import( 'groups', 'groups' );
			}
			if ( isset( $_POST['bpdd']['import-g-members'] ) && ! bpdd_is_imported( 'groups', 'members' ) ) {
				$g_members             = bpdd_import_groups_members();
				$imported['g_members'] = sprintf( __( '%s groups members (1 user can be in several groups)', 'bp-default-data' ), number_format_i18n( count( $g_members ) ) );
				bpdd_update_import( 'groups', 'members' );
			}

			//if ( isset( $_POST['bpdd']['import-forums'] ) && ! bpdd_is_imported( 'groups', 'forums' ) ) {
			//	$forums             = bpdd_import_groups_forums( $groups );
			//	$imported['forums'] = sprintf( __( '%s groups forum topics', 'bp-default-data' ), number_format_i18n( count( $forums ) ) );
			//  bpdd_update_import( 'groups', 'forums' );
			//}

			if ( isset( $_POST['bpdd']['import-g-activity'] ) && ! bpdd_is_imported( 'groups', 'activity' ) ) {
				$g_activity             = bpdd_import_groups_activity();
				$imported['g_activity'] = sprintf( __( '%s groups activity items', 'bp-default-data' ), number_format_i18n( $g_activity ) );
				bpdd_update_import( 'groups', 'activity' );
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
                        if (jQuery(this).attr('checked') === 'checked' && !jQuery('#import-users').attr('disabled')) {
                            jQuery('#import-users').attr('checked', 'checked');
                        }
                    });
                    jQuery('#import-users').click(function () {
                        if (jQuery(this).attr('checked') !== 'checked') {
                            jQuery('#import-profile, #import-friends, #import-activity, #import-messages').removeAttr('checked');
                        }
                    });

                    jQuery('#import-forums, #import-g-members, #import-g-activity').click(function () {
                        if (jQuery(this).attr('checked') === 'checked' && !jQuery('#import-groups').attr('disabled')) {
                            jQuery('#import-groups').attr('checked', 'checked');
                        }
                    });
                    jQuery('#import-groups').click(function () {
                        if (jQuery(this).attr('checked') !== 'checked') {
                            jQuery('#import-forums, #import-g-members, #import-g-activity').removeAttr('checked');
                        }
                    });

                    jQuery("#bpdd-admin-clear").click(function () {
                        if (confirm('<?php echo esc_js( __( 'Are you sure you want to delete all users (except one with ID=1), groups, messages, activities, forum topics etc?', 'bp-default-data' ) ); ?>')) {
                            return true;
                        }

                        return false;
                    });
                });
			</script>

			<p><?php _e( 'Please do not mess importing users and their data with groups on a slow server (or shared hosting). Importing is rather heavy process, so please finish with members first and then work with groups.', 'bp-default-data' ); ?></p>

			<p><?php _e( 'What do you want to import?', 'bp-default-data' ); ?></p>

			<ul class="items">
				<li class="users">
					<label for="import-users">
						<input type="checkbox" name="bpdd[import-users]" id="import-users" value="1" <?php bpdd_imported_disabled( 'users', 'users' ) ?>/>
						<?php _e( 'Users', 'bp-default-data' ); ?>
					</label>
					<ul>

						<?php if ( bp_is_active( 'xprofile' ) ) : ?>
							<li>
								<label for="import-profile">
									<input type="checkbox" name="bpdd[import-profile]" id="import-profile"
									       value="1" <?php bpdd_imported_disabled( 'users', 'xprofile' ) ?>/>
									<?php _e( 'Profile data (profile groups and fields with values)', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

						<?php if ( bp_is_active( 'friends' ) ) : ?>
							<li>
								<label for="import-friends">
									<input type="checkbox" name="bpdd[import-friends]" id="import-friends"
									       value="1" <?php bpdd_imported_disabled( 'users', 'friends' ) ?>/>
									<?php _e( 'Friends connections', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

						<?php if ( bp_is_active( 'activity' ) ) : ?>
							<li>
								<label for="import-activity">
									<input type="checkbox" name="bpdd[import-activity]" id="import-activity"
									       value="1" <?php bpdd_imported_disabled( 'users', 'activity' ) ?>/>
									<?php _e( 'Activity posts', 'bp-default-data' ); ?>
								</label>
							</li>
						<?php endif; ?>

						<?php if ( bp_is_active( 'messages' ) ) : ?>
							<li>
								<label for="import-messages">
									<input type="checkbox" name="bpdd[import-messages]" id="import-messages"
									       value="1" <?php bpdd_imported_disabled( 'users', 'messages' ) ?>/>
									<?php _e( 'Private messages', 'bp-default-data' ); ?>
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
							<?php _e( 'Groups', 'bp-default-data' ); ?></label>
						<ul>

							<li>
								<label for="import-g-members">
									<input type="checkbox" name="bpdd[import-g-members]" id="import-g-members"
									       value="1" <?php bpdd_imported_disabled( 'groups', 'members' ) ?>/>
									<?php _e( 'Members', 'bp-default-data' ); ?>
								</label>
							</li>

							<?php if ( bp_is_active( 'activity' ) ) : ?>
								<li>
									<label for="import-g-activity">
										<input type="checkbox" name="bpdd[import-g-activity]" id="import-g-activity"
										       value="1" <?php bpdd_imported_disabled( 'groups', 'activity' ) ?>/>
										<?php _e( 'Activity posts', 'bp-default-data' ); ?>
									</label>
								</li>
							<?php endif; ?>

							<?php if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() ) : ?>
								<li>
									<label for="import-forums">
										<input type="checkbox" disabled name="bpdd[import-forums]" id="import-forums"
										       value="1" <?php bpdd_imported_disabled( 'groups', 'forums' ) ?>/>
										<?php _e( 'Forum topics and posts', 'bp-default-data' ); ?>
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
				<p><?php _e( 'All imported users have the same password: <code>1234567890</code>', 'bp-default-data' ); ?></p>
				<p><?php _e( 'xProfile data importing doesn\'t produce activity records.', 'bp-default-data' ); ?></p>
			</fieldset>

			<p class="description">
				Many thanks to <a href="http://imdb.com" target="_blank">IMDB.com</a> for movies titles (groups names), <a href="https://en.wikipedia.org"
				                                                                                                           target="_blank">Wikipedia.org</a>
				(users names), <a href="https://en.wikipedia.org/wiki/Lorem_ipsum" target="_blank">Lorem Ipsum</a> (messages and forum posts), <a
						href="http://www.cs.virginia.edu/~robins/quotes.html" target="_blank">Dr. Gabriel Robins</a> and <a
						href="http://en.proverbia.net/shortfamousquotes.asp" target="_blank">Proverbia</a> (lists of quotes), <a href="https://www.youtube.com/"
				                                                                                                                 target="_blank">YouTube</a> and
				<a href="https://vimeo.com/" target="_blank">Vimeo</a> (videos), <a href="https://8biticon.com/" target="_blank">8biticon.com</a> (avatars and
				plugin icon).
			</p>

			<?php wp_nonce_field( 'bpdd-admin' ); ?>

		</form>
		<!-- #bpdd-admin-form -->
	</div><!-- .wrap -->
	<?php
}
