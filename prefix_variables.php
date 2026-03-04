<?php
$files = [
    'templates/thankyou-optimized.php',
    'templates/admin/dashboard.php',
    'templates/admin/documentation.php',
    'templates/admin/pos.php',
    'templates/admin/order-board.php',
    'templates/checkout-location-time.php',
    'templates/checkout-tip.php',
    'templates/location-modal.php',
    'templates/location-selector-tiles.php',
    'templates/location-selector-inline.php',
    'templates/admin/debug-info.php',
    'templates/admin/settings.php',
    'templates/admin/onboarding.php',
    'templates/admin/support-settings.php',
    'templates/admin/admin-settings.php',
    'templates/admin/checkout-fields.php',
    'templates/admin/help-admin.php',
    'templates/admin/help-partials/locations.php',
    'templates/admin/help-partials/orders.php',
    'templates/admin/help-partials/products.php',
    'templates/admin/help-partials/settings.php',
    'templates/admin/help-partials/support.php',
    'templates/admin/help-staff.php',
    'templates/admin/help-superadmin.php',
    'templates/admin/super-admin-settings.php',
    'templates/checkout-optimized.php',
    'templates/emails/pickup-reminder.php',
    'templates/emails/plain/pickup-reminder.php',
];

$skip_variables = [
    '$wpdb', '$post', '$wp_query', '$wp_rewrite', '$wp_auth_check_cookie', '$product', '$woocommerce', '$wc_order', '$order',
    '$_GET', '$_POST', '$_REQUEST', '$_SESSION', '$_SERVER', '$_COOKIE', '$_FILES', '$_ENV', '$GLOBALS',
    '$this', '$is_IIS', '$is_apache', '$is_nginx', '$is_macIE', '$is_winIE', '$is_gecko', '$is_lynx', '$is_opera', '$is_NS4', '$is_safari', '$is_chrome', '$is_iphone',
    '$wp_version', '$wp_db_version', '$tinymce_version', '$manifest_version', '$required_php_version', '$required_mysql_version',
    '$pagenow', '$taxnow', '$typenow', '$user_ID', '$user_identity', '$user_level', '$user_email', '$user_login', '$user_url', '$user_pass_md5', '$user_sids',
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) {
        echo "File not found: $path\n";
        continue;
    }

    $content = file_get_contents($path);
    $tokens = token_get_all($content);
    $new_content = '';

    foreach ($tokens as $token) {
        if (is_array($token)) {
            if ($token[0] === T_VARIABLE) {
                $var_name = $token[1];
                if (!in_array($var_name, $skip_variables) && strpos($var_name, '$lbite_') !== 0) {
                    $new_content .= '$lbite_' . substr($var_name, 1);
                } else {
                    $new_content .= $var_name;
                }
            } else {
                $new_content .= $token[1];
            }
        } else {
            $new_content .= $token;
        }
    }

    file_put_contents($path, $new_content);
    echo "Processed: $file\n";
}
