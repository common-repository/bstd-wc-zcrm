<?php

namespace BCRM\BC_INVENTORY_CONNECTOR\Core\Util;

/**
 * Class handling plugin uninstallation.
 *
 * @since 1.0.0
 * @access private
 * @ignore
 */
final class Uninstallation
{
    /**
     * Reset object.
     *
     * @since 1.0.0
     * @var Reset
     */
    private $reset;

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     *
     */
    public function __construct()
    {
    }

    /**
     * Registers functionality through WordPress hooks.
     *
     * @since 1.0.0
     */
    public function register()
    {
        add_action(
            'bc_inventory_connector_uninstall',
            [$this, 'deleteTable']
        );
    }

    public function deleteTable()
    {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bc_inventory_connector");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bc_inventory_connector_log");
    }
}
