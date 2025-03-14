<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    UBC\AuthorEditPages
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove the edit_pages capability from the author role.
$author_role = get_role( 'author' );
if ( $author_role ) {
	$author_role->remove_cap( 'edit_pages' );
}

// If this is a multisite installation, remove the capability from all sites.
if ( is_multisite() ) {
	$sites = get_sites();
	foreach ( $sites as $site ) {
		switch_to_blog( $site->blog_id );
		$site_author_role = get_role( 'author' );
		if ( $site_author_role ) {
			$site_author_role->remove_cap( 'edit_pages' );
		}
		restore_current_blog();
	}
}
