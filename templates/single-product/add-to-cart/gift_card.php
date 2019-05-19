<?php
/**
 * Simple custom product
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $product;

$name_your_price = new Alg_WC_Product_Open_Pricing_Core();
do_action( 'gift_card_before_add_to_cart_form' );  ?>

<form class="gift_card_cart" method="post" enctype='multipart/form-data'>
	<table cellspacing="0">
		<tbody>
			<tr>
				<td class="price">
					<?php

					$name_your_price->add_open_price_input_field_to_frontend();
						?>
				</td>
			</tr>
		</tbody>
	</table>
	<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
</form>

<?php do_action( 'gift_card_after_add_to_cart_form' ); ?>
