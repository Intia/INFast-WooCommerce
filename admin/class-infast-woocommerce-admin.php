<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.vangus-agency.com
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
 * @author     Vangus <hello@vangus-agency.com>
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

		// We don't use any JS in admin yet
		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/infast-woocommerce-admin.js', array( 'jquery' ), $this->version, false );

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

}
