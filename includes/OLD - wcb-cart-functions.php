<?php
/**
 * WooCommerceBronto Order Functions
 *
 * Functions for order specific things.
 *
 * @author    Bronto
 * @category  Core
 * @package   WooCommerceBronto/Functions
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function add_composite_products_cart ($composite_products) {

  foreach ($composite_products as $product) {
    $container = array();
    foreach ($product as $i => $v) {
      $item = $v['item'];
      $container_id = $item['container_id'];
      if (isset($item['attributes'])) {
        $container[$container_id] = array(
          'product_id' => $item['product_id'],
          'quantity' => $item['quantity'],
          'variation_id' => $item['variation_id'],
          'attributes' => $item['attributes'],
        );
        } else {
          $container[$container_id] = array(
          'product_id' => $item['product_id'],
          'quantity' => $item['quantity'],
          );
        }
      }
      $added = WC_CP()->cart->add_composite_to_cart( $v['composite_id'], $v['composite_quantity'], $container );
    }
}

function add_encoded_composite($container_ids,$values) {
  $composite_product = array();
  foreach ($container_ids as $container_id => $container_values ) {
    $args = array();
    if (isset($container_values['attributes'])) {
      $args = array(
      'composite_id' => $container_values['composite_id'],
      'composite_quantity' => $values['quantity'],
      'item' => array(
      'product_id' => $container_values['product_id'],
      'quantity' => $container_values['quantity'],
      'container_id' => $container_id,
      'attributes' => $container_values['attributes'],
      'variation_id' => $container_values['variation_id'],
      )
    );
  } else {
    $args = array(
      'composite_id' => $container_values['composite_id'],
      'composite_quantity' => $values['quantity'],
      'item' => array(
        'product_id' => $container_values['product_id'],
        'quantity' => $container_values['quantity'],
        'container_id' => $container_id,
        )
      );
    }
    array_push($composite_product,$args);
  }
  return $composite_product;
}

function get_email($current_user) {
  $email = '';
  if ($current_user->user_email) {
    $email = $current_user->user_email;
  } else {
    // See if current user is a commenter
    $commenter = wp_get_current_commenter();
    if ($commenter['comment_author_email']) {
      $email = $commenter['comment_author_email'];
    }
  }
  return $email;
}

function wcb_rebuild_cart() {

  // Exit if in back-end
  if(is_admin()){return;}
  global $woocommerce;
  // Exit if not on cart page or no wcb_rebuild_cart parameter
  $current_url = explode( '?', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" );
  $utm_wcb_rebuild_cart = isset($_GET['wcb_rebuild_cart']) ? $_GET['wcb_rebuild_cart'] : '';
  if($current_url[0]!==wc_get_cart_url() || $utm_wcb_rebuild_cart==='') {return;}

  // Rebuild cart
  $woocommerce->cart->empty_cart(true);

  $br_cart = unserialize(base64_decode($utm_wcb_rebuild_cart));
  $br_cart = json_decode($br_cart, true);
  $composite_products = $br_cart['composite'];
  $normal_products = $br_cart['normal_products'];

  $container = array();
  foreach ($normal_products as $product) {
    $cart_key = $woocommerce->cart->add_to_cart($product['product_id'],$product['quantity'],$product['variation_id'],$product['variation']);
  }
  if ( class_exists( 'WC_Composite_Products' ) ) {
    add_composite_products_cart($composite_products);
    }
    $carturl = wc_get_cart_url();
    if ($current_url[0]==wc_get_cart_url()){
      header("Refresh:0; url=".$carturl);
    }
  }


/**
 * Insert tracking code to all pages to send cart data.
 *
 * @access public
 * @return void
 */
function wcb_insert_cart_tracking() {
	/*echo '<script type="text/javascript">console.log(\'insert cart tracking start\');</script>';*/
	if ( WC()->cart->get_cart_contents_count() !== 0 ) {
  
	if ( !$checkout ) {

	  global $current_user;
	  wp_reset_query();

	  wp_get_current_user();

	  $cart = WC()->cart;
	  $brontoCart = array(
		'cartPhase' => 'SHOPPING',
		'currency' => get_woocommerce_currency(),
		'lineItems' => array(),
		'subtotal' => $cart->subtotal,
		'shippingAmount' => $cart->shipping_total,
		'taxAmount' => $cart->tax_total,
		'grandTotal' => $cart->total
	  );
	  $email = get_email($current_user);
	  if ($email !== ''){
		$bronto['emailAddress'] = $email;
	  }
	  $wcb_cart = array();
	  $composite_products = array();
	  $normal_products = array();
	  foreach ( $cart->get_cart() as $cart_item_key => $values ) {
		$product = $values['data'];
		$parent_product_id = $product->get_parent_id();

		if ($product->get_parent_id() == 0 ) {
		  $parent_product_id = $product->get_id();
		}
		$categories_array = get_the_terms( $parent_product_id, 'product_cat' );
		$categories = wp_list_pluck( $categories_array, 'name' );

		$is_composite_child = false;

		if ( class_exists( 'WC_Composite_Products' ) ) {
			$product_encoded = json_encode($product);
			$is_composite_child = wc_cp_is_composited_cart_item($values);
			$container = wc_cp_get_composited_cart_item_container($values);

			if ($product->get_type() == 'composite') {
			  $composite_product = array();

			  foreach (wc_cp_get_composited_cart_items($values) as $key => $val) {
				$composite_product = add_encoded_composite($val['composite_data'],$values);
				break;
			  }
			  array_push($composite_products,$composite_product);
			} else {
			  if (!$is_composite_child) {
				$normal_products[$cart_item_key] = array('product_id'=>$values['product_id'],'quantity'=>$values['quantity'],'variation_id'=>$values['variation_id'],'variation'=>$values['variation']);
			  }
			}
		  } else {
			$normal_products[$cart_item_key] = array('product_id'=>$values['product_id'],'quantity'=>$values['quantity'],'variation_id'=>$values['variation_id'],'variation'=>$values['variation']);
		  }
		  $image = wp_get_attachment_url(get_post_thumbnail_id($product->get_id()));

		  if ($image == false) {
			$image = wp_get_attachment_url(get_post_thumbnail_id($parent_product_id));
		  }

		  $brontoCart['lineItems'] []= array(
			'quantity' => $values['quantity'],
			'sku' => $product->get_id(),
			//'sku' => $product->get_sku(),
			'name' => $product->get_name(),
			'description' => $product->get_description(),
			'productUrl' => $product->get_permalink(),
			'imageUrl' => $image,
			'category' => implode(" ",$categories),
			'other' => $values['variation'],
			'salePrice' => $product->get_sale_price(),
			'unitPrice' => $values['line_subtotal'],
			'totalPrice' => $values['line_total']
		  );
		}
		if ( empty($brontoCart['lineItems']) ) {
		  return;
		}

		$wcb_cart['composite'] = $composite_products;
		$wcb_cart['normal_products'] = $normal_products;
		$brontoCart['cartUrl'] = wc_get_cart_url() . '?wcb_rebuild_cart=' . base64_encode(serialize(json_encode($wcb_cart)));
		$brontoEncoded = json_encode($brontoCart);

	  echo "\n" . '<!-- Start Bronto for WooCommerce // Plugin Version: ' . WooCommerceBronto::getVersion() . ' -->' . "\n";
	  echo <<<EOT
	  echo '<script type="text/javascript">';
		bronto('cart:send', $brontoEncoded);
	  </script>
	  <!-- end: Bronto Code. -->
EOT;
}
}
}

add_action( 'wp_footer', 'wcb_insert_cart_tracking' );






/**
 * Insert tracking code code for tracking started checkout.
 *
 * @access public
 * @return void
 */

function wcb_insert_checkout_tracking($checkout) {

  global $current_user;
  wp_reset_query();

  wp_get_current_user();

  $cart = WC()->cart;
  $brontoCart = array(
	'cartPhase' => 'BILLING',
	'currency' => get_woocommerce_currency(),
	'lineItems' => array(),
	'subtotal' => $cart->subtotal,
	'shippingAmount' => $cart->shipping_total,
	'taxAmount' => $cart->tax_total,
	'grandTotal' => $cart->total
  );
  
  
  
  
  $wcb_cart = array();
  $composite_products = array();
  $normal_products = array();
  
  foreach ( $cart->get_cart() as $cart_item_key => $values ) {
	$product = $values['data'];
	$parent_product_id = $product->get_parent_id();

	if ($product->get_parent_id() == 0 ) {
	  $parent_product_id = $product->get_id();
	}
	$categories_array = get_the_terms( $parent_product_id, 'product_cat' );
	$categories = wp_list_pluck( $categories_array, 'name' );

	
	
	
	
	
	
	$is_composite_child = false;

	if ( class_exists( 'WC_Composite_Products' ) ) {
		$product_encoded = json_encode($product);
		$is_composite_child = wc_cp_is_composited_cart_item($values);
		$container = wc_cp_get_composited_cart_item_container($values);

		if ($product->get_type() == 'composite') {
		  $composite_product = array();

		  foreach (wc_cp_get_composited_cart_items($values) as $key => $val) {
			$composite_product = add_encoded_composite($val['composite_data'],$values);
			break;
		  }
		  array_push($composite_products,$composite_product);
		} else {
		  if (!$is_composite_child) {
			$normal_products[$cart_item_key] = array('product_id'=>$values['product_id'],'quantity'=>$values['quantity'],'variation_id'=>$values['variation_id'],'variation'=>$values['variation']);
		  }
		}
	  } else {
		$normal_products[$cart_item_key] = array('product_id'=>$values['product_id'],'quantity'=>$values['quantity'],'variation_id'=>$values['variation_id'],'variation'=>$values['variation']);
	  }
	  $image = wp_get_attachment_url(get_post_thumbnail_id($product->get_id()));

	  if ($image == false) {
		$image = wp_get_attachment_url(get_post_thumbnail_id($parent_product_id));
	  }

	  $brontoCart['lineItems'] []= array(
	  		'quantity' => $values['quantity'],
			'sku' => $product->get_id(),
			//'sku' => $product->get_sku(),
			'name' => $product->get_name(),
			'description' => $product->get_description(),
			'productUrl' => $product->get_permalink(),
			'imageUrl' => $image,
			'category' => implode(" ",$categories),
			'other' => $values['variation'],
			'salePrice' => $product->get_sale_price(),
			'unitPrice' => $values['line_subtotal'],
			'totalPrice' => $values['line_total']
	  );
	}
	if ( empty($brontoCart['lineItems']) ) {
	  return;
	}

	$email = get_email($current_user);
	$wcb_cart['composite'] = $composite_products;
	$wcb_cart['normal_products'] = $normal_products;
	$brontoCart['cartUrl'] = wc_get_cart_url() . '?wcb_rebuild_cart=' . base64_encode(serialize(json_encode($wcb_cart)));
	$brontoEncoded = json_encode($brontoCart);

  echo "\n" . '<!-- Start Bronto for WooCommerce // Plugin Version: ' . WooCommerceBronto::getVersion() . ' -->' . "\n";
  echo <<<EOT
  <script type="text/javascript">
  	window.brontoCart2 = $brontoEncoded;
	bronto('cart:send', $brontoEncoded);
  </script>
  <!-- end: Bronto Code. -->
EOT;
}

add_action( 'woocommerce_after_checkout_form', 'wcb_insert_checkout_tracking' );
add_action( 'wp_loaded', 'wcb_rebuild_cart');


/**
 * Insert tracking code code for Order Complete
 *
 * @access public
 * @return void
 */

function wcb_insert_order_tracking() {

	$brontoItems = [];

	if( is_wc_endpoint_url( 'order-received' ) ):

		if(isset($_GET['view-order'])) {
			$order_id = $_GET['view-order'];
		}
		//check if on view order-received page and get parameter is available
		else if(isset($_GET['order-received'])) {
			$order_id = $_GET['order-received'];
		}
		//no more get parameters in the url
		else {
			$url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			$template_name = strpos($url,'/order-received/') === false ? '/view-order/' : '/order-received/';
			if (strpos($url,$template_name) !== false) {
				$start = strpos($url,$template_name);
				$first_part = substr($url, $start+strlen($template_name));
				$order_id = substr($first_part, 0, strpos($first_part, '/'));
			}
		}
		//yes, I can retrieve the order via the order id
		$order = new WC_Order($order_id);
		$br_order_id = trim(str_replace('#', '', $order->get_order_number()));
		$order_item = $order->get_items();

		foreach( $order_item as $item ) {
			$product_name = $item['name'];
			$product_id = ($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
			$product_qty = $item['qty'];
			$product = wc_get_product($product_id);

			$brontoItems[] = array(
				'sku' => $product->get_sku(),
				'name' => $product->get_name(),
				'description' => $product->get_description(),
				'category' => $product->get_category_ids(),
				'unitPrice' => $product->get_regular_price(),
				'salePrice' => $product->get_sale_price(),
				'quantity' => $item['quantity'],
				'totalPrice' => $product->get_price()*$item['quantity'],
				'imageUrl' => wp_get_attachment_image_src($product->get_image_id()),
				'productUrl' => $product->get_permalink()
			);
		}
		/*
		if (null !== $_cart->get_cart_discount_total()) {
			$br_discount = $_cart->get_cart_discount_total(); 
		} 
		else {
			$br_discount = $_cart->get_cart_discount_total(); $br_discount = 0;
		}
		*/
		
		echo '<script type="text/javascript">' . "\n";
		echo	'brontoCart = {' . "\n";
		echo		'"cartPhase": "ORDER_COMPLETE",' . "\n";
		echo		'"grandTotal": wc_format_decimal( $br_order_id, 2 ),' . "\n";
		echo		'"currency": "USD",' . "\n";
		echo		'"subtotal": wc_format_decimal( $_cart->subtotal , 2 ),' . "\n";
		echo		'//"discountAmount": $br_discount,' . "\n";
		echo		'//"taxAmount": wc_format_decimal( $_cart->get_taxes_total(), 2 ),'   . "\n";
		echo		'"lineItems": json_encode($brontoItems)' . "\n";
		echo	'}' . "\n";
		echo '</script>' . "\n";

	
	endif;
}


add_action('wp_footer', 'wcb_insert_order_tracking');


/**
* Insert email checkbox at checkout
*/

function br_checkbox_custom_checkout_field( $checkout ) {
    $bronto_settings = get_option('bronto_settings');
    woocommerce_form_field( 'br_newsletter_checkbox', array(
    'type'          => 'checkbox',
    'class'         => array('br_newsletter_checkbox_field'),
    'label'         => $bronto_settings['bronto_newsletter_text'],
    'value'  => true,
    'default' => 0,
    'required'  => false,
    ), $checkout->get_value( 'br_newsletter_checkbox' ));
}

/**
* Insert sms checkbox at checkout
*

function br_checkbox_custom_checkout_field2( $checkout ) {
    $bronto_settings = get_option('bronto_settings');
    woocommerce_form_field( 'br_sms_checkbox', array(
    'type'          => 'checkbox',
    'class'         => array('br_sms_checkbox_field'),
    'label'         => $bronto_settings['bronto_sms_order_updates_text'],
    'value'  => true,
    'default' => 0,
    'required'  => false,
    ), $checkout->get_value( 'br_sms_checkbox' ));
}
*/

function br_add_to_list( $order_id ) {
    $bronto_settings = get_option('bronto_settings');
    if ($_POST['br_newsletter_checkbox']){
          $email = $_POST['billing_email'];
          //if sms opt-in
          //if ($_POST['br_sms_checkbox']){
          //	$mobileNumber = $_POST['billing_phone'];
          //}
          //else {$mobileNumber =''};
		  
		  
		  $daUrl = 'https://app.bronto.com/public/?q=direct_add&fn=Public_DirectAddForm&id=' . $bronto_settings['bronto_direct_add_id'] . '&list1=' . $bronto_settings['bronto_newsletter_list_id'] . '&createCookie=1&email=' . $email;
		  $response = wp_remote_get($daUrl);
          
          /*** ORIGINAL  
          $url = 'https://app.bronto.com/public/?q=direct_add&fn=Public_DirectAddForm&id=' . $bronto_settings['bronto_direct_add_id'] . 'list1=' . $bronto_settings['bronto_newsletter_list_id'] . '&createCookie=1&email=' . $email;
          $data = http_build_query( array( 'email' => $email ) );
          $options = array(
			  'http' => array(
				  'header'  => "Content-type: application/x-www-form-urlencoded",
				  'method'  => 'GET',
				  'content' => $data,
			  ),
		  );

          update_post_meta( $order_id, 'bronto-response', $response );
          */
	}
}


$bronto_settings = get_option('bronto_settings');
if (!empty($bronto_settings['bronto_newsletter_list_id'])) {

	// Add the checkbox field
	add_action('woocommerce_after_checkout_billing_form', 'br_checkbox_custom_checkout_field');

	// Post list request to Bronto
	add_action('woocommerce_checkout_update_order_meta', 'br_add_to_list');

}
