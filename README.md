# UBC Author Edit Pages

A WordPress plugin that allows users with the author role to edit pages where they have been assigned as the author, mimicking the behavior that authors already have for posts.

## Description

By default, WordPress authors can create, edit, and delete their own posts, but they cannot do the same with pages. This plugin extends the capabilities of users with the "author" role to allow them to edit (but not create or publish) pages where they have been designated as the author.

### Key Features

-   Authors can edit pages where they are marked as the page author
-   Authors cannot publish pages (only edit them)
-   Authors cannot create new pages
-   Authors cannot delete pages
-   Authors can only edit their own pages (pages where they are the author)
-   Works in a multisite environment
-   When the plugin is disabled, author capabilities revert to WordPress defaults

## Installation

1. Upload the `ubc-author-edit-pages` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. No configuration is needed - authors will now be able to edit pages where they are assigned as the author

## Usage

1. Create or edit a page and assign an author with the "author" role to it
2. The author will now be able to edit that page
3. The author will see the "Pages" menu in the admin dashboard
4. The author will only see pages where they are the author in the pages list
5. The author will not be able to create new pages, publish pages, or delete pages

## Technical Details

### How It Works

This plugin uses WordPress's capabilities system to modify what authors can do with pages. It works through two main mechanisms:

1. **Role Capabilities**: On activation, the plugin adds specific capabilities to the author role:

    - `edit_pages` - Basic capability to see and edit pages
    - `edit_published_pages` - Ability to edit pages that have been published
    - `publish_pages` - Ability to update existing pages

2. **Capability Filtering**: The plugin uses two WordPress filters to dynamically control permissions:

    - `map_meta_cap` - Controls object-specific permissions (can this user edit this specific page?)
    - `user_has_cap` - Controls general capabilities (what can this user do with pages?)

3. **Admin UI Modifications**: The plugin modifies the WordPress admin interface to:
    - Show only the author's own pages in the page list
    - Remove "Add New" buttons and links
    - Prevent access to the new page screen
    - Modify row actions to only show appropriate actions

### Security Considerations

The plugin implements multiple layers of security to ensure authors can only edit their own pages:

-   **Capability Checks**: Both general and object-specific capability checks are implemented
-   **UI Restrictions**: All UI elements for creating new pages are hidden
-   **Redirect Protection**: Direct access to the new page URL is blocked
-   **Query Filtering**: The pages list is filtered to only show the author's own pages

### WordPress Integration

The plugin integrates with WordPress in a non-intrusive way:

-   Uses standard WordPress hooks and filters
-   Follows WordPress coding standards
-   Properly cleans up on deactivation
-   Works with the block editor and classic editor
-   Compatible with multisite installations

## Requirements

-   WordPress 5.0 or higher
-   PHP 8.0 or higher

## Frequently Asked Questions

### Can authors create new pages?

No, authors can only edit existing pages where they have been assigned as the author.

### Can authors publish pages?

Authors can save changes to existing published pages, but they cannot publish new pages.

### Can authors delete pages?

No, authors cannot delete pages.

### Does this plugin work in a multisite environment?

Yes, the plugin works in a multisite environment. It can be activated on individual sites as needed.

### How does the plugin prevent authors from creating new pages?

The plugin uses multiple methods to prevent page creation:

1. It removes the capability to create pages
2. It hides the "Add New" button in the UI
3. It redirects authors away from the new page screen
4. It blocks the ability to publish auto-draft pages

### How does the plugin determine which pages an author can edit?

The plugin checks if the user's ID matches the post_author field of the page. If they match, the author is granted permission to edit that page.

## Changelog

### 1.0.0

-   Initial release

## Credits

Developed by Rich Tape/UBC CTLT
