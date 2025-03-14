<?php
/**
 * The capabilities functionality of the plugin.
 *
 * This class manages the WordPress capabilities system for authors editing pages.
 * It handles adding and removing capabilities on plugin activation/deactivation,
 * and filters the capabilities checks to allow authors to edit only their own pages.
 *
 * @since      1.0.0
 * @package    UBC\AuthorEditPages
 */

namespace UBC\AuthorEditPages;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The capabilities functionality of the plugin.
 *
 * Manages the capabilities for authors to edit pages. WordPress uses a complex
 * capability system that involves both role capabilities and meta capabilities.
 * This class hooks into that system to modify the behavior for authors.
 *
 * Key concepts:
 * - Role capabilities: These are stored in the database and assigned to roles
 * - Meta capabilities: These are mapped to primitive capabilities during runtime
 * - Capability filtering: WordPress provides filters to modify capability checks
 *
 * @since      1.0.0
 * @package    UBC\AuthorEditPages
 * @author     Rich Tape/UBC CTLT
 */
class Capabilities {

	/**
	 * The logger instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Logger    $logger    The logger instance.
	 */
	protected $logger;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    Logger $logger    The logger instance.
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Register the hooks for this class.
	 *
	 * Sets up the WordPress filters that will be used to modify capabilities
	 * and control what pages authors can see and edit.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		// Add filter to allow authors to edit their own pages.
		// The map_meta_cap filter is used by WordPress to convert meta capabilities
		// (like edit_post) to primitive capabilities (like edit_posts).
		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 10, 4 );

		// Add filter to modify author capabilities.
		// The user_has_cap filter allows us to dynamically modify what capabilities
		// a user has at runtime, regardless of their role's default capabilities.
		add_filter( 'user_has_cap', array( $this, 'filter_author_capabilities' ), 10, 4 );

		// Filter the pages list to only show the author's own pages.
		// This ensures authors only see pages they are allowed to edit in the admin.
		add_filter( 'pre_get_posts', array( $this, 'filter_pages_for_author' ) );
	}

	/**
	 * Activate the plugin.
	 *
	 * Add the necessary capabilities to the author role when the plugin is activated.
	 * This grants authors the basic capabilities needed to access and edit pages.
	 *
	 * @since    1.0.0
	 */
	public function activate() {
		$this->logger->info( 'Activating plugin...', true );

		// Get the author role.
		$role = get_role( 'author' );

		// Add the necessary capabilities to the author role.
		if ( $role ) {
			// Basic capability to see the Pages menu.
			// Without this, authors won't even see the Pages menu in the admin.
			$role->add_cap( 'edit_pages', true );

			// Add capability to edit published pages (needed for their own pages).
			// This is required for authors to edit pages that have already been published.
			$role->add_cap( 'edit_published_pages', true );

			// Add capability to publish pages (needed to update their own pages).
			// This allows authors to save changes to their pages.
			$role->add_cap( 'publish_pages', true );

			$this->logger->info( 'Added page editing capabilities to author role.', true );
		} else {
			$this->logger->error( 'Author role not found.' );
		}
	}

	/**
	 * Deactivate the plugin.
	 *
	 * Remove the capabilities from the author role when the plugin is deactivated.
	 * This restores the default WordPress behavior where authors cannot edit pages.
	 *
	 * @since    1.0.0
	 */
	public function deactivate() {
		$this->logger->info( 'Deactivating plugin...', true );

		// Get the author role.
		$role = get_role( 'author' );

		// Remove the capabilities from the author role.
		if ( $role ) {
			$role->remove_cap( 'edit_pages' );
			$role->remove_cap( 'edit_published_pages' );
			$role->remove_cap( 'publish_pages' );

			$this->logger->info( 'Removed page editing capabilities from author role.', true );
		} else {
			$this->logger->error( 'Author role not found.' );
		}
	}

	/**
	 * Filter the map_meta_cap to allow authors to edit their own pages.
	 *
	 * This is one of the two main capability filtering functions in our plugin.
	 * The map_meta_cap filter is called whenever WordPress needs to check if a user
	 * can perform a specific action on a specific object (like editing a page).
	 *
	 * WordPress uses this filter to convert "meta capabilities" (like edit_post)
	 * into "primitive capabilities" (like edit_posts) that are actually stored
	 * in the database and assigned to roles.
	 *
	 * Our implementation:
	 * 1. We check if the user is an author
	 * 2. We check if the capability being checked is related to editing pages
	 * 3. We check if the page exists
	 * 4. If the user is the author of the page, we grant access by returning an empty array
	 * 5. If the user is not the author of the page, we deny access with 'do_not_allow'
	 *
	 * @since    1.0.0
	 * @param    array  $caps       The primitive capabilities that are required to perform the requested meta capability.
	 * @param    string $cap        The meta capability being checked (e.g., edit_post).
	 * @param    int    $user_id    The user ID.
	 * @param    array  $args       Additional arguments passed to the capability check, typically includes the object ID.
	 * @return   array              The filtered primitive capabilities required.
	 */
	public function filter_map_meta_cap( $caps, $cap, $user_id, $args ) {
		// Get the user object.
		$user = get_userdata( $user_id );

		// If the user is not an author, return the original capabilities.
		// This ensures we only modify behavior for authors.
		if ( ! $user || ! in_array( 'author', $user->roles, true ) ) {
			return $caps;
		}

		// List of capabilities we want to handle for authors.
		// These are meta capabilities related to editing pages.
		$page_caps = array(
			'edit_page',             // Direct edit capability for a page.
			'edit_post',             // Generic edit capability (works for pages too).
			'publish_page',          // Ability to publish a specific page.
			'publish_pages',         // Ability to publish pages in general.
			'edit_published_pages',  // Ability to edit already published pages.
			'edit_published_page',   // Ability to edit a specific published page.
		);

		// Handle capabilities for pages.
		// We only want to modify the behavior if:
		// 1. The capability being checked is in our list of page-related capabilities
		// 2. We have an object ID to check against (the page ID).
		if ( in_array( $cap, $page_caps, true ) && isset( $args[0] ) ) {
			$page_id = $args[0];
			$page    = get_post( $page_id );

			// If the page doesn't exist, return the original capabilities.
			// This is a safety check to prevent errors.
			if ( ! $page ) {
				return $caps;
			}

			// If it's a page and the user is the author, allow them to edit and publish it.
			// This is the core of our plugin's functionality - authors can edit their own pages.
			if ( 'page' === $page->post_type && $page->post_author == $user_id ) {
				$this->logger->info( "User $user_id is the author of page $page_id - granting capabilities.", true );

				// For authors of their own pages, grant all necessary capabilities.
				// Return an empty array to indicate no additional capabilities are needed.
				// This effectively grants the user the ability to perform the action.
				return array();
			} elseif ( 'page' === $page->post_type ) {
				// If it's a page but the user is not the author, explicitly deny access.
				// This ensures authors cannot edit pages they don't own.
				$this->logger->info( "User $user_id is NOT the author of page $page_id - denying capabilities.", true );

				// 'do_not_allow' is a special capability that no one has, effectively denying access.
				return array( 'do_not_allow' );
			}
		}

		// For all other cases, return the original capabilities.
		// This ensures we don't interfere with other capability checks.
		return $caps;
	}

	/**
	 * Filter author capabilities to control page editing.
	 *
	 * This is the second main capability filtering function in our plugin.
	 * While map_meta_cap handles specific object-level permissions (can this user edit this page?),
	 * user_has_cap handles general capability checks (does this user have this capability?).
	 *
	 * Our implementation:
	 * 1. We check if the user is an author
	 * 2. We grant basic page editing capabilities to all authors
	 * 3. If checking a specific page, we grant full capabilities if the user is the author
	 * 4. We explicitly deny capabilities for creating new pages and editing pages by other authors
	 * 5. We prevent authors from deleting pages
	 *
	 * The difference between this and map_meta_cap:
	 * - map_meta_cap is called when checking if a user can perform a specific action on a specific object
	 * - user_has_cap is called when checking if a user has a specific capability in general
	 * - We need both to fully control the capabilities system
	 *
	 * @since    1.0.0
	 * @param    array  $allcaps    All the capabilities of the user, including those from roles and filters.
	 * @param    array  $caps       Required primitive capabilities being checked.
	 * @param    array  $args       [0] Requested capability, [1] User ID, [2] Associated object ID (optional).
	 * @param    object $user       The user object (optional, will be retrieved from args if not provided).
	 * @return   array              The filtered capabilities of the user.
	 */
	public function filter_author_capabilities( $allcaps, $caps, $args, $user = null ) {
		// If $user is not provided, get the user from args.
		// This ensures we always have a user object to work with.
		if ( null === $user && isset( $args[1] ) ) {
			$user = get_userdata( $args[1] );
		}

		// If the user is not an author, return the original capabilities.
		// This ensures we only modify behavior for authors.
		if ( ! $user || ! in_array( 'author', (array) $user->roles, true ) ) {
			return $allcaps;
		}

		// Grant basic edit_pages capability to authors.
		// This allows authors to see the Pages menu in the admin.
		$allcaps['edit_pages'] = true;

		// Grant publish_pages capability to authors (needed for the Update button).
		// This allows authors to save changes to their pages.
		$allcaps['publish_pages'] = true;

		// Grant edit_published_pages capability to authors (needed to edit after publishing).
		// This allows authors to edit pages that have already been published.
		$allcaps['edit_published_pages'] = true;

		// If this is a specific page check, see if the user is the author.
		// args[2] contains the object ID (page ID) if this is a check for a specific page.
		if ( isset( $args[2] ) ) {
			$page_id = $args[2];
			$page    = get_post( $page_id );

			// If it's a page and the user is the author, grant all necessary capabilities.
			if ( $page && 'page' === $page->post_type && $page->post_author == $user->ID ) {
				// Grant all necessary capabilities for authors to edit and publish their own pages.
				// These capabilities are needed for various aspects of the editing interface.
				$allcaps['edit_published_pages'] = true;  // Edit pages that are already published.
				$allcaps['edit_published_page']  = true;  // Edit a specific published page.
				$allcaps['publish_pages']        = true;  // Publish pages in general.
				$allcaps['publish_page']         = true;  // Publish a specific page.
				$allcaps['edit_page']            = true;  // Edit a specific page.

				$this->logger->info( "Granting full edit and publish capabilities for author {$user->ID} on their page $page_id.", true );
			} elseif ( $page && 'page' === $page->post_type ) {
				// Explicitly deny capabilities for pages the user is not the author of.
				// This ensures authors cannot edit pages they don't own.
				$allcaps['edit_page']           = false;  // Cannot edit this specific page.
				$allcaps['edit_published_page'] = false;  // Cannot edit this specific published page.
				$allcaps['publish_page']        = false;  // Cannot publish this specific page.

				$this->logger->info( "Denying capabilities for author {$user->ID} on page $page_id (not the author).", true );
			}
		}

		// Prevent authors from creating new pages.
		// These capabilities control the ability to create and edit pages.
		$allcaps['create_pages']      = false;  // Cannot create new pages.
		$allcaps['edit_others_pages'] = false;  // Cannot edit pages created by others.

		// Explicitly prevent the capability to add new pages.
		// We need to check POST data, but this is a capability check, not form processing.
		// This specifically targets the case where an author tries to create a new page.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $args[0] ) && 'publish_pages' === $args[0] &&
			isset( $_POST['action'] ) && 'editpost' === $_POST['action'] &&
			isset( $_POST['original_post_status'] ) && 'auto-draft' === $_POST['original_post_status'] ) {
			// If this is a request to publish a new page (auto-draft), deny it.
			$allcaps['publish_pages'] = false;
			$this->logger->info( "Preventing author {$user->ID} from creating a new page.", true );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Prevent authors from deleting pages.
		// These capabilities control the ability to delete pages.
		$allcaps['delete_page']  = false;  // Cannot delete a specific page.
		$allcaps['delete_pages'] = false;  // Cannot delete pages in general.

		// Return the modified capabilities.
		return $allcaps;
	}

	/**
	 * Filter the pages list to only show the author's own pages.
	 *
	 * This function ensures that when an author views the Pages list in the admin,
	 * they only see pages where they are the author. This provides a cleaner UI
	 * and reinforces the restriction that authors can only edit their own pages.
	 *
	 * @since    1.0.0
	 * @param    \WP_Query $query    The WP_Query instance being filtered.
	 * @return   \WP_Query           The filtered query.
	 */
	public function filter_pages_for_author( $query ) {
		global $pagenow;

		// Only apply this filter in the admin area for the pages list.
		// We only want to modify the query on the edit.php page in the admin.
		if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
			return $query;
		}

		// Only apply to page post type.
		// We only want to modify queries for pages, not other post types.
		if ( isset( $_GET['post_type'] ) && 'page' === $_GET['post_type'] ) {
			// Get the current user.
			$user = wp_get_current_user();

			// If the user is an author, restrict to their own pages.
			// This ensures authors only see pages they are allowed to edit.
			if ( in_array( 'author', (array) $user->roles, true ) ) {
				// Only modify if not already filtered by author.
				// This prevents overriding an explicit author filter.
				if ( ! isset( $_GET['author'] ) ) {
					// Set the author parameter to the current user's ID.
					// This restricts the query to only show pages by this author.
					$query->set( 'author', $user->ID );
					$this->logger->info( "Filtering pages list to show only pages authored by user {$user->ID}.", true );
				}
			}
		}

		// Return the modified query.
		return $query;
	}
}
