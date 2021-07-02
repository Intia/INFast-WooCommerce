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
class Infast_Woocommerce_Admin_Settings {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Infast_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Infast_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/infast-woocommerce-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Infast_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Infast_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/infast-woocommerce-admin.js', array( 'jquery' ), $this->version, false );

	}

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

	public function infast_woocommerce_client_id_render() {

	    $options = get_option( 'infast_woocommerce' );
	    ?>
	    <input type='text' name='infast_woocommerce[client_id]' value='<?php echo $options['client_id']; ?>'>
	    <?php

	}

	public function infast_woocommerce_client_secret_render() {

	    $options = get_option( 'infast_woocommerce' );
	    ?>
	    <input type='text' name='infast_woocommerce[client_secret]' value='<?php echo $options['client_secret']; ?>'>
	    <?php

	}

	public function infast_woocommerce_enable_email_render() {

	    $options = get_option( 'infast_woocommerce' );
	    ?>
	    <input type="checkbox" name="infast_woocommerce[enable_email]" value="1" <?php if ( isset( $options['enable_email'] ) ) checked( $options['enable_email'], 1 ); ?>
		<?php

	}

	public function infast_woocommerce_section_callback() {
	}

	public function infast_sanitize_inputs( $input ) {

		$output = array();
		foreach( $input as $key => $value ) {
		    if( isset( $input[$key] ) ) {
		        $output[$key] = strip_tags( stripslashes( $input[ $key ] ) );
		    }   
		}

		return apply_filters( 'sandbox_theme_validate_input_examples', $output, $input );

	}

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
