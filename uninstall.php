<?php
/**
 * WooCommerceBronto Uninstall
 *
 * Uninstalling WooCommerceBronto deletes user roles, options, tables, and pages.
 *
 * @author    Bronto
 * @category  Core
 * @package   WooCommerceBronto/Uninstaller
 * @version   1.0.2
 */
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) 
  exit();

// Caps
$installer = include( 'includes/class-wcb-install.php' );
$installer->remove_roles();