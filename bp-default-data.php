<?php
/**
* Plugin Name: BuddyPress Default Data
* Plugin URI:  http://ovirium.com
* Description: Plugin will create lots of users, groups, topics, activity items - useful for testing purpose.
* Author:      slaFFik
* Version:     1.0.3
* Author URI:  http://ovirium.com
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BPDD_VERSION', '1.0.3' );

add_action( 'bp_init', 'bpdd_init' );
function bpdd_init() {
    add_action( bp_core_admin_hook(), 'bpdd_admin_page', 99 );
}

function bpdd_admin_page() {
    if ( ! is_super_admin() )
        return;

    add_submenu_page(
        is_multisite()?'settings.php':'tools.php',
        __( 'BuddyPress Default Data', 'bpdd' ),
        __( 'BP Default Data', 'bpdd' ),
        'manage_options',
        'bpdd-setup',
        'bpdd_admin_page_content'
    );
}

function bpdd_admin_page_content() {
?>
    <div class="wrap">
    <?php screen_icon( 'buddypress' ); ?>

    <style type="text/css">
        ul li.users{border-bottom: 1px solid #EEEEEE;margin: 0 0 10px;padding: 5px 0}
        ul li.users ul,ul li.groups ul{margin:5px 0 0 20px}
        #message ul.results li{list-style:disc;margin-left:25px}
    </style>
    <h2><?php _e( 'BuddyPress Default Data', 'bpdd' ); ?> <sup>v<?php echo BPDD_VERSION ?></sup></h2>

    <?php
    if ( ! empty( $_POST['bpdd-admin-clear'] ) ){
        bpdd_clear_db();
        echo '<div id="message" class="updated fade"><p>' . __( 'Everything was deleted', 'bpdd' ) .'</p></div>';
    }

    if ( isset( $_POST['bpdd-admin-submit'] ) ) {
        // default values
        $users      = false;
        $profile    = false;
        $messages   = false;
        $activity   = false;
        $friends    = false;
        $groups     = false;
        $forums     = false;
        $g_activity = false;
        $g_members  = false;

        // Check nonce before we do anything
        check_admin_referer( 'bpdd-admin' );

        // Import users
        if ( isset( $_POST['bpdd']['import-users'] ) ) {
            $users             = bpdd_import_users();
            $imported['users'] = sprintf( __( '%s new users', 'bpdd' ), number_format_i18n( count( $users ) ) );

            if ( isset( $_POST['bpdd']['import-friends'] ) ) {
                $friends             = bpdd_import_users_friends();
                $imported['friends'] = sprintf( __( '%s friends connections', 'bpdd' ), number_format_i18n( $friends ) );
            }

            if ( isset( $_POST['bpdd']['import-profile'] ) ) {
                $profile             = bpdd_import_users_profile();
                $imported['profile'] = sprintf( __( '%s profile entries', 'bpdd' ), number_format_i18n( $profile ) );
            }

            if ( isset( $_POST['bpdd']['import-messages'] ) ) {
                $messages             = bpdd_import_users_messages();
                $imported['messages'] = sprintf( __( '%s private messages', 'bpdd' ), number_format_i18n( count( $messages ) ) );
            }

            if ( isset( $_POST['bpdd']['import-activity'] ) ) {
                $activity             = bpdd_import_users_activity();
                $imported['activity'] = sprintf( __( '%s personal activity items', 'bpdd' ), number_format_i18n( $activity ) );
            }
        }

        // Import groups
        if ( isset( $_POST['bpdd']['import-groups'] ) ) {
            $groups             = bpdd_import_groups( $users );
            $imported['groups'] = sprintf( __( '%s new groups', 'bpdd' ), number_format_i18n( count( $groups ) ) );

            if ( isset( $_POST['bpdd']['import-g-members'] ) ) {
                $g_members             = bpdd_import_groups_members( $groups );
                $imported['g_members'] = sprintf( __( '%s groups members (1 user can be in several groups)', 'bpdd' ), number_format_i18n( count( $g_members ) ) );
            }

            if ( isset( $_POST['bpdd']['import-forums'] ) ) {
                $forums             = bpdd_import_groups_forums( $groups );
                $imported['forums'] = sprintf( __( '%s groups forum topics', 'bpdd' ), number_format_i18n( count( $forums ) ) );
            }

            if ( isset( $_POST['bpdd']['import-g-activity'] ) ) {
                $g_activity             = bpdd_import_groups_activity();
                $imported['g_activity'] = sprintf( __( '%s groups activity items', 'bpdd' ), number_format_i18n( $g_activity ) );
            }
        }
    ?>

    <div id="message" class="updated fade">
        <p>
            <?php
            _e( 'Data was successfully imported', 'bpdd' );
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
        jQuery(document).ready( function() {
            jQuery('input#import-profile, input#import-friends, input#import-activity, input#import-messages').click( function() {
                if (jQuery(this).attr('checked') == 'checked')
                    jQuery('input#import-users').attr('checked', 'checked');
            });
            jQuery('input#import-users').click( function() {
                if (jQuery(this).attr('checked') != 'checked')
                    jQuery('input#import-profile, input#import-friends, input#import-activity, input#import-messages').removeAttr('checked');
            });

            jQuery('input#import-forums, input#import-g-members, input#import-g-activity').click( function() {
                if (jQuery(this).attr('checked') == 'checked')
                    jQuery('input#import-groups').attr('checked', 'checked');
            });
            jQuery('input#import-groups').click( function() {
                if (jQuery(this).attr('checked') != 'checked')
                    jQuery('input#import-forums, input#import-g-members, input#import-g-activity').removeAttr('checked');
            });

            jQuery("input#bpdd-admin-clear").click( function() {
                if ( confirm( '<?php echo esc_js( __( 'Are you sure you want to delete all users (except one with ID=1), groups, messages, activities, forum topics etc?', 'bpdd' ) ); ?>' ) )
                    return true;
                else
                    return false;
            });
        });
        </script>

        <p><?php _e( 'Please do not import users twice as this will cause lots of errors (believe me). Just do clearing.', 'bpdd' ); ?></p>
        <p><?php _e( 'Please do not mess importing users and their data with groups. Importing is rather heavy process, so please finish with members first and then work with groups.', 'bpdd' ); ?></p>

        <ul class="items">
            <li class="users">
                <label for="import-users"><input type="checkbox" name="bpdd[import-users]" id="import-users" value="1" /> &nbsp; <?php _e( 'Do you want to import users?', 'bpdd' ); ?></label>
                <ul>

                    <?php if ( bp_is_active( 'xprofile' ) ) : ?>
                        <li>
                            <label for="import-profile"><input type="checkbox" disabled name="bpdd[import-profile]" id="import-profile" value="1" /> &nbsp; <?php _e( 'Do you want to import users profile data (profile groups and fields will be created)?', 'bpdd' ); ?></label>
                        </li>
                    <?php endif; ?>

                    <?php if ( bp_is_active( 'friends' ) ) : ?>
                        <li>
                            <label for="import-friends"><input type="checkbox" name="bpdd[import-friends]" id="import-friends" value="1" /> &nbsp; <?php _e( 'Do you want to create some friends connections between imported users?', 'bpdd' ); ?></label>
                        </li>
                    <?php endif; ?>

                    <?php if ( bp_is_active( 'activity' ) ) : ?>
                        <li>
                            <label for="import-activity"><input type="checkbox" name="bpdd[import-activity]" id="import-activity" value="1" /> &nbsp; <?php _e( 'Do you want to import activity posts for users?', 'bpdd' ); ?></label>
                        </li>
                    <?php endif; ?>

                    <?php if ( bp_is_active( 'messages' ) ) : ?>
                        <li>
                            <label for="import-messages"><input type="checkbox" name="bpdd[import-messages]" id="import-messages" value="1" /> &nbsp; <?php _e( 'Do you want to import private messages between users?', 'bpdd' ); ?></label>
                        </li>
                    <?php endif; ?>

                </ul>
            </li>

            <?php if ( bp_is_active( 'groups' ) ) : ?>
                <li class="groups">
                    <label for="import-groups"><input type="checkbox" name="bpdd[import-groups]" id="import-groups" value="1" /> &nbsp; <?php _e( 'Do you want to import groups?', 'bpdd' ); ?></label>
                    <ul>

                        <li>
                            <label for="import-g-members"><input type="checkbox" name="bpdd[import-g-members]" id="import-g-members" value="1" /> &nbsp; <?php _e( 'Do you want to import group members? Import users before doing this.', 'bpdd' ); ?></label>
                        </li>

                        <?php if ( bp_is_active( 'activity' ) ) : ?>
                            <li>
                                <label for="import-g-activity"><input type="checkbox" name="bpdd[import-g-activity]" id="import-g-activity" value="1" /> &nbsp; <?php _e( 'Do you want to import group activity posts?', 'bpdd' ); ?></label>
                            </li>
                        <?php endif; ?>

                        <?php if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() ) : ?>
                            <li>
                                <label for="import-forums"><input type="checkbox" disabled name="bpdd[import-forums]" id="import-forums" value="1" /> &nbsp; <?php _e( 'Do you want to import groups\' forum topics and posts?', 'bpdd' ); ?></label>
                            </li>

                            <?php else: ?>
                                <li>
                                    <?php _e( 'You can\'t import anything forums-related, because Forum Component is not installed correctly. Please recheck your settings.', 'bpdd' ); ?>
                                </li>
                            <?php endif; ?>

                    </ul>
                </li>
            <?php endif; ?>

        </ul><!-- .items -->

        <p class="submit">
            <input class="button-primary" type="submit" name="bpdd-admin-submit" id="bpdd-admin-submit" value="<?php esc_attr_e( 'Import Selected Data', 'bpdd' ); ?>" />
            <input class="button" type="submit" name="bpdd-admin-clear" id="bpdd-admin-clear" value="<?php esc_attr_e( 'Clear BuddyPress Data', 'bpdd' ); ?>" />
        </p>

        <p><?php _e( 'All users have the same password: <code>1234567890</code>', 'bpdd' ); ?></p>
        <p><?php _e( 'Friends connections don\'t produce notifications, while messages importing do.', 'bpdd' ); ?></p>
        <p><?php _e( 'Many thanks to <a href="http://imdb.com" target="_blank">IMDB.com</a> for movies titles (groups names), <a href="http://en.wikipedia.org" target="_blank">Wikipedia.org</a> (users names), <a href="http://en.wikipedia.org/wiki/Lorem_ipsum" target="_blank">Lorem Ipsum</a> (messages and forum posts), <a href="http://www.cs.virginia.edu/~robins/quotes.html">Dr. Gabriel Robins</a> (for a list of quotes collected by him), <a href="http://www.youtube.com/">Youtube</a> and <a href="http://vimeo.com/">Vimeo</a> (for videos).', 'bpdd' ); ?></p>

        <?php wp_nonce_field( 'bpdd-admin' ); ?>

    </form><!-- #bpdd-admin-form -->
    </div><!-- .wrap -->
    <?php
}

/*
*  Importer engine - USERS
*/
function bpdd_import_users() {
    $users_data = array();
    $users      = array();

    require( dirname( __FILE__ ) . '/data/users.php' );
    global $wpdb;
    foreach ( $users_data as $user ) {
        $cur = wp_insert_user( array(
            'user_login'      => $user['login'],
            'user_pass'       => $user['pass'],
            'display_name'    => $user['display_name'],
            'user_email'      => $user['email'],
            'user_registered' => bpdd_get_random_date( 45, 1 ),
        )) ;
        $query[] = $wpdb->last_query;

        bp_update_user_meta( $cur, 'last_activity', bpdd_get_random_date( 5 ) );
        bp_update_user_meta( $cur, 'notification_messages_new_message', 'no' );
        $users[] = $cur;
    }

    return $users;
}

function bpdd_import_users_profile() {
    return true;
}

function bpdd_import_users_messages() {
    $messages = array();

    require( dirname( __FILE__ ) . '/data/messages.php' );

    // first level messages
    for ( $i = 0; $i < 33; $i++ ) {
        $messages[] = messages_new_message( array(
            'sender_id'  => bpdd_get_random_users_ids( 1, 'string' ),
            'recipients' => bpdd_get_random_users_ids( 1, 'array' ),
            'subject'    => $messages_subjects[array_rand( $messages_subjects )],
            'content'    => $messages_content[array_rand( $messages_content )],
            'date_sent'  => bpdd_get_random_date( 15, 5 ),
        ) );
    }

    for ( $i = 0; $i < 33; $i++ ) {
        $messages[] = messages_new_message( array(
            'sender_id'  => bpdd_get_random_users_ids( 1, 'string' ),
            'recipients' => bpdd_get_random_users_ids( 2, 'array' ),
            'subject'    => $messages_subjects[array_rand( $messages_subjects )],
            'content'    => $messages_content[array_rand( $messages_content )],
            'date_sent'  => bpdd_get_random_date( 13, 3 ),
        ) );
    }

    for ( $i = 0; $i < 33; $i++ ) {
        $messages[] = messages_new_message( array(
            'sender_id'  => bpdd_get_random_users_ids( 1, 'string' ),
            'recipients' => bpdd_get_random_users_ids( 3, 'array' ),
            'subject'    => $messages_subjects[array_rand( $messages_subjects )],
            'content'    => $messages_content[array_rand( $messages_content )],
            'date_sent'  => bpdd_get_random_date( 10 ),
        ) );
    }

    $messages[] = messages_new_message( array(
        'sender_id'  => bpdd_get_random_users_ids( 1, 'string' ),
        'recipients' => bpdd_get_random_users_ids( 5, 'array' ),
        'subject'    => $messages_subjects[array_rand( $messages_subjects )],
        'content'    => $messages_content[array_rand( $messages_content )],
        'date_sent'  => bpdd_get_random_date( 5 ),
    ) );

    return $messages;
}

function bpdd_import_users_activity() {
    $users = bpdd_get_random_users_ids( 0 );

    require( dirname( __FILE__ ) . '/data/activity.php' );

    for( $i = 0, $count = 0; $i < 75; $i++ ) {
        $user    = $users[array_rand( $users )];
        $content = $activity[array_rand( $activity )];

        if ( bp_activity_post_update( array(
            'user_id' => $user,
            'content' => $content,
            ) ) ) {
            $count++;
        }
    }

    return $count;
}

function bpdd_import_users_friends() {
    $users = bpdd_get_random_users_ids( 50 );

    for ( $con = 0, $i = 0; $i < 100; $i++ ){
        $user_one = $users[array_rand( $users )];
        $user_two = $users[array_rand( $users )];

        if ( BP_Friends_Friendship::check_is_friend( $user_one, $user_two ) == 'not_friends' ) {

            // make them friends
            if ( friends_add_friend( $user_one, $user_two, true ) )
                $con++;
        }
    }

    return $con;
}

/*
*  Importer engine - GROUPS
*/
function bpdd_import_groups( $users = false ) {
    $groups    = array();
    $group_ids = array();

    if ( empty( $users ) )
        $users = get_users();

    require( dirname( __FILE__ ) . '/data/groups.php' );

    foreach ( $groups as $group ) {
        $cur = groups_create_group( array(
            'creator_id'   => $users[array_rand( $users )]->ID,
            'name'         => $group['name'],
            'description'  => $group['description'],
            'slug'         => groups_check_slug( sanitize_title( esc_attr( $group['name'] ) ) ),
            'status'       => $group['status'],
            'date_created' => bpdd_get_random_date( 30,5 ),
            'enable_forum' => $group['enable_forum']
        ) );

        groups_update_groupmeta( $cur, 'total_member_count', 1 );
        groups_update_groupmeta( $cur, 'last_activity', bpdd_get_random_date( 10 ) );

        // create forums if Forum Component is active
        if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() )
            groups_new_group_forum( $cur, $group['name'], $group['description'] );

        $group_ids[] = $cur;
    }

    return $group_ids;
}

function bpdd_import_groups_activity() {
    $users  = bpdd_get_random_users_ids( 0 );
    $groups = bpdd_get_random_groups_ids( 0 );

    require( dirname( __FILE__ ) . '/data/activity.php' );

    for ( $i = 0, $count = 0; $i < 150; $i++ ) {
        $user_id  = $users[array_rand( $users )];
        $group_id = $groups[array_rand( $groups )];
        $content  = $activity[array_rand( $activity )];

        if ( ! groups_is_user_member( $user_id, $group_id ) )
            continue;

        if ( groups_post_update( array(
            'user_id'  => $user_id,
            'group_id' => $group_id,
            'content'  => $content,
        ) ) ) {
            $count++;
        }
    }

    return $count;
}

function bpdd_import_groups_members( $groups = false ) {
    $members = array();

    if( ! $groups )
        $groups = bpdd_get_random_groups_ids( 0 );

    $new_member = new BP_Groups_Member;
    foreach ( $groups as $group_id ) {
        $user_ids = bpdd_get_random_users_ids( rand( 2, 15 ) );

        foreach ( $user_ids as $user_id ) {
            if ( groups_is_user_member( $user_id, $group_id ) )
                continue;

            $time = bpdd_get_random_date( 25, 1 );

            $new_member->id            = false;
            $new_member->group_id      = $group_id;
            $new_member->user_id       = $user_id;
            $new_member->inviter_id    = 0;
            $new_member->is_admin      = 0;
            $new_member->user_title    = '';
            $new_member->date_modified = $time;
            $new_member->is_confirmed  = 1;

            // save data - finally
            if ( $new_member->save() ) {
                $group = new BP_Groups_Group( $group_id );

                // record this in activity streams
                $activity_id[] = groups_record_activity( array(
                    'action'        => apply_filters( 'groups_activity_joined_group', sprintf( __( '%1$s joined the group %2$s', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( bp_get_group_name( $group ) ) . '</a>' ) ),
                    'type'          => 'joined_group',
                    'item_id'       => $group_id,
                    'user_id'       => $user_id,
                    'recorded_time' => $time
                ) );

                // modify group meta
                groups_update_groupmeta( $group_id, 'total_member_count', (int) groups_get_groupmeta( $group_id, 'total_member_count') + 1 );
                groups_update_groupmeta( $group_id, 'last_activity', $time );

                do_action( 'groups_join_group', $group_id, $user_id );

                // I need to know how many users were added to display in report after the import
                $members[] = $group_id;
            }
        }
    }

    return $members;
}

function bpdd_import_groups_forums() {
    return true;
}

/*
*  Helpers
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

    if ( bp_is_active( 'friends' ) )
        $sqls[] = "TRUNCATE TABLE {$prefix}bp_friends;";

    if ( bp_is_active( 'xprofile' ) )
        $sqls[] = "DELETE FROM {$prefix}bp_xprofile_data WHERE user_id > 1;";

    if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() ) {
        $sqls[] = "TRUNCATE TABLE {$prefix}bb_posts;";
        $sqls[] = "DELETE FROM {$prefix}bb_forums WHERE forum_id > 1;";
    }

    $sqls[] = "TRUNCATE TABLE {$prefix}bp_notifications;";
    $sqls[] = "DELETE FROM {$wpdb->prefix}users WHERE ID > 1;";
    $sqls[] = "DELETE FROM {$wpdb->prefix}usermeta WHERE user_id > 1;";
    $sqls[] = "DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key = 'total_friend_count';";

    foreach( $sqls as $sql )
        $wpdb->query( $sql );
}

function bpdd_get_random_groups_ids( $count = 1, $output = 'array' ) {
    global $wpdb;
    $groups = array();
    $data   = array();
    $limit  = '';

    if( $count > 0 )
        $limit = ' LIMIT ' . $count;

    $groups = $wpdb->get_results($wpdb->prepare(
                            "SELECT id FROM {$wpdb->prefix}bp_groups ORDER BY rand() {$limit}",
                            false));

    // reformat the array
    foreach( $groups as $group ) {
        $data[] = $group->id;
    }

    if ( $output == 'array' )
        return $data;
    elseif ( $output == 'string' )
        return implode( ',', $data );
}

function bpdd_get_random_users_ids( $count = 1, $output = 'array' ) {
    global $wpdb;
    $users = array();
    $limit = '';

    if( $count > 0 )
        $limit = ' LIMIT ' . $count;

    $users = $wpdb->get_results($wpdb->prepare(
                        "SELECT ID FROM {$wpdb->users} ORDER BY rand() {$limit}",
                        false));

    // reformat the array
    foreach( $users as $user )
        $data[] = $user->ID;

    if ( $output == 'array' )
        return $data;
    elseif ( $output == 'string' )
        return implode( ',', $data );
}

function bpdd_get_random_date( $days_from = 30, $days_to = 0 ) {
    // 1 day in seconds is 86400
    $from = $days_from * rand( 10000, 99999 );

    // $days_from should always be less than $days_to
    if ( $days_to > $days_from )
        $days_to = $days_from - 1;

    $to        = $days_to * rand( 10000, 99999 );
    $date_from = time() - $from;
    $date_to   = time() - $to;

    return date( 'Y-m-d H:i:s', rand( $date_from, $date_to ) );
}
