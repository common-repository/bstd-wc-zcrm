<?php

use BCRM\BC_INVENTORY_CONNECTOR\Plugin;

/**
 * Plugin Name:  Inventory Connector for Zoho and WooCommerce
 * Plugin URI:http://boostedcrm.com/
 * Description: Inventory Connector for Zoho and WooCommerce plugin by Boosted CRM
 * Version:     1.0
 * Author:      Boosted CRM
 * WC tested up to: 6.2
 * Author URI:  https://boostedcrm.com/
 * Text Domain: bc_inventory_connector
 * Domain Path: /languages
 * License: GPLv2 or later
 */

/***
 *
 *If try to direct access  plugin folder it will Exit
 *
 **/
if (!defined('ABSPATH')) {
    exit;
}

// Define most essential constants.
define('BC_INVENTORY_CONNECTOR_VERSION', '1.0');
define('BC_INVENTORY_CONNECTOR_PLUGIN_MAIN_FILE', __FILE__);
define('BC_INVENTORY_CONNECTOR_PLUGIN_BASENAME', plugin_basename(BC_INVENTORY_CONNECTOR_PLUGIN_MAIN_FILE));
define('BC_INVENTORY_CONNECTOR_PLUGIN_DIR_PATH', plugin_dir_path(BC_INVENTORY_CONNECTOR_PLUGIN_MAIN_FILE));
define('BC_INVENTORY_CONNECTOR_ROOT_URI', plugins_url('', BC_INVENTORY_CONNECTOR_PLUGIN_MAIN_FILE));
define('BC_INVENTORY_CONNECTOR_ASSET_URI', BC_INVENTORY_CONNECTOR_ROOT_URI . '/assets');

/**
 * Handles plugin activation.
 *
 * Throws an error if the plugin is activated on an older version than PHP 5.4.
 *
 * @access private
 *
 * @param bool $network_wide Whether to activate network-wide.
 */
function bc_inventory_connector_activate_plugin($network_wide)
{
    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
        wp_die(
            esc_html__('bc_inventory_connector requires PHP version 5.4.', 'bc_inventory_connector'),
            esc_html__('Error Activating', 'bc_inventory_connector')
        );
    }

    if ($network_wide) {
        return;
    }

    do_action('bc_inventory_connector_activation', $network_wide);
}

register_activation_hook(__FILE__, 'bc_inventory_connector_activate_plugin');

/**
 * Handles plugin deactivation.
 *
 * @access private
 *
 * @param bool $network_wide Whether to deactivate network-wide.
 */
function bc_inventory_connector_deactivate_plugin($network_wide)
{
    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
        return;
    }

    if ($network_wide) {
        return;
    }

    do_action('bc_inventory_connector_deactivation', $network_wide);
}

register_deactivation_hook(__FILE__, 'bc_inventory_connector_deactivate_plugin');

/**
 * Handles plugin uninstall.
 *
 * @access private
 */
function bc_inventory_connector_uninstall_plugin()
{
    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
        return;
    }

    do_action('bc_inventory_connector_uninstall');
}
register_uninstall_hook(__FILE__, 'bc_inventory_connector_uninstall_plugin');

if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
    // Autoload vendor files.
    require_once BC_INVENTORY_CONNECTOR_PLUGIN_DIR_PATH . 'vendor/autoload.php';

    // Initialize the plugin.
    Plugin::load(BC_INVENTORY_CONNECTOR_PLUGIN_MAIN_FILE);
}
