ECHO is on.
<?php
/* 
Plugin Name: Bossu Core Module
Description: Core foundation for Bossu Academy functionality.
Author: Bossu Academy
Version: 1.0
*/

// Custom hooks for modularity
do_action('bossu_init');

// Add Bossu Console menu (placeholder)
function bossu_add_console() {
    add_menu_page(
        'Bossu Console',        // Page title
        'Bossu Console',        // Menu title
        'manage_options',       // Capability
        'bossu-console',        // Menu slug
        function() {            // Callback (the page content)
            echo '<h1>Bossu Console Placeholder</h1>';
        }
    );
}
add_action('admin_menu', 'bossu_add_console');

// Hook for future skill plugins
function bossu_register_skill($slug) {
    // Future: Register courses from plugin
}
add_action('bossu_register_skill', 'bossu_register_skill');
