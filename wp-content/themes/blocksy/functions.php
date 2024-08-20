<?php

/**
 * Blocksy functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Blocksy
 */

if (version_compare(PHP_VERSION, '5.7.0', '<')) {
	require get_template_directory() . '/inc/php-fallback.php';
	return;
}

require get_template_directory() . '/inc/init.php';


// Add custom field to the general product data section
add_action('woocommerce_product_options_general_product_data', 'add_custom_shipping_cost_field');
function add_custom_shipping_cost_field()
{
	woocommerce_wp_text_input(
		array(
			'id' => '_custom_shipping_cost',
			'label' => __('Shipping Cost', 'woocommerce'),
			'desc_tip' => 'true',
			'description' => __('Enter the shipping cost for this product.', 'woocommerce'),
			'type' => 'number',
			'custom_attributes' => array(
				'step' => 'any',
				'min' => '0',
				'required' => 'required'
			)
		)
	);
}



// Save custom field value with validation
add_action('woocommerce_admin_process_product_object', 'save_custom_shipping_cost_field_with_validation');
function save_custom_shipping_cost_field_with_validation($product)
{
	if (isset($_POST['_custom_shipping_cost'])) {
		$custom_shipping_cost = sanitize_text_field($_POST['_custom_shipping_cost']);
		if (empty($custom_shipping_cost)) {
			// Add notice if the field is empty
			wc_add_notice(__('Shipping Cost is a required field.', 'woocommerce'), 'error');
		} else {
			$product->update_meta_data('_custom_shipping_cost', $custom_shipping_cost);
		}
	}
}
