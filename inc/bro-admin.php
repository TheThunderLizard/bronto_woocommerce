<?PHP
class WPBrontoAdmin {

    function __construct() {
        if (is_admin()) {
            $bronto_settings = get_option('bronto_settings');

            add_action('admin_menu', array(&$this, 'add_options_subpanel'));
            add_filter('plugin_action_links_' . BRONTO_BASENAME, array(&$this, 'plugin_settings_link'));
        }
    }

    function add_options_subpanel() {
        if (function_exists('add_menu_page') && current_user_can('manage_options')) {
            global $submenu, $brontowp;

            add_menu_page('Bronto', 'Bronto', 'manage_options', 'bronto_settings', array($this, 'settings'), BRONTO_URL . 'img/bronto-logo.png');
            add_submenu_page('bronto_settings', 'Help', 'Help', 'manage_options', 'bronto_help', array($this, 'help'));

            $submenu['bronto_settings'][0][0] = 'Settings';
        }
    }

    function help() {
        $content = '';
        $content = '<ol>
                      <li><a href="#help-1">Where do I find my Bronto Script Manager ID?</a></li>
                      <li><a href="#help-2">How do I add a Bronto email sign up into my sidebar?</a></li>
                      <li><a href="#help-3">Where do I find my Bronto Direct Add ID?</a></li>
                      <li><a href="#help-4">Where do I find my Bronto Pop-up Manaer ID?</a></li>
                    </ol>
                    <p><a name="help-1"></a><h2>1) Where do I find my Bronto Script Manager ID?</h2></p>
                    <p>
                      You can find your Bronto Script Manager ID by going to SETTINGS &raquo; INTEGRATIONS &raquo; <a href="https://app.bronto.com/mail/pref/script_manager/">SCRIPT MANAGER</a> in Bronto.
                      If you click  <strong>get code</strong> the overlay will load with your unique script.
                      The value that is after <code>sites/</code> and up to <code>/assets</code> this is the ID you will need.<br /><br />

                      Once you have added the Script Manager ID you will be able to toggle features on and off from script manager in Bronto.
                    </p>
                    <p><a name="help-2"></a><h2>2) How do I add a Bronto email sign up into my sidebar?</h2></p>
                    <p>
                      Make sure you have added your Direct Add ID.<br />
                      Then you can find the widget under APPEARANCE &raquo; WIDGETS titled &quot;Bronto: Email Sign Up&quot;.
                    </p>
                    <p><a name="help-3"></a><h2>3) Where do I find my Bronto Direct Add ID?</h2></p>
                    <p>
                      You can find your Direct Add ID by going to <a href="https://app.bronto.com/mail/pref/data_exchange/#Mail_SitePref_TrackingPref">SETTINGS &raquo; DATA EXCHANGE</a>.<br />
                      In the section labeled "Direct Add" find the image URL, then scroll right and copy the value after <code>&id=</code> but before <code>&email=</code>.
                    </p>
                    <p><a name="help-4"></a><h2>4) Where do I find my Pop-up Manager ID?</h2></p>
                    <p>
                      You can find your Pop-up Manager ID by going to <a href="https://app.bronto.com/mail/apps/app/1/">CONTACTS &raquo; GROW &raquo; POP-UP MANAGER</a>.<br />
                      Click on <strong>script tag</strong>. Then copy the value that appears after <code>bronto-popup-id=</code> (without the quotes).
                    </p>';

        $content = $this->postbox('bronto-help', 'FAQ', $content);
        $this->admin_wrap('Bronto Plugin Help', $content);
    }

    function settings() {
        $bronto_settings = $this->process_settings();

        $content = '<p>The settings below help to deploy <a href="https://help.bronto.com/bmp/reference/r_bmp_scripts_overview.html">Bronto Script Manager</a> to your site, which will allow you to easily deploy other features to your WooCommerce store. You can find this ID on your Bronto <a href="https://app.bronto.com/mail/pref/script_manager/">Script Manager page</a>.</p>
            <p>Insert your Bronto List ID to add a newsletter checkbox on the checkout page. To find a List ID, open the list in Bronto and copy the value in the lower right corner of the page - looks like this: <code>010300000000040000000000060007008000</code>.</p>
            <table class="form-table">';

        if (function_exists('wp_nonce_field')) {
          $content .= wp_nonce_field('bronto-update-settings', '_wpnonce', true, false);
        }
        $content .= '<tr><th scope="row"><label for="bronto_script_manager_id">Script Manager ID</label></th><td><input type="text" class="regular-text" name="bronto_script_manager_id" placeholder="Script Manager ID" value="' . $bronto_settings['bronto_script_manager_id'] . '" /></td></tr>';
        $content .= '<tr><th scope="row"><label for="bronto_cart_debug">Enable Cart Debug (print logging)</label></th><td><input type="checkbox" name="bronto_popup" value="true" ' . checked($bronto_settings['bronto_cart_debug'], 'true', false) . ' /></td></tr>';
        $content .= '<tr><th scope="row"><label for="bronto_newsletter_text">Subscribe to newsletter text</label></th><td><input type="text" class="regular-text" name="bronto_newsletter_text" placeholder="Eg. Sign-up For our Emails" value="' . $bronto_settings['bronto_newsletter_text'] . '" /></td></tr>';
        $content .= '<tr><th scope="row"><label for="bronto_newsletter_list_id">List ID (enables checkout opt-in)</label></th><td><input type="text" class="regular-text" name="bronto_newsletter_list_id" placeholder="Bronto list ID" value="' . $bronto_settings['bronto_newsletter_list_id'] . '" /></td></tr>';
        $content .= '<tr><th scope="row"><label for="bronto_direct_add_id">Direct Add ID</label></th><td><input type="text" class="regular-text" name="bronto_direct_add_id" placeholder="Bronto Direct Add ID" value="' . $bronto_settings['bronto_direct_add_id'] . '" /></td></tr>';
        $content .= '<tr><th scope="row"><label for="bronto_popup">Enable Bronto signup forms</label></th><td><input type="checkbox" name="bronto_popup" value="true" ' . checked($bronto_settings['bronto_popup'], 'true', false) . ' /></td></tr>';
        $content .= '<tr><th scope="row"><label for="bronto_popup_id">Add your Pop-up Manager ID</label></th><td><input type="text" class="regular-text" name="bronto_popup_id" placeholder="Bronto Pop-up Manager ID" value="' . $bronto_settings['bronto_popup_id'] . '" /></td></tr>';
        $content .= '<tr><th scope="row"><label for="bronto_sms_order_updates_text">Enable SMS order consent</label></th><td><input type="text" class="regular-text" name="bronto_sms_order_updates_text" placeholder="Eg. I\'d like Text Message updates for this order." value="' . $bronto_settings['bronto_sms_order_updates_text'] . '" /></td></tr>';
        $content .= '<tr><th scope="row"><label for="bronto_configuration_warning">Disable Configuration Warning</label></th><td><input type="checkbox" name="admin_settings_message" value="true" ' . checked($bronto_settings['admin_settings_message'], 'true', false) . ' /></td></tr>';
        $content .= '</table>';
        $content .= '<p>This will automatically install the Bronto script needed for signup forms. Learn more about Bronto <a href="https://help.bronto.com/hc/en-us/articles/360002035871-Install-Bronto-Signup-Forms#verify-your-installation">Signup forms.</a></p>';
        
        $wrapped_content = $this->postbox('bronto-settings', 'Connect to Bronto', $content);
        
        $this->admin_wrap('Bronto Settings', $wrapped_content);
    }

    function process_settings() {
        $bronto_notification = new WPBrontoNotification('settings_update');

        if (!empty($_POST['bronto_option_submitted'])) {
             
            $bronto_settings = get_option('bronto_settings');

            if ($_GET['page'] == 'bronto_settings' && check_admin_referer('bronto-update-settings')) {
                if (isset($_POST['bronto_script_manager_id']) && strlen($_POST['bronto_script_manager_id']) < 8) {
                    $bronto_settings['bronto_script_manager_id'] = $_POST['bronto_script_manager_id'];
                }

                $bronto_setting_keys = ['bronto_script_manager_id', 'admin_settings_message', 'bronto_subscribe_checkbox', 'bronto_newsletter_list_id', 'bronto_newsletter_text', 'bronto_sms_order_updates_text', 'bronto_cart_debug', 'bronto_direct_add_id', 'bronto_popup', 'bronto_popup_id'];
                $bronto_updated_settings = array_fill_keys($bronto_setting_keys, '');
                
                foreach($_POST as $key => $value) {
                  $bronto_updated_settings[$key] = $value;
                }
                
                $bronto_settings = array_merge($bronto_settings, $bronto_updated_settings);
                                
                $bronto_notification->display_message(3);
                update_option('bronto_settings', $bronto_settings);
            }
        }
        
        return get_option('bronto_settings');
    }

    function plugin_settings_link($links) {
        $settings_link = '<a href="' . BRONTO_ADMIN . 'admin.php?page=bronto_settings">Settings</a>';
        array_unshift($links, $settings_link);

        return $links;
    }

    function show_plugin_support() {
        $content = '<p>First, check the <a href="' . BRONTO_ADMIN . 'admin.php?page=bronto_help">Help Section</a>. If you still have questions or want to give feedback, please contact <a href="https://app.bronto.com/shared/support/index/">Bronto support.</a></p>';
        return $this->postbox('bronto-support', 'Help / Feedback', $content);
    }

    function postbox($id, $title, $content) {
        $wrapper = '';
        $wrapper .= '<div id="' . $id . '" class="postbox">';
        $wrapper .=   '<div class="handlediv" title="Click to toggle"><br /></div>';
        $wrapper .=   '<h3 class="hndle"><span>' . $title . '</span></h3>';
        $wrapper .=   '<div class="inside">' . $content . '</div>';
        $wrapper .= '</div>';
        return $wrapper;
    }

    function admin_wrap($title, $content) {

    $showpluginsupport = $this->show_plugin_support();

      echo <<<EOT
        <div class="wrap">
          <div class="dashboard-widgets-wrap">
            <h2>{$title}</h2>
            <form method="post" action="">
              <div id="dashboard-widgets" class="metabox-holder">
                <div class="postbox-container" style="width:60%;">
                  <div class="meta-box-sortables ui-sortable">
                     {$content}
                    <p class="submit">
                      <input type="submit" name="bronto_option_submitted" class="button-primary" value="Save Settings" />
                    </p>
                  </div>
                </div>
                <div class="postbox-container" style="width:40%;">
                  <div class="meta-box-sortables ui-sortable">
                    {$showpluginsupport}
                  </div>
                </div>
                </div>
            </form>
          </div>
        </div>
EOT;

    }
  }
?>
