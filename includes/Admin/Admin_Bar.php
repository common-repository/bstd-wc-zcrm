<?php

namespace BCRM\BC_INVENTORY_CONNECTOR\Admin;

/**
 * The admin menu and page handler class
 */
class Admin_Bar
{
    public function register()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        /*  */
    }

    /**
     * Register the admin menu
     *
     * @return void
     */
    public function register_admin_menu()
    {
        $capability = apply_filters('bc_inventory_connector_form_access_capability', 'manage_options');

        $hook = add_menu_page(__('Inventory Connector for Zoho and WooCommerce - The Best Plugin for Integrating with Zoho Inventory with WooCommerce', 'Inventory Connector for Zoho and WooCommerce'), 'Inventory Connector for Zoho and WooCommerce', $capability, 'bc_inventory_connector', [$this, 'table_home_page'], 'data:image/svg+xml;base64,' . base64_encode('<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><defs><style>.cls-1{fill:#fff;}</style></defs><rect width="256" height="256" rx="48.79"/><rect class="cls-1" x="177.29" y="64.43" width="35.88" height="28.05"/><path class="cls-1" d="M177.29,136.67a20.83,20.83,0,0,1-5.21,14.05,13.72,13.72,0,0,1-21.14,0A20.28,20.28,0,0,1,146,140.27c.07-1.19.11-2.39.11-3.6V135H109.86v1.66c0,1.21,0,2.41.11,3.6a20.3,20.3,0,0,1-4.92,10.45,13.71,13.71,0,0,1-21.13,0,20.78,20.78,0,0,1-5.21-14.05V64.43H42.83v72.24a56.6,56.6,0,0,0,14.6,38.15A49.25,49.25,0,0,0,128,178.36a49.25,49.25,0,0,0,70.57-3.54,56.6,56.6,0,0,0,14.6-38.15V108H177.29Z"/><path class="cls-1" d="M130.59,105.3c-1.88,0-4,.85-4,2.94s2.08,3,4.09,3c2.34,0,4.1-1.15,4.1-3S133,105.3,130.59,105.3Z"/><path class="cls-1" d="M130.59,77.1c-1.88,0-4,.84-4,2.93s2.08,3,4.09,3c2.34,0,4.1-1.15,4.1-3S133,77.1,130.59,77.1Z"/><path class="cls-1" d="M146.14,64.43H109.86V135h36.28ZM119.05,96.56h7.2v0a4.41,4.41,0,0,1-1.94-3.61c0-2.06,1.37-4.22,5.24-4.22h7.13v3.06H129.9c-1.76,0-3.08.64-3.08,2.33a2.49,2.49,0,0,0,1.71,2.36,2.57,2.57,0,0,0,.92.12h7.23v3.08H119.05Zm17.63,31.86H135l-12.44-8.56h-.08v7.79h-2.53V115.88h1.79l12.32,8.45h.1v-8.57h2.53Zm-6-14c-4,0-6.41-2.61-6.41-6.26s2.53-6,6.21-6c4.45,0,6.43,3.13,6.43,6.21A5.85,5.85,0,0,1,130.72,114.42Zm0-28.21c-4,0-6.41-2.61-6.41-6.26s2.53-6,6.21-6c4.45,0,6.43,3.13,6.43,6.21A5.85,5.85,0,0,1,130.72,86.21Z"/></svg>'), 56);

        add_action('load-' . $hook, [$this, 'load_assets']);
    }

    /**
     * Load the asset libraries
     *
     * @return void
     */
    public function load_assets()
    {
        /*  require_once dirname( __FILE__ ) . '/class-form-builder-assets.php';
        new BC_INVENTORY_CONNECTOR_Form_Builder_Assets(); */
    }

    /**
     * The contact form page handler
     *
     * @return void
     */
    public function table_home_page()
    {
        require_once BC_INVENTORY_CONNECTOR_PLUGIN_DIR_PATH . '/views/view-root.php';
        global $wp_rewrite;
        $api = [
            'base'      => get_rest_url() . 'bcwcinventoryzoho/v1',
            'separator' => $wp_rewrite->permalink_structure ? '?' : '&'
        ];
        $parsed_url = parse_url(get_admin_url());

        $base_apth_admin = str_replace($parsed_url['scheme'] . '://' . $parsed_url['host'], null, get_admin_url());
        wp_enqueue_script('bc_inventory_connector-admin-script', BC_INVENTORY_CONNECTOR_ASSET_URI . '/js/index.js');
        $bc_inventory_connector = apply_filters('bc_inventory_connector_localized_script', [
            'nonce'           => wp_create_nonce('bc_inventory_connector'),
            'confirm'         => __('Are you sure?', 'bc_inventory_connector'),
            'isPro'           => false,
            'routeComponents' => ['default' => null],
            'mixins'          => ['default' => null],
            'assetsURL'       => BC_INVENTORY_CONNECTOR_ASSET_URI . '/js/',
            'baseURL'         => $base_apth_admin . 'admin.php?page=bc_inventory_connector#/',
            'ajaxURL'         => admin_url('admin-ajax.php'),
            'api'             => $api,
        ]);

        wp_localize_script('bc_inventory_connector-admin-script', 'bc_inventory_connector', $bc_inventory_connector);
    }

    /**
     * Admin footer text.
     *
     * Fired by `admin_footer_text` filter.
     *
     * @since 1.3.5
     *
     * @param string $footer_text The content that will be printed.
     *
     * @return string The content that will be printed.
     **/
    public function admin_footer_text($footer_text)
    {
        $current_screen = get_current_screen();
        $is_bc_inventory_connectors_screen = ($current_screen && false !== strpos($current_screen->id, 'bc_inventory_connector'));

        if ($is_bc_inventory_connectors_screen) {
            $footer_text = sprintf(
                __('If you like %1$s please leave us a %2$s rating. A huge thank you from %3$s in advance!', 'bc_inventory_connector'),
                '<strong>' . __('bc_inventory_connector', 'bc_inventory_connector') . '</strong>',
                '<a href="https://wordpress.org/support/plugin/bc_inventory_connector/reviews/" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>',
                '<strong>Bit Press</strong>'
            );
        }

        return $footer_text;
    }
}
