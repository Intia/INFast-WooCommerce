<?php

/**
 * Admin class used to build our plugin settings page
 *
 * @link       https://intia.fr
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
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Admin_Settings {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $admin ) {

		$this->admin = $admin;

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

	    add_settings_field(
	        'infast_woocommerce_cc_email',
	        __( 'Envoyer une copie des emails à cette adresse', 'infast-woocommerce' ),
	        array( $this, 'infast_woocommerce_cc_email_render' ),
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
	    <input type='text' name='infast_woocommerce[client_id]' id="infast-client-id" value='<?php echo esc_attr( $options['client_id'] ); ?>'>
	    <?php

	}

	/**
	 * HTML render of Client secret field
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_client_secret_render() {

	    $options = get_option( 'infast_woocommerce' );
	    if ( array_key_exists( 'client_secret', $options ) ) {
		    $value = $options['client_secret'];
		    if ( ! empty( $value ) )
		    	$value = '*******************************';
		} else
			$value = '';
	    ?>
	    <input type='text' name='infast_woocommerce[client_secret]' id="infast-client-secret" value='<?php echo esc_attr( $value ); ?>'>
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
	 * HTML render of CC email field
	 *
	 * @since    1.0.0
	 */
	public function infast_woocommerce_cc_email_render() {

	    $options = get_option( 'infast_woocommerce' );
	    ?>
	    <input type="email" name="infast_woocommerce[cc_email]" value="<?php if ( isset ( $options['cc_email'] ) ) echo esc_attr( $options['cc_email'] ); ?>" />
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
		foreach( $input as $idx => $value ) {

			// error_log( 'FIXME LOG infast_sanitize_inputs idx:'. $idx . ' value:'. $value );

			if( isset( $input[$idx] ) ) {
		    	if ( $idx == 'client_secret' ) {
					$infast_api = Infast_Woocommerce_Api::getInstance();

					$oldClientSecret = get_option( 'infast_woocommerce' )['client_secret'];
					$oldClientSecretDecrypted = $infast_api->decrypt_key( $oldClientSecret );

					// BUG ???
					// I don't understand why we pass twice in infast_sanitize_inputs
					// We pass a first time, and we encrypt the clientSecret
					// During the second pass $oldClientSecret is empty but $input[$idx] as the value of the encrypted clientSecret of the fist pass.
					// => We encrypt the clientSecret again
		    		if ( strpos( $input[$idx], '*' ) !== false || $input[$idx] == $oldClientSecretDecrypted ) {
			    		$output[$idx] = $oldClientSecret;
			    	} else if ( ! empty( $value ) ) {
			    		$output[$idx] = $infast_api->encrypt_key( $value );
			    	}
		    	} else {
		        	$output[$idx] = strip_tags( stripslashes( $input[ $idx ] ) );
		    	}
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

		<h2><?php _e( 'Tester la connexion à INFast', 'infast-woocommerce' ); ?></h2>
	    <button type="button" class="button button-primary infast-test-btn"><?php _e( 'Tester la connexion', 'infast-woocommerce' ); ?></button>
		</br>
		
	    <h2><?php _e( 'Synchroniser les produits', 'infast-woocommerce' ); ?></h2>
	    <button type="button" class="button button-primary infast-syncall-btn"><?php _e( 'Lancer la synchronisation', 'infast-woocommerce' ); ?></button>
	    <?php
	}

	/**
	 * Generate a new OAuth2 token when client ID and/or secret has been updated
	 *
	 * @since    1.0.0
	 */
	public function infast_option_updated( $option, $old_value, $value ) {

		if ( $option == 'infast_woocommerce' ) {
			if ( ( ! empty( $value['client_id'] ) && ! empty( $value['client_secret'] ) ) &&
				 ( $value['client_id'] != $old_value['client_id'] ||
				 $value['client_secret'] != $old_value['client_secret'] ) ) {

				$infast_api = Infast_Woocommerce_Api::getInstance();
				$access_token = $infast_api->get_oauth2_token( true );

				if ( $access_token == false ) {
					add_settings_error( 'infast-woocommerce', 'OAuth2 Error', 'Votre client ID et/ou client secret n\'a pas pu etre vérifié' );
				}

			}
		}

	}

	/**
	 * Add custom field to shipping methods to store INFast ID
	 *
	 * @since    1.0.0
	 */
	public function infast_shipping_add_infast_id_field( $settings ) {

	    $settings['infast_shipping_id'] = array(
	        'title'       => esc_html__( 'INFast ID', 'flightbox' ),
	        'type'        => 'hidden',
	        'description' => '',
	    );

	    return $settings;
	}

	public function infast_shipping_add_infast_id_field_filter() {
	    $shipping_methods = WC()->shipping->get_shipping_methods();
	    foreach ( $shipping_methods as $shipping_method ) {
	        add_filter( 'woocommerce_shipping_instance_form_fields_' . $shipping_method->id, array( $this, 'infast_shipping_add_infast_id_field' ) );
	    }
	}

	public function infast_synchronise_all() {
		$this->admin->synchronise_all();
		wp_send_json_success();
	}

	public function infast_test_authentication() {
		$name = $this->admin->test_authentication();
		if($name) {
			wp_send_json_success($name);
		} else {
			wp_send_json_error();
		}		
	}
	
}
