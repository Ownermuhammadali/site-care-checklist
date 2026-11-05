<?php
/**
 * Plugin Name:       Site Care Checklist
 * Plugin URI:        https://muhammadalinaseer.com
 * Description:       A lightweight dashboard widget with a site health checklist: updates, backup reminders, spam comments, post revision cleanup, and a broken link note.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Muhammad Ali Naseer
 * Author URI:        https://muhammadalinaseer.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       site-care-checklist
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SCC_VERSION', '1.0.0' );
define( 'SCC_OPTION_CHECKLIST', 'scc_checklist_items' );
define( 'SCC_OPTION_LAST_BACKUP', 'scc_last_backup_date' );

/**
 * Default checklist items. Keys are stable slugs, values are labels.
 *
 * @return array
 */
function scc_default_checklist() {
    return array(
        'updates'    => 'WordPress, themes and plugins are up to date',
        'backup'     => 'A recent backup exists and was tested',
        'spam'       => 'Spam comments have been reviewed',
        'revisions'  => 'Old post revisions have been cleaned up',
        'links'      => 'Broken links have been checked',
        'security'   => 'Login and security settings were reviewed',
    );
}

/**
 * Get the saved checklist state, merged over the defaults.
 *
 * @return array
 */
function scc_get_checklist() {
    $saved    = get_option( SCC_OPTION_CHECKLIST, array() );
    $defaults = scc_default_checklist();
    $state    = array();

    if ( ! is_array( $saved ) ) {
        $saved = array();
    }

    foreach ( $defaults as $key => $label ) {
        $state[ $key ] = ! empty( $saved[ $key ] );
    }

    return $state;
}

/**
 * Count post revisions stored in the database.
 *
 * @return int
 */
function scc_count_revisions() {
    global $wpdb;
    $count = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_status = 'inherit'"
    );
    return $count;
}

/**
 * Register the dashboard widget.
 */
function scc_register_dashboard_widget() {
    wp_add_dashboard_widget(
        'scc_site_care_widget',
        'Site Care Checklist',
        'scc_render_dashboard_widget'
    );
}
add_action( 'wp_dashboard_setup', 'scc_register_dashboard_widget' );

/**
 * Render the dashboard widget.
 */
function scc_render_dashboard_widget() {
    $checklist   = scc_get_checklist();
    $labels      = scc_default_checklist();
    $last_backup = get_option( SCC_OPTION_LAST_BACKUP, '' );
    $spam_count  = 0;
    $update_data = array();

    if ( function_exists( 'wp_get_update_data' ) ) {
        $update_data = wp_get_update_data();
    }

    $spam = wp_count_comments();
    if ( isset( $spam->spam ) ) {
        $spam_count = (int) $spam->spam;
    }

    $revision_count = scc_count_revisions();
    $core_update    = ! empty( $update_data['counts']['total'] ) ? (int) $update_data['counts']['total'] : 0;

    echo '<p>Quick site health check. Tick an item when it is done, then save.</p>';

    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    wp_nonce_field( 'scc_save_checklist', 'scc_nonce' );
    echo '<input type="hidden" name="action" value="scc_save_checklist" />';
    echo '<ul style="list-style:none;margin:0;padding:0;">';

    foreach ( $labels as $key => $label ) {
        $checked = ! empty( $checklist[ $key ] ) ? ' checked="checked"' : '';
        echo '<li style="margin:6px 0;"><label><input type="checkbox" name="scc_items[]" value="' . esc_attr( $key ) . '"' . $checked . ' /> ' . esc_html( $label ) . '</label></li>';
    }

    echo '</ul>';
    submit_button( 'Save checklist', 'secondary', 'submit', false );
    echo '</form>';

    echo '<hr />';
    echo '<ul>';
    echo '<li>Updates waiting: <strong>' . esc_html( $core_update ) . '</strong> (<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">review updates</a>)</li>';
    echo '<li>Spam comments: <strong>' . esc_html( $spam_count ) . '</strong> (<a href="' . esc_url( admin_url( 'edit-comments.php?comment_status=spam' ) ) . '">review spam</a>)</li>';
    echo '<li>Post revisions stored: <strong>' . esc_html( $revision_count ) . '</strong></li>';

    if ( $last_backup ) {
        echo '<li>Last backup marked done: <strong>' . esc_html( $last_backup ) . '</strong></li>';
    } else {
        echo '<li>Last backup: <strong>not recorded yet</strong>. Run a backup, then mark it done below.</li>';
    }

    echo '</ul>';

    echo '<p><strong>Broken links:</strong> this plugin does not scan links automatically. Open your site, check the main menu, footer and contact page, and tick the broken links item above when they all work.</p>';

    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:8px;">';
    wp_nonce_field( 'scc_cleanup_revisions', 'scc_nonce' );
    echo '<input type="hidden" name="action" value="scc_cleanup_revisions" />';
    submit_button( 'Clean up revisions', 'secondary', 'submit', false );
    echo '</form>';

    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;">';
    wp_nonce_field( 'scc_mark_backup', 'scc_nonce' );
    echo '<input type="hidden" name="action" value="scc_mark_backup" />';
    submit_button( 'Mark backup done today', 'secondary', 'submit', false );
    echo '</form>';
}

/**
 * Save the checklist state.
 */
function scc_handle_save_checklist() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to do this.' );
    }

    check_admin_referer( 'scc_save_checklist', 'scc_nonce' );

    $labels = scc_default_checklist();
    $posted = isset( $_POST['scc_items'] ) && is_array( $_POST['scc_items'] ) ? $_POST['scc_items'] : array();
    $state  = array();

    foreach ( $labels as $key => $label ) {
        $state[ $key ] = in_array( $key, $posted, true );
    }

    update_option( SCC_OPTION_CHECKLIST, $state );
    wp_safe_redirect( admin_url( 'index.php' ) );
    exit;
}
add_action( 'admin_post_scc_save_checklist', 'scc_handle_save_checklist' );

/**
 * Delete all post revisions.
 */
function scc_handle_cleanup_revisions() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to do this.' );
    }

    check_admin_referer( 'scc_cleanup_revisions', 'scc_nonce' );

    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_status = 'inherit'"
    );

    wp_safe_redirect( admin_url( 'index.php' ) );
    exit;
}
add_action( 'admin_post_scc_cleanup_revisions', 'scc_handle_cleanup_revisions' );

/**
 * Record today as the last backup date.
 */
function scc_handle_mark_backup() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to do this.' );
    }

    check_admin_referer( 'scc_mark_backup', 'scc_nonce' );

    update_option( SCC_OPTION_LAST_BACKUP, current_time( 'Y-m-d' ) );
    wp_safe_redirect( admin_url( 'index.php' ) );
    exit;
}
add_action( 'admin_post_scc_mark_backup', 'scc_handle_mark_backup' );
