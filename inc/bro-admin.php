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
        $content = '<style>code{font-size:10px;} h4{font-weight: 600; text-transform: uppercase; } input[type="checkbox"], .regular-text {margin:0px 0px 18px 0px;} div.inside h3 {padding: 10px !important; border: 1px solid #999; border-radius: 4px; margin:0px !important; font-weight:600 !important;} div.inside > p {padding: 10px; border:1px solid #efefef; background-color:#f6f6f6; margin: 0px 4px 10px 4px; !important;}</style>
        			<ol>
                      <li><a href="#help-1">Where do I find my Bronto Script Manager ID?</a></li>
                      <li><a href="#help-2">How do I add a Bronto email sign up into my sidebar?</a></li>
                      <li><a href="#help-3">Where do I find my Bronto Direct Add ID?</a></li>
                      <li><a href="#help-4">Where do I find my Bronto Pop-up Manaer ID?</a></li>
                      <li><a href="#help-4">Where do I find my Coupon Manager ID?</a></li>
                      <li><a href="#help-5">What is SMS Order Consent?</a></li>
                      <li><a href="#help-6">What is this "PDP Selector" setting?</a></li>
                      <li><a href="#help-6">What do I use for a Browse Recovery selector?</a></li>
                    </ol>
                    <h3 id="help-1">1. Where do I find my Bronto Script Manager ID?</h3>
                    <p>
                      You can find your Bronto Script Manager ID by going to SETTINGS &raquo; INTEGRATIONS &raquo; <a href="https://app.bronto.com/mail/pref/script_manager/">SCRIPT MANAGER</a> in Bronto.
                      If you click  <strong>get code</strong> the overlay will load with your unique script.  The value that is after <code>sites/</code> and up to <code>/assets</code> this is the ID you will need.<br /><br />

                      Once you have added the Script Manager ID you will be able to toggle features on and off from script manager in Bronto.
                    </p>
                    <h3 id="help-2">2. How do I add a Bronto email sign up into my sidebar?</h3>
                    <p>
                      Make sure you have added your Direct Add ID.  Then you can find the widget under APPEARANCE &raquo; WIDGETS titled &quot;Bronto: Email Sign Up&quot;.
                    </p>
                    <h3 id="help-3">3. Where do I find my Bronto Direct Add ID?</h3>
                    <p>
                      You can find your Direct Add ID by going to <a href="https://app.bronto.com/mail/pref/data_exchange/#Mail_SitePref_TrackingPref">SETTINGS &raquo; DATA EXCHANGE</a>.<br />  In the section labeled "Direct Add" find the image URL, then scroll right and copy the value after <code>&id=</code> but before <code>&email=</code>.
                    </p>
                    <h3 id="help-4">4. Where do I find my Pop-up Manager ID?</h3>
                    <p>
                      You can find your Coupon Manager ID by going to <a href="https://app.bronto.com/mail/apps/app/2/">COMMERCE &raquo; COUPON MANAGR &raquo; SETTINGS</a>.  In the overlay that loads you will see "Account ID" and a value that looks like this: <code>yW5XxZKGFM6nJpBuZCmabNdMeKEsbsQI8euRMqLczpWU</code>.  Copy this value.
                    </p>
                    <h3 id="help-5">5. What is SMS Order Consent?</h3>
                    <p>
                      SMS Order consent is a paid feature that allows you to trigger order notifications via text message to your customers.  The opt-in is displayed in checkout and consent must be given on each order.
                    </p>
                    <h3 id="help-6">6. What is this "PDP Selector" setting?</h3>
                    <p>
                      The PDP selector is a "css selector" used to determine where you want web recommendations to appear.
                    </p>
                    <h3 id="help-7">7. What do I use for a Browse Recovery selector?</h3>
                    <p>
                      By navigating to <a href="https://app.bronto.com/mail/apps/app/2/">SETTINGS &raquo; PLATFORM &raquo; BROWSE</a> you can access a menu by clicking a little gear icon (alternatively this menu might have moved to SETTINGS &raquo; INTEGRATIONS &raquo; SCRIPT MANAGER). Set "Ecommerce Platform" to <code>Other None</code> then set "Email Selectors" to <code>input[type="email"]</code>, then set the "Product ID Selector Type" to <code>JSON</code>and finally the "Product ID Selector
" to <code>brontoBrowseObject["product"]["id"]</code>.
                    </p>';

        $content = $this->postbox('bronto-help', 'FAQ', $content);
        $this->admin_wrap('Bronto Plugin Help', $content);
    }

    function settings() {
        $bronto_settings = $this->process_settings();

        $content = '<p>The settings below help to deploy various Bronto features to your website.</p>';

        if (function_exists('wp_nonce_field')) {
          $content .= wp_nonce_field('bronto-update-settings', '_wpnonce', true, false);
        }
        $content .= '<style>code{font-size:10px;} h4{font-weight: 600 !important;} input[type="checkbox"], .regular-text {margin:0px 0px 18px 0px;} input[name^="bronto"]{padding:5px; border-radius:5px; background-color:#efefef;} .inline {display:inline-block; font-weight:100 !important;} div.inside  input[type="checkbox"]{margin: 0px 10px 0px 0px;} .helpText {margin-bottom: 8px; font-size:.8em;}</style>';
        $content .= '<div><label for="bronto_script_manager_id"><h4>Script Manager ID</h4></label><div class="helpText">Adding <a href="https://help.bronto.com/bmp/reference/r_bmp_scripts_overview.html">Bronto Script Manager</a> to your site will enable simpler Bronto feature deployment to your WooCommerce store. You can find the below ID on your Bronto <a href="https://app.bronto.com/mail/pref/script_manager/">Script Manager page</a>.</div><input type="text" class="regular-text" name="bronto_script_manager_id" placeholder="Script Manager ID" value="' . $bronto_settings['bronto_script_manager_id'] . '" /></div>';
        $content .= '<div><input type="checkbox" name="bronto_popup" value="true" ' . checked($bronto_settings['bronto_cart_debug'], 'true', false) . ' /><label for="bronto_cart_debug"><h4 class="inline">Enable Cart Debug (print logging)</h4></label></div>';
        $content .= '<hr>';
        $content .= '<div><label for="bronto_direct_add_id"><h4>Direct Add ID</h4></label><input type="text" class="regular-text" name="bronto_direct_add_id" placeholder="Bronto Direct Add ID" value="' . $bronto_settings['bronto_direct_add_id'] . '" /></div>';
		$content .= '<div><label for="bronto_newsletter_text"><h4>Checkout - Subscribe to newsletter text</h4></label><input type="text" class="regular-text" name="bronto_newsletter_text" placeholder="Eg. Sign-up For our Emails" value="' . $bronto_settings['bronto_newsletter_text'] . '" /></div>';
        $content .= '<div><label for="bronto_newsletter_list_id"><h4>Checkout - List ID (enables checkout opt-in)</h4></label><div class="helpText">Insert the same o a different Bronto List ID to add a checkbox to the checkout page & add contacts to a distinct list.</div><input type="text" class="regular-text" name="bronto_newsletter_list_id" placeholder="Bronto list ID" value="' . $bronto_settings['bronto_newsletter_list_id'] . '" /></div>';
		$content .= '<hr>';
        $content .= '<div><label for="bronto_sms_order_updates_text"><h4>Enable SMS order consent</h4></label><div class="helpText">Entering you call to action below will enable SMS Order Consent capture at checkout.  Order based SMS is a paid Bonto feature.</div><input type="text" class="regular-text" name="bronto_sms_order_updates_text" placeholder="Eg. I\'d like Text Message updates for this order." value="' . $bronto_settings['bronto_sms_order_updates_text'] . '" /></div>';
		$content .= '<hr>';
		$content .= '<div><label for="bronto_popup_id"><h4>Add your Pop-up Manager ID</h4></label><div class="helpText">Entering your ID here will auto-deploy the pop-up manager to all pages of your site.</div><input type="text" class="regular-text" name="bronto_popup_id" placeholder="Bronto Pop-up Manager ID" value="' . $bronto_settings['bronto_popup_id'] . '" /></div>';
        $content .= '<hr>';
        $content .= '<div><label for="bronto_coupon_manager_id"><h4>Add your Coupon Manager ID</h4></label><input type="text" class="regular-text" name="bronto_coupon_manager_id" placeholder="Bronto Coupon Manager ID" value="' . $bronto_settings['bronto_coupon_manager_id'] . '" /></div>';
        $content .= '<hr>';
        $content .= '<div><label for="bronto_web_recs_pdp"><h4>Web Recs - "CSS Selector" for your Product Detail Page</h4></label><input type="text" class="regular-text" name="bronto_web_recs_pdp" placeholder="div.class.class" value="' . $bronto_settings['bronto_web_recs_pdp'] . '" /></div>';
        $content .= '<p>This CSS selector is used to target then insert 3 specific "div" tags into which Bronto Web Recommendations may load.  Learn more here <a href="https://help.bronto.com/bmp/concept/c_bmp_app_recommendations_web_div_tags.html">Placing Div Tags on your website.</a></p>';
        $content .= '<div><label for="bronto_web_recs_div1"><h4 class="inline">Label - Rec Block 1 (optional) &nbsp;&nbsp;</h4></label><input type="text" name="bronto_web_recs_div1" placeholder="Related Items" value="' . $bronto_settings['bronto_web_recs_div1'] . '" /></div>';
        $content .= '<div><label for="bronto_web_recs_div2"><h4 class="inline">Label - Rec Block 2 (optional) &nbsp;&nbsp;</h4></label><input type="text" name="bronto_web_recs_div2" placeholder="Others Also Viewed" value="' . $bronto_settings['bronto_web_recs_div2'] . '" /></div>';
        $content .= '<div><label for="bronto_web_recs_div3"><h4 class="inline">Label - Rec Block 3 (optional) &nbsp;&nbsp;</h4></label><input type="text" name="bronto_web_recs_div3" placeholder="Just For You" value="' . $bronto_settings['bronto_web_recs_div3'] . '" /></div>';
        $content .= '<div><input type="checkbox" name="bronto_web_recs_enabled" value="true" ' . checked($bronto_settings['bronto_web_recs_enabled'], 'true', false) . ' /><label for="bronto_web_recs_enabled"><h4 class="inline">Enable Web Recs ("render" function)</h4></label></div>';
        $content .= '<hr>';
        $content .= '<div><label for="bronto_configuration_warning"><h4>Disable Configuration Warning</h4></label><input type="checkbox" name="admin_settings_message" value="true" ' . checked($bronto_settings['admin_settings_message'], 'true', false) . ' /></div>';
        
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

                $bronto_setting_keys = ['bronto_script_manager_id', 'admin_settings_message', 'bronto_subscribe_checkbox', 'bronto_newsletter_list_id', 'bronto_newsletter_text', 'bronto_sms_order_updates_text', 'bronto_cart_debug', 'bronto_web_recs_enabled','bronto_web_recs_pdp','bronto_web_recs_div1','bronto_web_recs_div2','bronto_web_recs_div3', 'bronto_coupon_manager_id', 'bronto_direct_add_id', 'bronto_popup', 'bronto_popup_id'];
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
        <!-- REPORTING -->
        <!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','GTM-T29Q8S7');</script>
		<!-- End Google Tag Manager -->
        <!-- REPORTING END -->
        <div class="wrap">
          <div class="dashboard-widgets-wrap">
            <h3>{$title}</h3>
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
