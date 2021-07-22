<?php

/**
 * Admin class used to build our plugin settings page
 *
 * @link       https://www.vangus-agency.com
 * @since      1.0.0
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/admin
 */

/**
 * Admin class used to build our plugin settings page
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/admin
 * @author     Vangus <hello@vangus-agency.com>
 */
class Infast_Woocommerce_Admin_Settings {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Register our settings page
	 *
	 * @since    1.0.0
	 */
	public function register_admin_page() {

	    add_submenu_page(
	    	'woocommerce',
	    	__( 'Paramètres INFast', 'infast-woocommerce' ),
	    	'INFast',
	    	'manage_options',
	    	'infast-woocommerce-page',
	    	array( $this, 'infast_woocommerce_page' )
	    );

	}

	/**
	 * Adding our section with multiple fields to our settings page
	 *
	 * @since    1.0.0
	 */
	public function register_sections() {

	    register_setting(
	    	'infast-woocommerce',
	    	'infast_woocommerce',
	    	array( 'sanitize_callback' => array( $this, 'infast_sanitize_inputs' ) ) );

	    add_settings_section(
	        'infast_woocommerce_section',
	        __( 'Paramètres INFast', 'infast-woocommerce' ),
	        array( $this, 'infast_woocommerce_section_callback' ),
	        'infast-woocommerce'
	    );

	    add_settings_field(
	        'infast_woocommerce_client_id',
	        __( 'Cient ID', 'infast-woocommerce' ),
	        array( $this, 'infast_woocommerce_client_id_render' ),
	        'infast-woocommerce',
	        'infast_woocommerce_section'
	    );

	    add_settings_field(
	        'infast_woocommerce_client_secret',
	        __( 'Client secret', 'infast-woocommerce' ),
	        array( $this, 'infast_woocommerce_client_secret_render' ),
	        'infast-woocommerce',
	        'infast_woocommerce_section'
	    );

	    add_settings_field(
	        'infast_woocommerce_enable_email',
	        __( 'Envoyer les factures automatiquement par email ?', 'infast-woocommerce' ),
	        array( $this, 'infast_woocommerce_enable_email_render' ),
	        'infast-woocommerce',
	        'infast_woocommerce_section'
	    );

	}

	/**
	 * HTML render of Client ID field
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_client_id_render() {

	    $options = get_option( 'infast_woocommerce' );
	    ?>
	    <input type='text' name='infast_woocommerce[client_id]' value='<?php echo $options['client_id']; ?>'>
	    <?php

	}

	/**
	 * HTML render of Client secret field
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_client_secret_render() {

	    $options = get_option( 'infast_woocommerce' );
	    ?>
	    <input type='text' name='infast_woocommerce[client_secret]' value='<?php echo $options['client_secret']; ?>'>
	    <?php

	}

	/**
	 * HTML render of Enable email field
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_enable_email_render() {

	    $options = get_option( 'infast_woocommerce' );
	    ?>
	    <input type="checkbox" name="infast_woocommerce[enable_email]" value="1" <?php if ( isset( $options['enable_email'] ) ) checked( $options['enable_email'], 1 ); ?>
		<?php

	}

	/**
	 * HTML render of our section
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_section_callback() {
	}

	/**
	 * Sanitize the user inputs
	 *
	 * @since    1.0.0
	 */
	public function infast_sanitize_inputs( $input ) {

		$output = array();
		foreach( $input as $key => $value ) {
		    if( isset( $input[$key] ) ) {
		        $output[$key] = strip_tags( stripslashes( $input[ $key ] ) );
		    }   
		}

		return $output;

	}

	/**
	 * HTML render of our settings page
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_page() {

		settings_errors();
	    ?>
	    <form action='options.php' method='post'>

	        <?php
	        settings_fields( 'infast-woocommerce' );
	        do_settings_sections( 'infast-woocommerce' );
	        submit_button();
	        ?>

	    </form>
	    <?php

	}

	/**
	 * Generate a new OAuth2 token when client ID and/or secret has been updated
	 *
	 * @since    1.0.0
	 */
	public function infast_option_updated( $option, $old_value, $value ) {

		if ( $option == 'infast_woocommerce' ) {
			if ( ! empty( $value['client_id'] ) && ! empty( $value['client_secret'] ) &&
				 $value['client_id'] != $old_value['client_id'] ||
				 $value['client_secret'] != $old_value['client_secret'] ) {

				$infast_api = Infast_Woocommerce_Api::getInstance();
				$access_token = $infast_api->get_oauth2_token( true );

				if ( $access_token == false ) {
					add_settings_error( 'infast-woocommerce', 'OAuth2 Error', 'Votre client ID et/ou client secret n\'a pas pu etre vérifié' );
				}

			}
		}

	}

}
