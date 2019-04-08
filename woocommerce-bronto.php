<?php
/**
 * Plugin Name: Bronto for WooCommerce V1
 * Plugin URI: http://wordpress.org/extend/plugins/woocommerce-bronto/
 * Description: A plugin to automatically sync your WooCommerce carts, orders, and newsletter sign-ups.  Also enables Browse Recovery, Cart recovery, and pop-up sign-up and checkout opt-in.
 * Version: 1.0.0
 * Author: Bronto, Inc.
 * Author URI: https://www.bronto.com
 * Requires at least: 
 * Tested up to: 
 * WC requires at least: 2.0
 * WC tested up to: 3.5.0
 * Text Domain: woocommerce-bronto
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerceBronto
 * @category Core
 * @author Bronto
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! function_exists('is_plugin_inactive')) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if(is_plugin_inactive( 'woocommerce/woocommerce.php')) {return;}

if (is_plugin_active('bronto/bronto.php')) {
    //plugin is activated
    die('Bronto now bundles the wordpress plugin and the Woocommerce plugin. Please deactivate or remove the Bronto Wordpress plugin and re activate the Bronto for WooCommerce plugin.');
}

if ( ! class_exists( 'WooCommerceBronto' ) ) :

/**
 * Main WooCommerceBronto Class
 *
 * @class WooCommerceBronto
 * @version 1.0.0
 */
final class WooCommerceBronto {

  /**
   * @var string
   */
  public static $version = '1.0.0';

  /**
   * @var WooCommerceBronto The single instance of the class
   * @since 2.0.0
   */
  protected static $_instance = null;

  /**
   * Get plugin version number.
   *
   * @since 2.0.0
   * @static
   * @return int
   */
  public static function getVersion() {
    return self::$version;
  }

  /**
   * Main WooCommerceBronto Instance
   *
   * Ensures only one instance of WooCommerceBronto is loaded or can be loaded.
   *
   * @since 1.0.0
   * @static
   * @see WCB()
   * @return WooCommerceBronto - Main instance
   */
  public static function instance() {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  /**
   * Cloning is forbidden.
   *
   * @since 1.0.0
   */
  public function __clone() {
    _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-bronto' ), '0.9' );
  }

  /**
   * Unserializing instances of this class is forbidden.
   *
   * @since 1.0.0
   */
  public function __wakeup() {
    _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-bronto' ), '0.9' );
  }

  /**
   * WooCommerceBronto Constructor.
   * @access public
   * @return WooCommerceBronto
   */
  public function __construct() {
    // Auto-load classes on demand
    if ( function_exists( "__autoload" ) ) {
      spl_autoload_register( "__autoload" );
    }

    spl_autoload_register( array( $this, 'autoload' ) );

    $this->define_constants();

    // Include required files
    $this->includes();

    // Init API
    $this->api = new WCB_API();

    // Hooks
    add_action( 'init', array( $this, 'init' ), 0 );
    // add_action( 'init', array( $this, 'include_template_functions' ) );

    // Loaded action
    do_action( 'woocommerce_bronto_loaded' );
  }

  /**
   * Auto-load in-accessible properties on demand.
   *
   * @param mixed $key
   * @return mixed
   */
  public function __get( $key ) {
    if ( method_exists( $this, $key ) ) {
      return $this->$key();
    }
    return false;
  }

  /**
   * Auto-load WC classes on demand to reduce memory consumption.
   *
   * @param mixed $class
   * @return void
   */
  public function autoload( $class ) {
    $path  = null;
    $class = strtolower( $class );
    $file = 'class-' . str_replace( '_', '-', $class ) . '.php';

    if ( $path && is_readable( $path . $file ) ) {
      include_once( $path . $file );
      return;
    }

    // Fallback
    if ( strpos( $class, 'wcb_' ) === 0 ) {
      $path = $this->plugin_path() . '/includes/';
    }

    if ( $path && is_readable( $path . $file ) ) {
      include_once( $path . $file );
      return;
    }
  }

   // Define WC Constants

  private function define_constants() {
    define( 'WCB_PLUGIN_FILE', __FILE__ );
    define( 'WCB_VERSION', $this->version );

    // if ( ! defined( 'WCB_TEMPLATE_PATH' ) ) {
    //   define( 'WCB_TEMPLATE_PATH', $this->template_path() );
    // }
  }

   // Include required core files used in admin and on the frontend.


  private function includes() {
    include_once( 'includes/wcb-core-functions.php' );
    include_once( 'includes/class-wcb-install.php' );
  }

  /**
   * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
   */
  // public function include_template_functions() {
  //   include_once( 'includes/wc-template-functions.php' );
  // }

  /**
   * Init WooCommerceBronto when WordPress Initialises.
   */
  public function init() {
    // Init action
    do_action( 'woocommerce_bronto_init' );
  }

  /**
   * Get the plugin url.
   *
   * @return string
   */
  public function plugin_url() {
    return untrailingslashit( plugins_url( '/', __FILE__ ) );
  }

  /**
   * Get the plugin path.
   *
   * @return string
   */
  public function plugin_path() {
    return untrailingslashit( plugin_dir_path( __FILE__ ) );
  }

}

endif;

/**
 * Returns the main instance of WCB to prevent the need to use globals.
 *
 * @since  0.9
 * @return WooCommerceBronto
 */
function WCB() {
  return WooCommerceBronto::instance();
}

// Global for backwards compatibility.
$GLOBALS['woocommerce-bronto'] = WCB();

// load the wordpress tracking and widgets

// Makes sure the plugin is defined before trying to use it

$url = plugins_url();

if ( ! function_exists('is_plugin_inactive')) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if (is_plugin_inactive('wordpress-bronto-master/bronto.php')) {
    //plugin is not activated

$my_plugin_file = __FILE__;

if (isset($plugin)) {
    $my_plugin_file = $plugin;
}
else if (isset($mu_plugin)) {
    $my_plugin_file = $mu_plugin;
}
else if (isset($network_plugin)) {
    $my_plugin_file = $network_plugin;
}


//
// CONSTANTS
// ------------------------------------------
if (!defined('BRONTO_URL')) {
    define('BRONTO_URL', plugin_dir_url($my_plugin_file));
}
if (!defined('BRONTO_PATH')) {
    define('BRONTO_PATH', WP_PLUGIN_DIR . '/' . basename(dirname($my_plugin_file)) . '/');
}
if (!defined('BRONTO_BASENAME')) {
    define('BRONTO_BASENAME', plugin_basename($my_plugin_file));
}
if (!defined('BRONTO_ADMIN')) {
    define('BRONTO_ADMIN', admin_url());
}
if (!defined('BRONTO_PLUGIN_VERSION' ) ) {
    define('BRONTO_PLUGIN_VERSION', '1.3');
}

//
// INCLUDES
// ------------------------------------------
require_once(BRONTO_PATH . 'inc/bro-script-manager.php');
require_once(BRONTO_PATH . 'inc/bro-admin.php');
require_once(BRONTO_PATH . 'inc/bro-widgets.php');
require_once(BRONTO_PATH . 'inc/bro-notice.php');
require_once(BRONTO_PATH . 'inc/bro-popup.php');




//
// HELPER CLASS - WPBronto
// ------------------------------------------

class WPBronto {

    public static function is_connected($bronto_script_manager_id='') {
        if (trim($bronto_script_manager_id) != '') {
            return true;
        } else {
            $bronto_settings = get_option('bronto_settings');
            if (trim($bronto_settings['bronto_script_manager_id']) != '') {
                return true;
            } else {
                return false;
            }
        }
    }

    function __construct() {
        global $brontowp_admin, $brontowp_notice, $brontowp_analytics, $brontowp_tracking;
        global $post;

        $brontowp_analytics = new WPBrontoScriptManager();
        
        $brontowp_admin = new WPBrontoAdmin();

        $brontowp_analytics = new WPBrontoPopup();

        // Display config message.
        $brontowp_message = new WPBrontoNotification();
        add_action('admin_notices', array(&$brontowp_message, 'config_warning'));

        $brwidget = function($name) {
			return register_widget("Bronto_EmailSignUp_Widget");
		};

		add_action('widgets_init', $brwidget);
    }

    function add_defaults() {
        $bronto_settings = get_option('bronto_settings');

        if (($bronto_settings['installed'] != 'true') || !is_array($bronto_settings)) {
            $bronto_settings = array(
                'installed' => 'true',
                'bronto_script_manager_id' => '',
                'bronto_newsletter_list_id' => '',
                'admin_settings_message' => '',
                'bronto_newsletter_text' => '',
                'bronto_sms_order_updates_text' => '',
                'bronto_cart_debug' => '',
                'bronto_popup' => ''
            );
            update_option('bronto_settings', $bronto_settings);
        }
    }

    function format_text($content, $br=true) {
        return $content;
    }
}



//
// INIT
// ------------------------------------------

global $brontowp;
$brontowp = new WPBronto();
// RegisterDefault settings
register_activation_hook(__FILE__, array( $brontowp, 'add_defaults'));

}