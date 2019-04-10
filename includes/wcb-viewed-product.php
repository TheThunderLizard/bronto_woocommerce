<?php
add_action( 'wp_footer', 'view_product', PHP_INT_MAX );
function view_product() {
	if ( is_product() ) {
		global $product;
		$parent_product_id = $product->get_parent_id();
        if ($product->get_parent_id() == 0 ) {
			$parent_product_id = $product->get_id();
        }
		$product_title    = (string) $product->get_name();
		$product_id       = (int) $product->get_id();
		$parent_product_id = (int) $parent_product_id;
		$permalink        = (string) get_permalink( $product->get_id() );
		$price            = (float) $product->get_price();
		$image            = (string) wp_get_attachment_url(get_post_thumbnail_id($product->get_id()));
		$categories_array = get_the_terms( $product->get_id(), 'product_cat' );
		if ($categories_array === false) {
			$categories_array = array();
		}
		$categories       = (string) json_encode( wp_list_pluck( $categories_array, 'name' ) );
		$output = <<<EOT
			<script>
			    brontoBrowseObject={
			    	'product':{
			    		'title': '{$product_title}',
						'id': '{$parent_product_id}',
						'variantId': '{$product_id}',
						'categories': '{$categories}', // The list of categories is an array of strings.
						'imageUrl': '{$image}',
						'url': '{$permalink}',
						'price': {$price}
					}
				};
				console.log('insert brontoBrowseObject');				
			</script>
EOT;
		echo $output;
		
		/**
		 * WEB RECS - create divs to inject web recs
		 *
		 */
		 
		//setings for recommendations web
		$bronto_settings = get_option('bronto_settings');
		if (!empty($bronto_settings['bronto_web_recs_pdp'])) {
			
			//rec's container divs
			$brSelector = (string) $bronto_settings['bronto_web_recs_pdp'];
			if (!empty($bronto_settings['bronto_web_recs_div1'])){
				$brDiv1 = (string) $bronto_settings['bronto_web_recs_div1'];
			} else {
				$brDiv1 = "";
			};
			if (!empty($bronto_settings['bronto_web_recs_div2'])){
				$brDiv2 = (string) $bronto_settings['bronto_web_recs_div2'];
			} else {
				$brDiv2 = "";
			};
			if (!empty($bronto_settings['bronto_web_recs_div3'])){
				$brDiv3 = (string) $bronto_settings['bronto_web_recs_div3'];
			} else {
				$brDiv3 = "";
			};
			
			//build script to create divs using seleector
			echo '<script type="text/javascript" >';
			echo '	var querySelector = "' . $brSelector . '";';	
			echo '	for (var i = 0; i < 3; i++){';
			echo '		var brontoDiv = document.createElement("div");';
			echo '		brontoDiv.id = "bronto-rec" + (i + 1);';
			echo '		if (i == 0){brontoDiv.innerHTML = "<h2>' . $brDiv1 . '</h2>";}'; 
			echo '		else if (i == 1){brontoDiv.innerHTML = "<h2>' . $brDiv2 . '</h2>";}'; 
			echo '		else {brontoDiv.innerHTML = "<h2>' . $brDiv3 . '</h2>";}';
			echo '		brontoDiv.style.display = "none";';
			echo '		var recTarget = document.querySelector(querySelector);';
			echo '		recTarget.appendChild(brontoDiv);';
			echo '	}';
			if ($bronto_settings['bronto_web_recs_enabled']){
				//if enabled - fire "render"
				echo '	bronto("webRecs:render");';
				echo '	bronto("log:print");';
				// NEXT //
				//var brontoIdentifier = {value: "*\/product\/*"};
				//set request time criteria
				//bronto("webRecs:setParameter", {name: "brand", value: seller_name_rtc});
			}//endif web recs enabled
			echo '</script>';
		}//endif web recs pdp
	}//endif pdp check
}