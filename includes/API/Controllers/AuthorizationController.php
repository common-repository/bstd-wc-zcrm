<?php

namespace BCRM\BC_INVENTORY_CONNECTOR\API\Controllers;

// use WP_REST_Controller;

class AuthorizationController
{
    public function Authorization()
    {
        $code = urlencode(sanitize_text_field($_GET['code']));
        $location = urlencode(esc_url_raw($_GET['location']));
        $accountServer = esc_url_raw($_GET['accounts-server']);
        $url = admin_url("/admin.php?page=bc_inventory_connector#/?code={$code}&location={$location}&accounts-server={$accountServer}");
        if (wp_safe_redirect($url)) {
            exit;
        }

        if (!headers_sent()) {
            header('Content-Type: text/html');
        }

        echo "<script type='text/javascript'>window.location='$url'</script><a href='$url'>please click here to redirect</a>";
        exit;
    }
}
