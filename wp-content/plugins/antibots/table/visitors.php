<?php

/**
 * @author William Sergio Minossi
 * @copyright 2020
 */




if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
global $wpdb;
add_action('admin_menu', 'antibots_add_menu_items2');
// add_action('wp_head', 'antibots_ajaxurl');
function antibots_add_menu_items2()
{
  $antibots_table_page = add_submenu_page(
    'anti_bots_plugin', // $parent_slug
    'Visits Log', // string $page_title
    'Visits Log', // string $menu_title
    'manage_options', // string $capability
    'antibots_my-custom-submenu-page',
    'antibots_render_list_page'
  );
}
function antibots_render_list_page()
{
  require_once ANTIBOTSPATH . 'table/visitors_render.php';
}
