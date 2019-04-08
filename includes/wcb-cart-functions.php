<?php
/**
 * WooCommerceBronto Order Functions
 *
 * Functions for cart/order specific things.
 *
 * @author    Bronto
 * @category  Core
 * @package   WooCommerceBronto/Functions
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//get settings globally
$bronto_settings = get_option('bronto_settings');

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


/**
 * REBUILD CART  - check for 'wcb_rebuild_cart=' in query string
 *
 * @access public
 * @return void
 */
 
add_action( 'wp_loaded', 'wcb_rebuild_cart');

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
		//trigger cart capture by Bronto
		wcb_cart_send();
	}
};

/**
 * ORDER SEND  - send order on thank you page
 *
 * @access public
 * @return void
 */
 
add_action( 'woocommerce_thankyou', 'wcb_order_send' );

function wcb_order_send( $order_id ) {

	if( is_wc_endpoint_url( 'order-received' ) ) {

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
		
		//begin data assembly  	
		$order = new WC_Order($order_id);
		$br_order_id = trim(str_replace('#', '', $order->get_order_number()));

		$phase = "ORDER_COMPLETE"; 
		$currency = (string) get_woocommerce_currency();
		$subtotal = (float) round($order->get_subtotal(), 2);
		$discountAmount = (float) round($order->get_total_discount(), 2);
		$taxAmount = (float) round($order->get_total_tax(), 2);
		$grandTotal = (float) round($order->get_total(), 2);
		$orderId = (string) $order->id;
	
		$emailAddress = $order->get_billing_email();

		$cartUrl = (string) wc_get_cart_url();
		$lineItems = (array) $order->get_items();
	
		// build line items array, rounding prices to 2 decimals
		foreach($lineItems as $lineItem) {
			$product = $order->get_product_from_item( $lineItem );
			$post_thumbnail_id = get_post_thumbnail_id($lineItem['product_id']);
			$imageUrl = wp_get_attachment_url( $post_thumbnail_id ); 
			$product_instance = wc_get_product($lineItem['product_id']);

			$CartLineItems[] = array (
				'sku' => (string) $product->get_sku(),
				'name' => (string) htmlspecialchars($product->get_name()), //escape double quotes with htmlspecialchars()
				'description' =>  (string) htmlspecialchars($product_instance->get_short_description()),
				'category' => implode(" ", $product->get_category_ids()),
				'unitPrice' => (float) round($product->get_price(), 2),
				'unitPrice' => (float) round($product->get_sale_price(), 2),
				'quantity' =>  (int) $lineItem->get_quantity(),
				'totalPrice' =>  (float) round(((float) $product->get_price()) * ((int) $lineItem->get_quantity()), 2),
				'imageUrl' => (string) $imageUrl,
				'productUrl' => (string) get_permalink($lineItem['product_id']) 
			);
		}
	
		//build php array only if cart has items in array
		if(count($CartLineItems) > 0 || !empty($order)) {
			$json_string = array(
				'phase' => $phase,
				'currency' => $currency,
				'subtotal' => $subtotal,
				'discountAmount' => $discountAmount,
				'taxAmount' => number_format($taxAmount, 2), 
				'grandTotal' => $grandTotal,
				'customerOrderId' => $orderId,
				'cartUrl' => $cartUrl,
				'lineItems' => $CartLineItems
			);

			//insert email address key into array only if email is not empty
			if (!empty($emailAddress)) {
				$json_string['emailAddress'] = $emailAddress;
			};
		
			//fetch mobile number
			session_start();
			if (isset($_SESSION['brMobileNumber'])) {
				$brMobileNumber = (string) $_SESSION['brMobileNumber'];
				$json_string['mobilePhoneNumber'] = $brMobileNumber;
				$json_string['orderSmsConsentChecked'] = 1;
				//unset mobile num storage
				unset($_SESSION['brMobileNumber']);
			};
			
			/// pretty print for debugging
			if($bronto_settings['bronto_cart_debug']){ //checks if checkbox checked
				echo '<pre>';
				//json encode array
				$json_string = json_encode($json_string, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
				echo $json_string;
				echo '</pre>';
			} 
			else {
				//json encode array
				$json_string = json_encode($json_string, JSON_UNESCAPED_SLASHES);

				//final script output to page
				echo '<script type="text/javascript">';
				echo 'brontoCart = ' . $json_string . ';';
				echo '</script>';
			
			} //endif $debug
		} //endif count($lineItems) > 0
	}//end if is_wc_endpoint_url( 'order-received' )
}


/**
 * ADD BRONTO CART TRACKING - send cart upon cart actions
 *
 * @access public
 * @return void
 */

//add_action('woocommerce_add_to_cart', 'wcb_cart_send', 50); //when item is added to cart from PDP page - breaks w/ ajax
add_action('wp_footer', 'wcb_cart_send', 1);
add_action('woocommerce_cart_actions', 'wcb_cart_send', 50); // when cart item is updated on cart page

function wcb_cart_send() {
	//omit on order thank you
	if ( is_wc_endpoint_url( 'order-received' ) ){return;}
	
	//check if cart url has rebuild params
	$current_url = explode( '?', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" );
	$utm_wcb_rebuild_cart = isset($_GET['wcb_rebuild_cart']) ? $_GET['wcb_rebuild_cart'] : '';
	if( $utm_wcb_rebuild_cart !='') {return;}
	
	//if not proceed
	global $current_user;
	wp_reset_query();
	wp_get_current_user();
	$cart = WC()->cart;
	
	//cart headers
	$currency = (string) get_woocommerce_currency();
	$subtotal = (float) round($cart->get_subtotal(), 2);
	$discountAmount = (float) round($cart->get_discount_total(), 2);
	$taxAmount = (float) round($cart->get_total_tax(), 2);
	$grandTotal = (float) round($cart->get_cart_contents_total(), 2);
	$cartLineItems = array();
	$email = get_email($current_user);
	if ($email !== ''){
	$cartEmail = $email;
	}
	
	//composite vs. normal products handling
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

	  $cartLineItems[]= array(
		'quantity' => (int) $values['quantity'],
		//'sku' => (string) htmlspecialchars($product->get_sku()), //escape double quotes with htmlspecialchars()
		'sku' => (string) htmlspecialchars($product->get_id()), //alternate sku as needed
		'name' => (string) (string) htmlspecialchars($product->get_name()),
		'description' => (string) (string) htmlspecialchars($product->get_description()),
		'productUrl' => (string) $product->get_permalink(),
		'imageUrl' => (string) $image,
		'category' => implode(" ",$categories),
		'other' => (string) $values['variation'],
		'salePrice' => (float) round($product->get_sale_price(), 2),
		'unitPrice' => (float) round($values['line_subtotal'], 2),
		'totalPrice' => (float) round($values['line_total'], 2)
	  );
	}
	
	if(count($cartLineItems) > 0) {
		$wcb_cart['composite'] = $composite_products;
		$wcb_cart['normal_products'] = $normal_products;
		$brontoCartUrl = wc_get_cart_url() . '?wcb_rebuild_cart=' . base64_encode(serialize(json_encode($wcb_cart)));
		$brontoEncoded = json_encode($brontoCart);
				
		$json_string = array(
			'currency' => $currency,
			'subtotal' => $subtotal,
			'discountAmount' => $discountAmount,
			'taxAmount' => number_format($taxAmount, 2), 
			'grandTotal' => $grandTotal,
			'cartUrl' => $brontoCartUrl,
			'lineItems' => $cartLineItems
		);
		if ($email !== ''){
			$json_string['customerEmail'] = $email;
		};
		
		// pretty print for debugging
		if($bronto_settings['bronto_cart_debug']){
			echo '<pre>';
			//json encode array
			$json_string = json_encode($json_string, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
			echo $json_string;
			echo '</pre>';
		} else {
			//json encode array
			$json_string = json_encode($json_string, JSON_UNESCAPED_SLASHES);

			//final script output to page
			echo '<script type="text/javascript">';
			echo 'brontoCart = ' . $json_string . ';';
			echo '</script>';
			//count, prevent multi-run
			++$cartRunCnt;
		} //endif $debug
	} else {
		echo '<script type="text/javascript">';
		echo 'brontoCart = {};';
		echo '</script>';
		//count, prevent multi-run
		++$cartRunCnt;		
	} //endif count($lineItems) > 0
}


/**
 * CHECKOUT - send cart upon entering checkout 
 *
 * @access public
 * @return void
 */

add_action( 'woocommerce_after_checkout_form', 'wcb_cart_send_checkout' );

function wcb_cart_send_checkout() {
	global $current_user;
	wp_reset_query();
	wp_get_current_user();
	$cart = WC()->cart;
	
	//cart headers
	$currency = (string) get_woocommerce_currency();
	$subtotal = (float) round($cart->get_subtotal(), 2);
	$discountAmount = (float) round($cart->get_discount_total(), 2);
	$taxAmount = (float) round($cart->get_total_tax(), 2);
	$grandTotal = (float) round($cart->get_cart_contents_total(), 2);
	$cartLineItems = array();
	$phase = 'BILLING';
	$email = get_email($current_user);
	if ($email !== ''){
	$cartEmail = $email;
	}
	
	//composite vs. normal products handling
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

	  $cartLineItems[]= array(
		'quantity' => (int) $values['quantity'],
		//'sku' => (string) htmlspecialchars($product->get_sku()), //escape double quotes with htmlspecialchars()
		'sku' => (string) htmlspecialchars($product->get_id()), //alternate sku as needed
		'name' => (string) (string) htmlspecialchars($product->get_name()),
		'description' => (string) (string) htmlspecialchars($product->get_description()),
		'productUrl' => (string) $product->get_permalink(),
		'imageUrl' => (string) $image,
		'category' => implode(" ",$categories),
		'other' => (string) $values['variation'],
		'salePrice' => (float) round($product->get_sale_price(), 2),
		'unitPrice' => (float) round($values['line_subtotal'], 2),
		'totalPrice' => (float) round($values['line_total'], 2)
	  );
	}
	
	if(count($cartLineItems) >= 0) {
		$wcb_cart['composite'] = $composite_products;
		$wcb_cart['normal_products'] = $normal_products;
		$brontoCartUrl = wc_get_cart_url() . '?wcb_rebuild_cart=' . base64_encode(serialize(json_encode($wcb_cart)));
		$brontoEncoded = json_encode($brontoCart);
				
		$json_string = array(
			'phase' => $phase,
			'currency' => $currency,
			'subtotal' => $subtotal,
			'discountAmount' => $discountAmount,
			'taxAmount' => number_format($taxAmount, 2), 
			'grandTotal' => $grandTotal,
			'cartUrl' => $brontoCartUrl,
			'lineItems' => $cartLineItems
		);
		if (isset($cartEmail)){
			$json_string['customerEmail'] = $cartEmail;
		};
		
		// pretty print for debugging
		if($bronto_settings['bronto_cart_debug']){
			echo '<pre>';
			//json encode array
			$json_string = json_encode($json_string, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
			echo $json_string;
			echo '</pre>';
		} else {
			//json encode array
			$json_string = json_encode($json_string, JSON_UNESCAPED_SLASHES);

			//final script output to page
			echo '<script type="text/javascript">';
			echo 'brontoCart = ' . $json_string . ';';
			echo '</script>';
		} //endif $debug
	} else {
		echo '<script type="text/javascript">';
		echo 'brontoCart = {}';
		echo '</script>';		
	} //endif count($lineItems) > 0
};


/**
 * ADD EMPTY CART - send cart empty cart actions
 *
 * @access public
 * @return void
 */
 
add_action('woocommerce_cart_is_empty', 'wcb_cart_empty', 50); // all cart items have been removed from cart page

function wcb_cart_empty() {
	echo '<script type="text/javascript">';
	echo 'brontoCart = {"lineItems":[] }';
	echo '</script>';		
}


/**
 * ADD CHECKBOX - Email opt-in checkbox to checkout
 *
 * @access public
 * @return void
 */

if (!empty($bronto_settings['bronto_newsletter_list_id'])) {
	// Add the checkbox field
	add_action('woocommerce_after_checkout_billing_form', 'br_checkbox_custom_checkout_field');
	// Post list request to Bronto
	add_action('woocommerce_checkout_update_order_meta', 'br_add_to_list');
}


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

function br_add_to_list( $order_id ) {
    $bronto_settings = get_option('bronto_settings');
    if ($_POST['br_newsletter_checkbox']){
          $email = $_POST['billing_email'];
		  $daUrl = 'https://app.bronto.com/public/?q=direct_add&fn=Public_DirectAddForm&id=' . $bronto_settings['bronto_direct_add_id'] . '&list1=' . $bronto_settings['bronto_newsletter_list_id'] . '&createCookie=1&email=' . $email;
		  $response = wp_remote_get($daUrl);
	}
}


/**
 * ADD CHECKBOX - SMS order notifications checkbox to checkout
 *
 * @access public
 * @return void
 */

if (!empty($bronto_settings['bronto_sms_order_updates_text'])) {
	// Add the checkbox field
	add_action('woocommerce_after_checkout_billing_form', 'br_checkbox_custom_checkout_field2');
	//session for mobile number storage
	session_start();
	add_action('woocommerce_checkout_update_order_meta', 'br_add_mobile_number');
}

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

function br_add_mobile_number( $order_id ) {
    if ($_POST['br_sms_checkbox']){
          $mobileNumber = $_POST['billing_phone'];
          $_SESSION['brMobileNumber'] = $mobileNumber;
	}
}





































