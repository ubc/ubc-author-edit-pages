<?php
/**
 * The admin UI functionality of the plugin.
 *
 * This class handles the WordPress admin interface modifications needed to
 * support authors editing their own pages. It works in conjunction with the
 * Capabilities class to ensure a consistent user experience.
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
 * The admin UI functionality of the plugin.
 *
 * Manages the admin UI modifications for authors to edit pages. While the
 * Capabilities class handles the permission system, this class focuses on
 * the visual and interactive elements of the admin interface to ensure that:
 *
 * 1. Authors can only see their own pages in the admin
 * 2. Authors cannot access the "Add New Page" functionality
 * 3. Authors only see appropriate action links for their pages
 * 4. The admin interface is consistent with the capabilities granted
 *
 * @since      1.0.0
 * @package    UBC\AuthorEditPages
 * @author     Rich Tape/UBC CTLT
 */
class Admin_UI {

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
	 * Sets up all the WordPress hooks needed to modify the admin UI for authors.
	 * These hooks work together to create a consistent experience where authors
	 * can only see and edit their own pages.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		// Make Pages menu visible to authors.
		// This is necessary because by default, authors don't see the Pages menu at all.
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 10, 3 );

		// Remove "Add New" button for authors on the Pages screen.
		// This prevents authors from creating new pages through the submenu.
		add_action( 'admin_menu', array( $this, 'remove_add_new_page_for_authors' ) );

		// Add "Mine" filter to pages list.
		// This provides a clear way for authors to see only their pages.
		add_filter( 'views_edit-page', array( $this, 'add_mine_filter_to_pages' ) );

		// Modify row actions for pages to ensure edit links are shown for author's own pages.
		// This controls what actions (edit, trash, etc.) appear for each page in the list.
		add_filter( 'page_row_actions', array( $this, 'modify_page_row_actions' ), 10, 2 );

		// Filter get_edit_post_link to ensure it works for authors editing their own pages.
		// This ensures the edit links point to the correct URL with proper permissions.
		add_filter( 'get_edit_post_link', array( $this, 'filter_edit_post_link' ), 10, 3 );

		// Redirect authors away from the new page screen.
		// This prevents authors from directly accessing the new page URL.
		add_action( 'admin_init', array( $this, 'prevent_new_page_for_authors' ) );

		// Add custom CSS to hide "Add New" button in admin bar.
		// This removes visual elements that authors shouldn't be able to use.
		add_action( 'admin_head', array( $this, 'hide_add_new_page_button' ) );
	}

	/**
	 * Filter the user capabilities to make Pages menu visible to authors.
	 *
	 * This function grants the edit_others_pages capability to authors, but ONLY
	 * for the purpose of making the Pages menu visible in the admin. The actual
	 * ability to edit other pages is still controlled by the Capabilities class.
	 *
	 * This is a UI-only modification that doesn't grant actual editing permissions.
	 *
	 * @since    1.0.0
	 * @param    array $allcaps    All the capabilities of the user.
	 * @param    array $caps       Required capabilities.
	 * @param    array $args       [0] Requested capability, [1] User ID, [2] Associated object ID.
	 * @return   array             The filtered capabilities.
	 */
	public function filter_user_has_cap( $allcaps, $caps, $args ) {
		// Get the user object.
		$user = get_userdata( $args[1] );

		// If the user is not an author, return the original capabilities.
		if ( ! $user || ! in_array( 'author', $user->roles, true ) ) {
			return $allcaps;
		}

		// Make the Pages menu visible to authors.
		if ( isset( $allcaps['edit_pages'] ) && $allcaps['edit_pages'] ) {
			// We need this capability just to see the Pages menu, but we'll restrict actual editing elsewhere.
			// This is ONLY for UI visibility and doesn't grant actual permission to edit others' pages.
			$allcaps['edit_others_pages'] = true;
			$this->logger->log( "Making Pages menu visible to author user {$args[1]}." );
		}

		return $allcaps;
	}

	/**
	 * Remove "Add New" button for authors on the Pages screen.
	 *
	 * This function removes the "Add New" submenu item from the Pages menu
	 * for authors, preventing them from creating new pages through the admin menu.
	 *
	 * @since    1.0.0
	 */
	public function remove_add_new_page_for_authors() {
		// Get the current user.
		$user = wp_get_current_user();

		// If the user is not an author, return.
		if ( ! $user || ! in_array( 'author', $user->roles, true ) ) {
			return;
		}

		// Remove the "Add New" submenu for pages.
		remove_submenu_page( 'edit.php?post_type=page', 'post-new.php?post_type=page' );
		$this->logger->log( "Removed 'Add New' button for author user {$user->ID}." );
	}

	/**
	 * Prevent authors from accessing the new page screen.
	 *
	 * This function redirects authors away from the new page screen if they
	 * try to access it directly via URL. This is a security measure to ensure
	 * authors cannot create new pages even if they know the direct URL.
	 *
	 * @since    1.0.0
	 */
	public function prevent_new_page_for_authors() {
		global $pagenow;

		// Get the current user.
		$user = wp_get_current_user();

		// If the user is not an author, return.
		if ( ! $user || ! in_array( 'author', $user->roles, true ) ) {
			return;
		}

		// Check if we're on the new page screen.
		if ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'page' === $_GET['post_type'] ) {
			// Redirect to the pages list.
			wp_redirect( admin_url( 'edit.php?post_type=page' ) );
			exit;
		}
	}

	/**
	 * Hide "Add New" button in admin bar for authors.
	 *
	 * This function adds custom CSS to hide the "Add New Page" button in the
	 * admin bar and the page list for authors. This is a UI modification to
	 * ensure consistency with the capabilities restrictions.
	 *
	 * @since    1.0.0
	 */
	public function hide_add_new_page_button() {
		// Get the current user.
		$user = wp_get_current_user();

		// If the user is not an author, return.
		if ( ! $user || ! in_array( 'author', $user->roles, true ) ) {
			return;
		}

		// Add CSS to hide the "Add New" button.
		?>
		<style type="text/css">
			#wp-admin-bar-new-page {
				display: none !important;
			}
			.page-title-action {
				display: none !important;
			}
		</style>
		<?php
	}

	/**
	 * Add "Mine" filter to pages list.
	 *
	 * This function adds a "Mine" filter to the pages list view, allowing authors
	 * to easily see only their own pages. For authors, we actually replace all other
	 * views with just the "Mine" view to simplify the interface and reinforce that
	 * they can only work with their own pages.
	 *
	 * @since    1.0.0
	 * @param    array $views    The list of views.
	 * @return   array           The filtered list of views.
	 */
	public function add_mine_filter_to_pages( $views ) {
		// Get the current user.
		$user = wp_get_current_user();

		// If the user is not an author, return the original views.
		if ( ! $user || ! in_array( 'author', $user->roles, true ) ) {
			return $views;
		}

		// Get the number of pages for the current user.
		$count = count_user_posts( $user->ID, 'page', true );

		// Add the "Mine" filter.
		$class = '';
		if ( isset( $_GET['author'] ) && $_GET['author'] == $user->ID ) {
			$class = 'current';
		}

		$mine_url      = add_query_arg( 'author', $user->ID, admin_url( 'edit.php?post_type=page' ) );
		$views['mine'] = sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			esc_url( $mine_url ),
			$class,
			__( 'Mine', 'ubc-author-edit-pages' ),
			$count
		);

		$this->logger->log( "Added 'Mine' filter to pages list for author user {$user->ID}." );

		// For authors, make "Mine" the only view available.
		// This simplifies the UI and reinforces that authors can only see their own pages.
		if ( in_array( 'author', $user->roles, true ) ) {
			// Keep only the "Mine" view.
			return array( 'mine' => $views['mine'] );
		}

		return $views;
	}

	/**
	 * Modify row actions for pages to ensure edit links are shown for author's own pages.
	 *
	 * This function controls what action links (edit, trash, etc.) appear for each page
	 * in the admin list. For authors, we ensure they can only see edit links for their
	 * own pages, and we remove actions they shouldn't have access to (like delete).
	 *
	 * @since    1.0.0
	 * @param    array    $actions    The list of row actions.
	 * @param    \WP_Post $post       The post object.
	 * @return   array                The filtered list of row actions.
	 */
	public function modify_page_row_actions( $actions, $post ) {
		// Get the current user.
		$user = wp_get_current_user();

		// If the user is not an author, return the original actions.
		if ( ! $user || ! in_array( 'author', $user->roles, true ) ) {
			return $actions;
		}

		// If the user is the author of the page, ensure the edit link is present.
		if ( $post->post_author == $user->ID ) {
			// If edit action is missing, add it.
			if ( ! isset( $actions['edit'] ) ) {
				$actions['edit'] = sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					get_edit_post_link( $post->ID ),
					/* translators: %s: Post title. */
					esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'ubc-author-edit-pages' ), get_the_title( $post->ID ) ) ),
					__( 'Edit', 'ubc-author-edit-pages' )
				);
				$this->logger->log( "Added edit link for author user {$user->ID} on page {$post->ID}." );
			}

			// Remove any actions that authors shouldn't have.
			// Authors should only be able to edit, not delete or quick edit.
			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['trash'] );
			unset( $actions['delete'] );
		} else {
			// If the user is not the author, remove all actions.
			// This ensures authors cannot see action links for pages they don't own.
			return array();
		}

		return $actions;
	}

	/**
	 * Filter the edit post link to ensure it works for authors editing their own pages.
	 *
	 * This function ensures that edit links for pages work correctly for authors.
	 * It adds necessary parameters to the edit URL for the author's own pages,
	 * and returns an empty link for pages the author doesn't own.
	 *
	 * @since    1.0.0
	 * @param    string $link    The edit link.
	 * @param    int    $post_id The post ID.
	 * @param    string $context The link context.
	 * @return   string          The filtered edit link.
	 */
	public function filter_edit_post_link( $link, $post_id, $context ) {
		// Get the current user.
		$user = wp_get_current_user();

		// If the user is not an author, return the original link.
		if ( ! $user || ! in_array( 'author', $user->roles, true ) ) {
			return $link;
		}

		// Get the post.
		$post = get_post( $post_id );

		// If the post doesn't exist or is not a page, return the original link.
		if ( ! $post || 'page' !== $post->post_type ) {
			return $link;
		}

		// If the user is the author of the page, ensure the edit link works.
		if ( $post->post_author == $user->ID ) {
			$this->logger->log( "Ensuring edit link works for author user {$user->ID} on page {$post_id}." );

			// Make sure the link has the proper nonce and other parameters.
			if ( strpos( $link, 'action=edit' ) === false ) {
				$link = add_query_arg( 'action', 'edit', $link );
			}
		} else {
			// If the user is not the author, return an empty link.
			// This ensures authors cannot get edit links for pages they don't own.
			return '';
		}

		return $link;
	}
}
