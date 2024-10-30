<?php

namespace BCRM\BC_INVENTORY_CONNECTOR;

use BCRM\BC_INVENTORY_CONNECTOR\Admin\Admin_Bar;
use BCRM\BC_INVENTORY_CONNECTOR\Admin\Admin_Ajax;
use BCRM\BC_INVENTORY_CONNECTOR\API\Routes\Routes;
use BCRM\BC_INVENTORY_CONNECTOR\Core\Util\Activation;
use BCRM\BC_INVENTORY_CONNECTOR\Core\Util\HttpHelper;
use BCRM\BC_INVENTORY_CONNECTOR\Core\Util\Uninstallation;

/**
 * Main class for the plugin.
 *
 * @since 1.0.0-alpha
 */
final class Plugin
{
    /**
     * Main instance of the plugin.
     *
     * @since 1.0.0-alpha
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Holds various class instances
     *
     * @var array
     */
    private $container = [];

    /**
     * Registers the plugin with WordPress.
     *
     * @since 1.0.0-alpha
     */
    public function register()
    {
        (new Activation())->activate();
        (new Uninstallation())->register();

        $display_bc_form_meta = function () {
            printf('<meta name="generator" content="Forms by BitCode %s" />', esc_attr(BC_INVENTORY_CONNECTOR_VERSION));
        };
        add_action('wp_head', $display_bc_form_meta);
        add_action('login_head', $display_bc_form_meta);
        add_action('plugins_loaded', [$this, 'init_plugin']);
        add_action('rest_api_init', [$this, 'register_bf_api_routes'], 10);
        // Initiate the plugin on 'init'
        $this->init_plugin();
    }

    public function register_bf_api_routes()
    {
        $routes = new Routes();
        $routes->register_routes();
    }

    /*****************************frm***************************************************************** */
    /**
     * Do plugin upgrades
     *
     * @since 1.1.2
     *
     * @return void
     */
    public function plugin_upgrades()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
    }

    /**
     * Initialize the hooks
     *
     * @return void
     */
    public function init_hooks()
    {
        // Localize our plugin
        add_action('init', [$this, 'localization_setup']);

        // initialize the classes
        add_action('init', [$this, 'init_classes']);
        add_action('init', [$this, 'wpdb_table_shortcuts'], 0);

        add_action('woocommerce_loaded', function () {
            add_action('woocommerce_checkout_order_processed', [$this, 'executeIntegration'], 20, 2);
        });

        add_filter('plugin_action_links_' . plugin_basename(BC_INVENTORY_CONNECTOR_PLUGIN_MAIN_FILE), [$this, 'plugin_action_links']);
    }

    private function woocommerce_get_order($order_id)
    {
        if (!function_exists('wc_get_order')) {
            require_once dirname(WC_PLUGIN_FILE) . '/includes/class-wc-order.php';
        }

        if (function_exists('wc_get_order')) {
            return wc_get_order($order_id);
        }

        return (object) [];
    }

    public function executeIntegration($order_id, $importType)
    {
        global $wpdb;
        $if_already_imported_result = $wpdb->get_results("SELECT COUNT(response_type) as success_count FROM {$wpdb->prefix}bc_inventory_connector_log WHERE order_id = {$order_id} AND response_type = 'success' GROUP BY generated_at");
        foreach ($if_already_imported_result as $res) {
            if ($res->success_count === '2') {
                return;
            }
        }
        $integ_result = $wpdb->get_results("SELECT integration_details FROM {$wpdb->prefix}bc_inventory_connector ORDER BY id DESC LIMIT 1");

        if (!$integ_result) {
            return;
        }
        $integ_details = json_decode($integ_result[0]->integration_details);

        if (isset($integ_details->enabled) && !$integ_details->enabled) {
            return;
        }

        $order = $this->woocommerce_get_order($order_id);
        if ((intval($integ_details->tokenDetails->generates_on) + (55 * 60)) < time()) {
            $requiredParams['clientId'] = $integ_details->clientId;
            $requiredParams['clientSecret'] = $integ_details->clientSecret;
            $requiredParams['dataCenter'] = $integ_details->dataCenter;
            $requiredParams['tokenDetails'] = $integ_details->tokenDetails;
            $newTokenDetails = Admin_Ajax::refreshAccessToken((object)$requiredParams);
            if ($newTokenDetails) {
                $integ_details->tokenDetails = $newTokenDetails;
                $new_integ_details = wp_json_encode($integ_details);
                $wpdb->query("UPDATE {$wpdb->prefix}bc_inventory_connector SET integration_details = '{$new_integ_details}' WHERE id = 1");
            }
        }

        $fieldMap = $integ_details->field_map;
        $fieldData = [];

        $woocommerceFieldValuesMap = [
            'billing_address_1'   => $order->get_billing_address_1(),
            'billing_address_2'   => $order->get_billing_address_2(),
            'billing_city'        => $order->get_billing_city(),
            'billing_company'     => $order->get_billing_company(),
            'billing_country'     => $order->get_billing_country(),
            'billing_email'       => $order->get_billing_email(),
            'billing_first_name'  => $order->get_billing_first_name(),
            'billing_last_name'   => $order->get_billing_last_name(),
            'billing_phone'       => $order->get_billing_phone(),
            'billing_postcode'    => $order->get_billing_postcode(),
            'billing_state'       => $order->get_billing_state(),
            'order_comments'      => $order->get_customer_note(),
            'shipping_address_1'  => $order->get_shipping_address_1(),
            'shipping_address_2'  => $order->get_shipping_address_2(),
            'shipping_city'       => $order->get_shipping_city(),
            'shipping_company'    => $order->get_shipping_company(),
            'shipping_country'    => $order->get_shipping_country(),
            'shipping_first_name' => $order->get_shipping_first_name(),
            'shipping_last_name'  => $order->get_shipping_last_name(),
            'shipping_postcode'   => $order->get_shipping_postcode(),
            'shipping_state'      => $order->get_shipping_state(),
            'shipping_total'      => $order->get_shipping_total()
        ];

        foreach ($fieldMap as $fldType => $fields) {
            $fieldData[$fldType] = [];
            foreach ($fields as $fieldPair) {
                if (!empty($fieldPair->zohoFormField) && !empty($fieldPair->formField)) {
                    if ($fieldPair->formField === 'custom' && isset($fieldPair->customValue)) {
                        $fieldData[$fldType][$fieldPair->zohoFormField] = $fieldPair->customValue;
                    } elseif (strpos($fieldPair->zohoFormField, 'billing_address_') !== false || strpos($fieldPair->zohoFormField, 'shipping_address_') !== false) {
                        $fld = explode('_bc_', $fieldPair->zohoFormField);
                        $fieldData[$fldType][$fld[0]][$fld[1]] = $woocommerceFieldValuesMap[$fieldPair->formField];
                    } elseif (isset($woocommerceFieldValuesMap[$fieldPair->formField])) {
                        $fieldData[$fldType][$fieldPair->zohoFormField] = $woocommerceFieldValuesMap[$fieldPair->formField];
                    } else {
                        $fieldData[$fldType][$fieldPair->zohoFormField] = isset($order->{$fieldPair->formField}) ? $order->{$fieldPair->formField} : '';
                    }
                }
            }
        }

        $defaultHeader['Authorization'] = "Zoho-oauthtoken {$integ_details->tokenDetails->access_token}";
        $defaultHeader['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
        $generated_at = uniqid();

        $customer = null;

        // Search If Customer Email already Exists
        if (isset($fieldData['inventoryCustomerFields']['email']) && !empty($fieldData['inventoryCustomerFields']['email'])) {
            $searchCustomerEndpoint = "https://inventory.zoho.{$integ_details->dataCenter}/api/v1/contacts?organization_id={$integ_details->orgId}&email={$fieldData['inventoryCustomerFields']['email']}";
            $searchCustomerResponse = HttpHelper::get($searchCustomerEndpoint, null, $defaultHeader);
            if (isset($searchCustomerResponse->code) && $searchCustomerResponse->code === 0 && isset($searchCustomerResponse->contacts) && count($searchCustomerResponse->contacts)) {
                $customer = $searchCustomerResponse->contacts[0];
            }

            // add customer email as a contact persons
            $fieldData['inventoryCustomerFields']['contact_persons'] = [
                (object) [
                    'email'              => $fieldData['inventoryCustomerFields']['email'],
                    'is_primary_contact' => true
                ]
            ];
        }

        if (is_null($customer)) {
            // Create a Customer
            $createCustomerEndpoint = "https://inventory.zoho.{$integ_details->dataCenter}/api/v1/contacts?organization_id={$integ_details->orgId}";

            $data['JSONString'] = wp_json_encode($fieldData['inventoryCustomerFields']);

            $createCustomerResponse = HttpHelper::post($createCustomerEndpoint, $data, $defaultHeader);
            if (isset($createCustomerResponse->code) && $createCustomerResponse->code === 0) {
                $customer = $createCustomerResponse->contact;
                $this->saveToLogDB($order_id, 'customer', 'success', $createCustomerResponse, $generated_at);
            } elseif (isset($createCustomerResponse->code) && $createCustomerResponse->code !== 0) {
                $this->saveToLogDB($order_id, 'customer', 'error', $createCustomerResponse, $generated_at);
                return;
            }
        }

        if (!is_null($customer)) {
            if ($importType === 'contactsales' || !in_array($importType, ['contact', 'contactsales'])) {
                $customerId = $customer->contact_id;
                $fieldData['inventorySalesFields']['customer_id'] = $customerId;
                apply_filters('bc_inventory_connector_addSalesOrder', $order_id, $integ_details, $defaultHeader, $fieldData['inventorySalesFields'], $generated_at);
            }
        }
    }

    private function saveToLogDB($order_id, $apiType, $respType, $respObj, $generated_at)
    {
        global $wpdb;
        $respObj = addslashes(wp_json_encode($respObj));
        $wpdb->query("INSERT INTO {$wpdb->prefix}bc_inventory_connector_log(order_id, api_type, response_type, response_obj, generated_at) VALUE($order_id, '{$apiType}', '{$respType}', '{$respObj}', '$generated_at')");
    }

    /**
     * Set WPDB table shortcut names
     *
     * @return void
     */
    public function wpdb_table_shortcuts()
    {
        global $wpdb;

        $wpdb->bc_inventory_connector_schema = $wpdb->prefix . 'bc_inventory_connector_schema';
        $wpdb->bc_inventory_connector_schema_meta = $wpdb->prefix . 'bc_inventory_connector_schema_meta';
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup()
    {
        load_plugin_textdomain('bc_inventory_connector', false, BC_INVENTORY_CONNECTOR_PLUGIN_DIR_PATH . '/lang/');
    }

    /**
     * Instantiate the required classes
     *
     * @return void
     */
    public function init_classes()
    {
        if ($this->is_request('admin')) {
            $this->container['admin'] = (new Admin_Bar())->register();
            $this->container['admin_ajax'] = (new Admin_Ajax())->register();
        }
    }

    /**
     * Plugin action links
     *
     * @param  array $links
     *
     * @return array
     */
    public function plugin_action_links($links)
    {
        $links[] = '<a href="https://boostedcrm.com/" target="_blank">' . __('Boosted CRM', 'bc_inventory_connector') . '</a>';

        return $links;
    }

    /**
     * What type of request is this?
     *
     * @since 1.0.0-alpha
     *
     * @param  string $type admin, ajax, cron, api or frontend.
     *
     * @return bool
     */
    private function is_request($type)
    {
        switch ($type) {
            case 'admin':
                return is_admin();

            case 'ajax':
                return defined('DOING_AJAX');

            case 'cron':
                return defined('DOING_CRON');

            case 'api':
                return defined('REST_REQUEST');

            case 'frontend':
                return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
        }
    }

    public function init_plugin()
    {
        $this->init_hooks();

        do_action('bc_inventory_connector_loaded');
    }
    /********************************************************************************************** */

    /**
     * Retrieves the main instance of the plugin.
     *
     * @since 1.0.0-alpha
     *
     * @return BC_INVENTORY_CONNECTOR Plugin main instance.
     */
    public static function instance()
    {
        return static::$instance;
    }

    /**
     * Loads the plugin main instance and initializes it.
     *
     * @since 1.0.0-alpha
     *
     * @param string $main_file Absolute path to the plugin main file.
     * @return bool True if the plugin main instance could be loaded, false otherwise.
     */
    public static function load($main_file)
    {
        if (null !== static::$instance) {
            return false;
        }

        static::$instance = new static($main_file);
        static::$instance->register();

        return true;
    }
}
