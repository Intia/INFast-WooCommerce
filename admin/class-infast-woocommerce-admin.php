<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://intia.fr
 * @since      1.0.0
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/admin
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		// We don't use any CSS in admin yet
		//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/infast-woocommerce-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/infast-woocommerce-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Call our specific INFast API class to run the process of creating a new invoice
	 *
	 * @since    1.0.0
	 */
	public function generate_invoice( $order_id ) {

		$infast_api = Infast_Woocommerce_Api::getInstance();
		$infast_api->generate_invoice( $order_id );

	}

	/**
	 * This function synchronise all WooCommerce products to INFast
	 *
	 * @since    1.0.0
	 */
	public function synchronise_all() {

		$infast_api = Infast_Woocommerce_Api::getInstance();

		$args = array(
		  'numberposts' => -1,
		  'post_type'   => 'product'
		);
		 
		$products = get_posts( $args );
		foreach ( $products as $product ) {
            $infast_product_id = get_post_meta( $product->ID, '_infast_product_id', true );
            if ( ! $infast_product_id )
				$infast_api->create_product( $product->ID );
			else
				$infast_api->create_product( $product->ID, $infast_product_id );
		}


		// $zones = WC_Shipping_Zones::get_zones();
		// $shipping_methods = array_map(function($zone) {
		//     return $zone['shipping_methods'];
		// }, $zones);

		// foreach ( $shipping_methods as $shipping_method ) {
		//     foreach ( $shipping_method as $shipping_idx => $shipping ) {

		//     	$data_shipping = get_option( 'woocommerce_' . $method_id . '_' . $shipping_id . '_settings' );
		//     	$infast_shipping_id = isset( $data_shipping['infast_shipping_id'] ) ? isset( $data_shipping['infast_shipping_id'] ) : false;
		//     	if ( ! $infast_product_id )
		//     		$infast_api->create_product( $shipping_id, $method_id, $item,  );
		//     	else
		//     		$infast_api->create_product( $product->ID, $infast_product_id );

		//     }
		// }

	}

	/**
	 * Update the user on INFast
	 *
	 * @since    1.0.0
	 * @param      integer    $user_id       WooCommerce User ID to update
	 * @param      array    $old_user_data       Old user data
	 */
	public function update_user( $user_id, $old_user_data ) {

		$infast_customer_id = get_user_meta( $user_id, '_infast_customer_id', true );
		if ( $infast_customer_id ) {
			$infast_api = Infast_Woocommerce_Api::getInstance();
			$infast_api->create_customer( $user_id, NULL, $infast_customer_id );
		}

	}

	/**
	 * Update the product on INFast
	 *
	 * @since    1.0.0
	 * @param      integer    $product_id       WooCommerce Product ID updated
	 */
	public function update_product( $product_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		    return;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			return;

		// if ( ! $update )
		// 	return;

		$product = wc_get_product( $product_id );
		$infast_product_id = get_post_meta( $product_id, '_infast_product_id', true );
		if ( ! $infast_product_id )
			$infast_product_id = NULL;
		$infast_api = Infast_Woocommerce_Api::getInstance();
		$infast_api->create_product( $product_id, $infast_product_id );


	}

}
