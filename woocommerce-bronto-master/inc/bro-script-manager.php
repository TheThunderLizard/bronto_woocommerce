<?php
class WPBrontoScriptManager {

    function __construct() {
        add_action('wp_head', array(&$this, 'insert_script_manager'));
    }

    function insert_script_manager() {
        global $current_user;
        wp_reset_query();

        wp_get_current_user();
        $bronto_settings = get_option('bronto_settings');

        if ($bronto_settings['bronto_script_manager_id'] == '') {
          return;
        }

        echo "\n" . '<!-- Start Bronto Script Manager // Plugin Version: ' . BRONTO_PLUGIN_VERSION .' -->' . "\n";
        echo '<script type="text/javascript">' . "\n";
        echo '!function(o,t){var e=window.bronto=function(){"string"==typeof arguments[0]&&e.q.push(arguments),e.go&&e.go()};e.q=e.q||[];var n=o.createElement(t),s=o.getElementsByTagName(t)[0];s.parentNode.insertBefore(n,s),n.async=!0,n.onload=e,n.src="https://snip.bronto.com/v2/sites/' . $bronto_settings['bronto_script_manager_id'] . '/assets/bundle.js"}(document,"script");' . "\n";
        echo '</script>' . "\n";
        echo '<!-- end: Bronto Bronto Script Manager -->' . "\n";
		
		
        if ($current_user->user_email) {
            echo "\n" . '<!-- Start Bronto Email Send -->' . "\n";
            echo '<script type="text/javascript">' . "\n";
            echo 'if (localStorage){ if (localStorage.getItem(\'WCB_email\') == null){ console.log("no email set"); localStorage.setItem("WCB_email", "' . $current_user->user_email . '");' . "\n";
            echo 'bronto("emailAddress:send","' . $current_user->user_email . '");' . "\n";
            echo '} else if ("' . $current_user->user_email . '" !== localStorage.getItem(\'WCB_email\')){ console.log("emails dont match "); localStorage.setItem(\'WCB_email\',"' . $current_user->user_email . '"); bronto("emailAddress:send","' . $current_user->user_email . '")  } else {  console.log(\'they are the same\'); } };' . "\n";
            echo '</script>' . "\n";
        echo '<!-- end: Bronto Email Send -->' . "\n";
        } else {
            // See if current user is a commenter
            $commenter = wp_get_current_commenter();
            if ($commenter['comment_author_email']) {
               	echo "\n" . '<!-- Start Bronto Email Send -->' . "\n";
               	echo '<script type="text/javascript">' . "\n";
               	echo 'if (localStorage){ if (localStorage.getItem(\'WCB_email\') == null){ 	console.log("no email set"); localStorage.setItem("WCB_email", "' . $commenter['comment_author_email'] . '");' . "\n";
               	echo 'bronto("emailAddress:send","' . $commenter['comment_author_email'] . '");' . "\n";
               	echo '} else if ("' . $commenter['comment_author_email'] . '" !== localStorage.getItem(\'WCB_email\')){ console.log("emails dont match "); localStorage.setItem(\'WCB_email\',"' . $commenter['comment_author_email'] . '"); bronto("emailAddress:send","' . $commenter['comment_author_email'] . '")  } else {  console.log(\'they are the same\'); } };' . "\n";
                echo '</script>' . "\n";
       			echo '<!-- end: Bronto Email Send -->' . "\n";
            }
        }
    }
}

?>
