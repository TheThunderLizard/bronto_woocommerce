<?php
class WPBrontoNotification {

    public $admin_message_text = '';
    public $default_message_text = '';

    function __construct($default_message_text = '') {
        $this->admin_message_text = '';
        $this->default_message_text = $default_message_text;
    }

    function config_warning() {
        $bronto_settings = get_option('bronto_settings');

        if (!WPBronto::is_connected($bronto_settings['public_api_key'])) {
            if (!$bronto_settings['admin_settings_message']) {
                if (!(isset($_GET['page']) && $_GET['page'] == 'bronto_settings')) {
                    $this->admin_message('config_warning');
                }
            }
        }
    }

    function admin_message($message='default-error', $display_time=0) {
        $message_text = '';

        switch ($message) {
            case 'settings_update':
                $message_text = 'Bronto settings updated.';
                break;
            case 'config_warning':
                $message_text = 'Please go to the <a href="' . BRONTO_ADMIN . 'admin.php?page=bronto_settings">Bronto settings page</a> to add your Bronto Script Manager ID keys or to hide this warning.';
                break;
            case 'default_error':
                $message_text = 'An error occurred, please try again or contact Bronto support.';
                break;
            default:
                $message_text = $message;
                break;
        }

        echo '<div id="msg-' . $message . '" class="updated fade"><p>' . $message_text . '</p></div>' . "\n";
        if ($display_time != 0) {
            echo '<script type="text/javascript">setTimeout(function () { jQuery("#msg-' . $message . '").hide("slow");}, ' . $display_time * 1000 . ');</script>';
        }
    }

    function add_message($message_text) {
        if (trim($this->admin_message_text) != '') {
            $this->admin_message_text .= '<br />';
        }
        $this->admin_message_text .= $message_text;
    }

    function display_message($display_time=0) {
        if (trim($this->admin_message_text) != '') {
            $this->admin_message($this->admin_message_text, $display_time);
        } else {
            $this->admin_message($this->default_message_text, $display_time);
        }
    }
}

?>
