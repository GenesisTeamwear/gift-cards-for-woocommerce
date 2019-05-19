<?php
/*
Plugin Name: Add Gift Card as Custom Product Type
Description: A simple demo plugin on how to add Gift Card as your custom product type
Author: Jonny Rudd
Version: 1.0
*/
add_action( 'plugins_loaded', 'wcpt_register_gift_card_type' );
function wcpt_register_gift_card_type() {
	// declare the product class
	class WC_Product_Gift_Card extends WC_Product {
		public function __construct( $product ) {
			$this->product_type = 'gift_card';
			parent::__construct( $product );
			// add additional functions here
		}
	}
}
add_filter( 'product_type_selector', 'wcpt_add_gift_card_type' );
function wcpt_add_gift_card_type( $type ) {
	// Key should be exactly the same as in the class product_type
	$type['gift_card'] = __( 'Gift Card' );

	return $type;
}
add_filter( 'woocommerce_product_data_tabs', 'gift_card_tab' );
function gift_card_tab( $tabs ) {

	$tabs['gift_card'] = array(
		'label'  => __( 'Gift Card', 'wcpt' ),
		'target' => 'gift_card_options',
		'class'  => ('show_if_gift_card'),
	);
	return $tabs;
}
add_action( 'woocommerce_product_data_panels', 'wcpt_gift_card_options_product_tab_content' );
function wcpt_gift_card_options_product_tab_content() {
	// Dont forget to change the id in the div with your target of your product tab
	?><div id='gift_card_options' class='panel woocommerce_options_panel'><div class='options_group'>
		<?php
			woocommerce_wp_checkbox(
				array(
					'id'    => '_enable_gift_card',
					'label' => __( 'Enable Gift Card Product', 'wcpt' ),
				)
			);
									 woocommerce_wp_text_input(
										 array(
											 'id'          => '_gift_card_price',
											 'label'       => __( 'Price', 'wcpt' ),
											 'placeholder' => '',
											 'desc_tip'    => 'true',
											 'description' => __( 'Enter Gift Card Price.', 'wcpt' ),
										 )
									 );
		?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_process_product_meta', 'save_gift_card_options_field' );
function save_gift_card_options_field( $post_id ) {
	$enable_gift_card = isset( $_POST['_enable_gift_card'] ) ? 'yes' : 'no';
	update_post_meta( $post_id, '_enable_gift_card', $enable_gift_card );
	if ( isset( $_POST['_gift_card_price'] ) ) :
		update_post_meta( $post_id, '_gift_card_price', sanitize_text_field( $_POST['_gift_card_price'] ) );
	endif;
}
add_action( 'woocommerce_single_product_summary', 'gift_card_template', 60 );
function gift_card_template() {
	global $product;
	if ( 'gift_card' == $product->get_type() ) {
		$template_path = plugin_dir_path( __FILE__ ) . 'templates/';
		// Load the template
		wc_get_template(
			'single-product/add-to-cart/gift_card.php',
			'',
			'',
			trailingslashit( $template_path )
		);
	}
}

add_filter( 'woocommerce_locate_template', 'thankyou_template', 10, 3 );
function thankyou_template( $template, $template_name, $template_path ) {

	if ( 'checkout/thankyou.php' !== $template_name || WC_TEMPLATE_DEBUG_MODE ) {
		return $template;
	}

		// Look within passed path within the theme - this is priority.
		$template_name = 'thankyou.php';

		// Get our template
	if ( $template ) {
		$template = plugin_dir_path( __FILE__ ) . 'templates/checkout/' . $template_name;
	}

		return $template;
}

function create_coupon_progmatically( $price, $coupon_code ) {
	/**
 * Create a coupon programatically
 */
	$amount = $price; // Amount
	$discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product

	$coupon = array(
		'post_title' => $coupon_code,
		'post_content' => '',
		'post_status' => 'publish',
		'post_author' => 1,
		'post_type'     => 'shop_coupon',
	);

	$new_coupon_id = wp_insert_post( $coupon );

	// Add meta
	update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
	update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
	update_post_meta( $new_coupon_id, 'individual_use', 'no' );
	update_post_meta( $new_coupon_id, 'product_ids', '' );
	update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
	update_post_meta( $new_coupon_id, 'usage_limit', '1' );
	update_post_meta( $new_coupon_id, 'expiry_date', '' );
	update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
	update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

	return $coupon_code;
}

function crypto_rand_secure( $min, $max ) {
	$range = $max - $min;
	if ( $range < 1 ) {
		return $min; // not so random...
	}
	$log = ceil( log( $range, 2 ) );
	$bytes = (int) ($log / 8) + 1; // length in bytes
	$bits = (int) $log + 1; // length in bits
	$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
	do {
		$rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) );
		$rnd = $rnd & $filter; // discard irrelevant bits
	} while ( $rnd > $range );
	return $min + $rnd;
}

function getToken( $length ) {
	$token = '';
	$codeAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$codeAlphabet .= 'abcdefghijklmnopqrstuvwxyz';
	$codeAlphabet .= '0123456789';
	$max = strlen( $codeAlphabet ); // edited

	for ( $i = 0; $i < $length; $i++ ) {
		$token .= $codeAlphabet[ crypto_rand_secure( 0, $max - 1 ) ];
	}

	return $token;
}



add_action( 'woocommerce_email_order_meta', 'add_email_order_meta', 10, 3 );
/*
 * @param $order_obj Order Object
 * @param $sent_to_admin If this email is for administrator or for a customer
 * @param $plain_text HTML or Plain text (can be configured in WooCommerce > Settings > Emails)
 */
function add_email_order_meta( $order_obj, $sent_to_admin, $plain_text ) {

	// this order meta checks if order is marked as a gift
	$coupons_generated = (int) get_post_meta( $order_obj->get_order_number(), 'generated_coupons', true );

	// we won't display anything if it is not a gift
	if ( empty( $coupons_generated ) ) {
		return;
	}

	// ok, if it is the gift order, get all the other fields
	while ( 1 <= $coupons_generated ) {
		$gift_card_value = get_post_meta( $order_obj->get_order_number(), 'generated_coupon_code_value_' . $coupons_generated . '', true );
		$gift_card_code = get_post_meta( $order_obj->get_order_number(), 'generated_coupon_code_' . $coupons_generated . '', true );
		// ok, we will add the separate version for plaintext emails
		if ( $plain_text === false ) {

			// you shouldn't have to worry about inline styles, WooCommerce adds them itself depending on the theme you use
			echo '<h2>Gift Card Information</h2>
		<ul>
		<li><strong>Gift Card Code:</strong> ' . $gift_card_code . '</li>
		<li><strong>Gift Card Value:</strong> ' . $gift_card_value . '</li>
		</ul>';

		} else {

			echo "GIFT CARD INFORMATION\n
		Gift Card Code: $gift_card_code\n
		Gift Card Value: $gift_card_value";

		}
		$coupons_generated--;
	}

}

/**
 * Add the field to the checkout page
 */
add_action( 'woocommerce_after_order_notes', 'send_to_email_checkout_field' );
function send_to_email_checkout_field( $checkout ) {
	echo '<div id="customise_checkout_field"><h2>' . __( 'Send Gift Card To Another Email' ) . '</h2>';
	woocommerce_form_field(
		'send_coupon_to_another_email_address', array(
			'type' => 'text',
			'class' => array(
				'my-field-class form-row-wide',
			),
			'label' => __( 'Send Gift Card To Another Email Address?' ),
			'placeholder' => __( 'email address' ),
			'required' => false,
		) , $checkout->get_value( 'send_coupon_to_another_email_address' )
	);
	echo '</div>';
}

add_action( 'woocommerce_admin_order_data_after_shipping_address', 'edit_woocommerce_checkout_page', 10, 1 );
function edit_woocommerce_checkout_page( $order ) {
	global $post_id;
	$order = new WC_Order( $post_id );
	echo '<p><strong>' . __( 'Send Gift Card To' ) . ':</strong> ' . get_post_meta( $order->get_id(), 'send_coupon_to_another_email_address', true ) . '</p>';
}

add_filter( 'woocommerce_email_headers', 'custom_cc_email_headers', 10, 3 );
function custom_cc_email_headers( $header, $email_id, $order ) {

	// Get the custom email from user meta data  (with the correct User ID)
	$custom_user_email = get_user_meta( $order->get_id(), 'send_coupon_to_another_email_address', true );

	if ( empty( $custom_user_email ) ) {
		return $header; // Exit (if empty value)
	}

	$formatted_email = utf8_decode( '<' . $custom_user_email . '>' );

	// Add Cc to headers
	$header .= 'Cc: ' . $formatted_email . '\r\n';

	return $header;
}
