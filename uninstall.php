<?php
/**
 * Uninstall cleanup for Site Care Checklist.
 * Deletes the plugin options created by the plugin.
 * This file is only run by WordPress when the plugin is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'scc_checklist_items' );
delete_option( 'scc_last_backup_date' );
