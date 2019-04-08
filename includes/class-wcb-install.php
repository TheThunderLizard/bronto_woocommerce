<?php
/**
 * Installation related functions and actions.
 *
 * @author    Bronto
 * @category  Admin
 * @package   WooCommerceBronto/Classes
 * @version     0.9.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCB_Install' ) ) :

/**
 * WCB_Install Class
 */
class WCB_Install {

  /**
   * Hook in tabs.
   */
  public function __construct() {
    register_activation_hook( WCB_PLUGIN_FILE, array( $this, 'install' ) );

    add_action( 'admin_init', array( $this, 'check_version' ), 5 );
  }

  /**
   * check_version function.
   *
   * @access public
   * @return void
   */
  public function check_version() {
    if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_bronto_version' ) != WCB()->version ) ) {
      $this->install();

      do_action( 'woocommerce_bronto_updated' );
    }
  }

  /**
   * Install WCB
   */
  public function install() {
    $this->create_options();
    $this->create_roles();

    // Update version
    update_option( 'woocommerce_bronto_version', WCB()->version );

    // Flush rules after install
    flush_rewrite_rules();
  }
  
  /**
   * Default options
   *
   * Sets up the default options used on the settings page
   *
   * @access public
   */
  function create_options() { }

  /**
   * Create roles and capabilities
   */
  public function create_roles() {
    global $wp_roles;

    if ( class_exists( 'WP_Roles' ) ) {
      if ( ! isset( $wp_roles ) ) {
        $wp_roles = new WP_Roles();
      }
    }

    // Add supplemental permissions to certain users. Assumes WooCommerce roles exist.
    if ( is_object( $wp_roles ) ) {
      $capabilities = $this->get_core_capabilities();

      foreach ( $capabilities as $cap_group ) {
        foreach ( $cap_group as $cap ) {
          $wp_roles->add_cap( 'shop_manager', $cap );
          $wp_roles->add_cap( 'administrator', $cap );
        }
      }
    }
  }

  /**
   * Get capabilities for WooCommerceBronto - these are assigned to admin/shop manager during installation or reset
   *
   * @access public
   * @return array
   */
  public function get_core_capabilities() {
    $capabilities = array();

    $capability_types = array( 'bronto_shop_cart', );

    foreach ( $capability_types as $capability_type ) {

      $capabilities[ $capability_type ] = array(
        // Post type
        "edit_{$capability_type}",
        "read_{$capability_type}",
        "delete_{$capability_type}",
        "edit_{$capability_type}s",
        "edit_others_{$capability_type}s",
        "publish_{$capability_type}s",
        "read_private_{$capability_type}s",
        "delete_{$capability_type}s",
        "delete_private_{$capability_type}s",
        "delete_published_{$capability_type}s",
        "delete_others_{$capability_type}s",
        "edit_private_{$capability_type}s",
        "edit_published_{$capability_type}s",

        // Terms
        "manage_{$capability_type}_terms",
        "edit_{$capability_type}_terms",
        "delete_{$capability_type}_terms",
        "assign_{$capability_type}_terms"
      );
    }

    return $capabilities;
  }

  /**
   * woocommerce-bronto_remove_roles function.
   *
   * @access public
   * @return void
   */
  public function remove_roles() {
    global $wp_roles;

    if ( class_exists( 'WP_Roles' ) ) {
      if ( ! isset( $wp_roles ) ) {
        $wp_roles = new WP_Roles();
      }
    }

    if ( is_object( $wp_roles ) ) {

      $capabilities = $this->get_core_capabilities();

      foreach ( $capabilities as $cap_group ) {
        foreach ( $cap_group as $cap ) {
          $wp_roles->remove_cap( 'shop_manager', $cap );
          $wp_roles->remove_cap( 'administrator', $cap );
        }
      }
    }
  }
}

endif;

return new WCB_Install();
