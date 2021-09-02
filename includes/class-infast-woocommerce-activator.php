<?php

/**
 * Fired during plugin activation
 *
 * @link       https://intia.fr
 * @since      1.0.0
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

        $stored_saltkey1 = get_option( 'infast_saltkey_1' );
        if ( ! $stored_saltkey1 || empty( $stored_saltkey1 ) ) {
            $salt_key1 = bin2hex( random_bytes( 20 ) );
            add_option( 'infast_saltkey_1', $salt_key1, 'no' );            
        }

        $stored_saltkey2 = get_option( 'infast_saltkey_2' );
        if ( ! $stored_saltkey2 || empty( $stored_saltkey2 ) ) {
            $salt_key2 = bin2hex( random_bytes( 20 ) );
            add_option( 'infast_saltkey_2', $salt_key2, 'no' );            
        }

	}

}
