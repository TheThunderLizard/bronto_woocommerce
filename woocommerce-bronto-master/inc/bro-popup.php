<?php
class WPBrontoPopup {
    function __construct() {
        add_action('wp_footer', array(&$this, 'insert_popup'));
        add_action('wp_footer', array(&$this, 'insert_cart_send'));
    }
    function insert_popup() {
        global $current_user;
        wp_reset_query();
        wp_get_current_user();
        $bronto_settings = get_option('bronto_settings');
        if ($bronto_settings['bronto_popup_id'] == '' || $bronto_settings['bronto_popup'] == false) {
          return;
        }
                
        echo "\n" . '<!-- BEGIN BRONTO POPUP MANAGER -->' . "\n";
        echo '<script type="text/javascript">' . "\n";
        echo '(function(body) {' . "\n";
        echo 'var popupScript = document.createElement(\'script\');' . "\n";
        echo 'popupScript.setAttribute(\'bronto-popup-id\', "' . $bronto_settings['bronto_popup_id'] . '");' . "\n";
        echo 'popupScript.src = "https://cdn.bronto.com/popup/delivery.js";' . "\n";
        echo 'popupScript.async = true;' . "\n";
        echo 'body.appendChild(popupScript);' . "\n";
        echo '})(document.body || document.documentElement);' . "\n";
        echo '</script>' . "\n";
        echo '<!-- END BRONTO POPUP MANAGER -->' . "\n";
    }
    
    function insert_cart_send() {
        echo "\n" . '<!-- BRONTO CART SEND  -->' . "\n";
        echo '<script type="text/javascript">' . "\n";
        echo 'if (typeof brontoCart !== "undefined") {' . "\n";
        echo 'bronto("cart:send", brontoCart);' . "\n";
        echo '}' . "\n";
        echo '</script>' . "\n";
        echo '<!-- END BRONTO CART SEND -->' . "\n";
    }
}
?>