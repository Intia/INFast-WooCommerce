<?php

/**
 * The file that defines the class interacting with INFast API
 *
 *
 * @link       https://intia.fr
 * @since      1.0.0
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes
 */

/**
 * The file that defines the class interacting with INFast API
 *
 * Theses functions allow the communication with INFast throught their API (OAuth2 authentification, create a customer, create au document, ...)
 *
 * @since      1.0.0
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes
 * @author     INTIA <dev@intia.fr>
 */
class Infast_Woocommerce_Api {

    /**
     * This class in a Singleton, so you can access it from anywhere else with Infast_Woocommerce_Api::getInstance();
     *
     * @since    1.0.0
     */
    private static $instance = null;

    private function __construct() {
    }

    public static function getInstance()
    {
        if ( self::$instance == null ) {
            self::$instance = new Infast_Woocommerce_Api();
        }  
        return self::$instance;
    }

    /**
	 * Encrypt a key
	 *
	 * @since    1.0.0
	 * @param    string     $string    key to encrypt
	 */
	public function encrypt_key( $string )
	{
		$salt1 = get_option( 'infast_saltkey_1' );
        $salt2 = get_option( 'infast_saltkey_2' );

	    $encrypt_method = 'AES-256-CBC';
	    $key = hash( 'sha256', $salt1 );
	    $iv = substr( hash( 'sha256', $salt2 ), 0, 16 ); // sha256 is hash_hmac_algo
	    $output = openssl_encrypt( $string, $encrypt_method, $key, 0, $iv );
	    $output = base64_encode( $output );

	    return '~' . $output;
	}

    /**
     * Decrypt key
     *
     * @since    1.0.0
     * @param      string    $string       The key
     */
    public function decrypt_key( $string ) {

        $string = ltrim( $string, '~' );
        $salt1 = get_option( 'infast_saltkey_1' );
        $salt2 = get_option( 'infast_saltkey_2' );

        $encrypt_method = 'AES-256-CBC';
        $key = hash( 'sha256', $salt1 );
        $iv = substr( hash( 'sha256',  $salt2 ), 0, 16 ); // sha256 is hash_hmac_algo
        $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );

        return $output;
    }

    /**
     * Get the OAuth2 token used to authenticate INFast API calls. Generate it if needed.
     *
     * @since    1.0.0
     * @param      bool    $override       Force Oauth2 token regeneration
     */
    public function get_oauth2_token( $override = false ) {

        if ( $override == false ) {
            $access_token = get_option( 'infast_access_token' );
            if ( $access_token != false && ! empty( $access_token ) )
                return $access_token;
        }

        $url = INFAST_API_URL . 'oauth2/token';

        $options = get_option( 'infast_woocommerce' );
        $client_secret = $this->decrypt_key( $options['client_secret'] );
        $body = array(
            'client_id'     => $options['client_id'],
            'client_secret' => $client_secret,
            'grant_type'    => 'client_credentials',
            'scope'         => 'write',
        );

        $headers = array(
            'Content-Type'  =>  'application/x-www-form-urlencoded',
        );

        $args = array(
            'body'        => $body,
            'headers'     => $headers,
        );

        $resp = wp_remote_post( $url, $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( is_wp_error( $resp ) ) {

            $error_message = $resp->get_error_message();
            error_log( 'INFast WooCommerce - Get OAuth2 access token : ' . $error_message );
            update_option( 'infast_access_token', false );
            return false;

        } else if ( $http_code == 200) {

            $resp_body = json_decode( $resp['body'], true );
            if ( is_array( $resp_body ) && array_key_exists( 'access_token', $resp_body ) ){
                $access_token = $resp_body['access_token'];
                update_option( 'infast_access_token', $access_token );
                return $access_token;                
            } else {
                error_log( 'INFast WooCommerce - Get OAuth2 access token : ' . print_r( $resp['body'] ) );
                update_option( 'infast_access_token', false );
                return false;
            }

        } else {
            error_log( 'INFast WooCommerce - Get OAuth2 access token : ' . print_r( $resp['body'] ) );
            update_option( 'infast_access_token', false );
            return false;  
        }

        
    }

    public function test_authentication( $force = false ) {
        $access_token = $this->get_oauth2_token( $force );
        if ( $access_token == false ) {
            error_log( 'INFast API: Failed to test authentication' );
            return false;
        }

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_get( INFAST_API_URL . 'api/v1/me', $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->test_authentication( true );
            }

            error_log( 'INFast API: Failed to test authentication' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            error_log( 'INFast API: Failed to test authentication' );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $me = $response['name'];
            return $me;            
        }
    }

    /**
     * Manage to process to create a new invoice on INFast from a WooCommercer order ID, including creating a new customer on INFast first
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to create the invoice on INFast
     */
    public function generate_invoice( $order_id ) {

        $order = wc_get_order( $order_id );
        $user_id = $order->get_user_id();
        $infast_customer_id = get_user_meta( $user_id, '_infast_customer_id', true );

        if ( ! $infast_customer_id ) {
            $infast_customer_id = NULL;
            $infast_customer_id = $this->create_customer( $user_id, $order_id, $infast_customer_id );
        }
        if ( $infast_customer_id ) {
            $document_id = $this->create_document( $order_id, $infast_customer_id );
            if ( $document_id ) {
                $this->add_document_payment( $order_id, $document_id );

                $options = get_option( 'infast_woocommerce' );
                if ( $options['enable_email'] ) {
                    $this->send_document_email( $order_id, $document_id );
                }
            }
        }

    }

    /**
     * Create a document on INFast
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to create the invoice on INFast
     * @param      string    $customer_id       INFast customer ID previously created
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    private function create_document( $order_id, $customer_id, $force = false ) {

        $order = wc_get_order( $order_id );

        $access_token = $this->get_oauth2_token( $force );
        if ( $access_token == false ) {
            $order->add_order_note( 'INFast API: Document not created, check INFast settings if your Client ID and Client secret are valid' );
            error_log( 'INFast API: Document not created, invalid client ID and/or client secret' );
            return false;
        }

        $data = $this->create_document_prepare_data( $order_id, $customer_id );

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => json_encode( $data ),
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_post( INFAST_API_URL . 'api/v1/documents', $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401) { // access token is expired
            if(!$force) {
                return $this->create_document( $order_id, $customer_id, true );
            }

            $order->add_order_note( 'INFast API: Document created error: authentication failed');
            error_log( 'INFast API: Document created error: authentication failed');
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            $order->add_order_note( 'INFast API: Document created error:' . $error_message );
            error_log( 'INFast API: Document created error:' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $document_id = $response['_id'];
            $order->add_order_note( 'INFast API: Document created ' . $document_id );
            return $document_id;            
        }

    }

    /**
     * Fill an array with all data needed to call the API to create a document
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/documents/createdocument
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to create the invoice on INFast
     * @param      string    $customer_id       INFast customer ID previously created
     */
    private function create_document_prepare_data( $order_id, $customer_id ) {

        $order = wc_get_order( $order_id );

        $data = array();
        $data['type'] = 'INVOICE';
        // $data['status'] = 'ACCEPTED';
        $data['customerId'] = $customer_id;
        $data['refInt'] = strval( $order_id );
        $data['emitDate'] = date( DATE_ISO8601, strtotime('today'));
        $data['dueDate'] = date( DATE_ISO8601, strtotime('today'));
        $data['paymentMethod'] = 'OTHER'; // We use 'OTHER' because a lot of different gateway ID are existing. Gateway ID is specified in line below
        $data['paymentMethodInfo'] = $order->get_payment_method();

        $tax = new WC_Tax();
        $data['lines'] = array();

        foreach ( $order->get_items() as $item_id => $item ) {
            
            $product = wc_get_product( $item->get_product_id() );
            $infast_product_id = get_post_meta( $product->get_id(), '_infast_product_id', true );

            if ( ! $infast_product_id ) {
                $infast_product_id = NULL;
                $infast_product_id = $this->create_product( $product->get_id(), $infast_product_id );
            }

            if ( wc_tax_enabled() ) {
                $taxes = $tax->get_rates( $product->get_tax_class() );
                $rates = array_shift( $taxes );
                $item_rate = array_shift( $rates );
            } else {
                $item_rate = 0;
            }

            $price_with_discount = floatval( $item->get_total() );
            $price_without_discount = floatval( $item->get_subtotal() );
            if ( $price_with_discount == $price_without_discount )
                $discount_percent = 0;
            else
                $discount_percent = 100 - ( ( 100 * $price_with_discount ) / $price_without_discount );

            $data['lines'][] = array(
                'lineType' => 'product',
                'productId' => $infast_product_id,
                'quantity' => $item->get_quantity(),
                'vat' => $item_rate, // VAT rate (ex : 20 for 20% VAT rate)
                'description' => $product->get_short_description(),
                'price' => $item->get_subtotal() / $item->get_quantity(), // Unit price excluding taxes
                'discount'  => $discount_percent,
            );

        }

        foreach( $order->get_items( 'fee' ) as $item_id => $item ) {

            if ( wc_tax_enabled() ) {
                $taxes = $tax->get_rates( $item->get_tax_class() );
                $rates = array_shift( $taxes );
                if ( $rates == NULL  || $item->get_total_tax() == "0" )
                    $item_rate = 0.00;
                else
                    $item_rate = array_shift( $rates );
            } else {
                $item_rate = 0;
            }

            $data['lines'][] = array(
                'lineType' => 'product',
                'name' => $item->get_name(),
                'price' => floatval( $item->get_total() ),
                'vat' => floatval( $item_rate ),
                'quantity' => 1,
                'isService' => false,
            );
        }

        foreach( $order->get_items( 'shipping' ) as $item_id => $item ) {

            $method_id = $item['method_id'];
            $instance_id = $item['instance_id'];
            $data_shipping = get_option( 'woocommerce_' . $method_id . '_' . $instance_id . '_settings' );
            $infast_shipping_id = $data_shipping['infast_shipping_id'];
            if ( empty( $infast_shipping_id ) ) {
                $infast_shipping_id = NULL;
                $infast_shipping_id = $this->create_product_shipping( $instance_id, $method_id, $item, $infast_shipping_id );
            }

            if ( wc_tax_enabled() ) {
                $taxes = $tax->get_rates( $item->get_tax_class() );
                $rates = array_shift( $taxes );
                if ( $rates == NULL || $item->get_total_tax() == "0" )
                    $item_rate = 0.00;
                else
                    $item_rate = array_shift( $rates );
            } else {
                $item_rate = 0;
            }

            $data['lines'][] = array(
                'lineType' => 'product',
                'productId' => $infast_shipping_id,
                'price' => floatval( $item->get_total() ), // force use WP value, shipping method is not updated on INFast side
                'vat' => floatval( $item_rate ),  // force use WP value, shipping method is not updated on INFast side
                'quantity' => 1,
                'isService' => true,
            );

        }

        // $data['discount']['type'] = 'CASH';
        // $data['discount']['amount'] = floatval( $order->get_discount_total() );

        return $data;

    }

    /**
     * Create/Update a product on INFast
     *
     * @since    1.0.0
     * @param      int    $product_id       WooCommerce product ID
     * @param      int    $infast_product_id       INFast product ID you want to update, use NULL to create
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    public function create_product( $product_id, $infast_product_id = NULL, $force = false ) {

        $access_token = $this->get_oauth2_token( $force );
        if ( $access_token == false ) {
            error_log( 'INFast API: Product not created/updated, invalid client ID and/or client secret' );
            return false;
        }

        if ( $infast_product_id == NULL ) {
            $request_url = INFAST_API_URL . 'api/v1/products';
            $request_type = 'POST';
        } else {
            $request_url = INFAST_API_URL . 'api/v1/products/' . $infast_product_id;
            $request_type = 'PATCH';
        }

        $data = $this->create_product_prepare_data( $product_id );

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => json_encode( $data ),
            'method'      => $request_type,
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_request( $request_url, $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->create_product( $product_id, true );
            }

            error_log( 'INFast API: Shipping created error: Authentication failed' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            error_log( 'INFast API: Shipping created error:' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $infast_product_id = $response['_id'];
            update_post_meta( $product_id, '_infast_product_id', $infast_product_id );
            return $infast_product_id;        
        }

    }

    /**
     * Fill an array with all data needed to call the API to create/update a product
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/products/createproduct
     *
     * @since    1.0.0
     * @param      int    $product       WooCommerce product ID
     */
    private function create_product_prepare_data( $product_id ) {

        $product = wc_get_product( $product_id );

        if ( wc_tax_enabled() ) {
            $tax = new WC_Tax();
            $taxes = $tax->get_rates( $product->get_tax_class() );
            $rates = array_shift( $taxes );
            if ( $rates == NULL)
                $product_rate = 0.00;
            else
                $product_rate = array_shift( $rates );
        } else {
            $product_rate = 0;
        }

        $data = array();

        $data['name'] = $product->get_name();
        $data['price'] = floatval( wc_get_price_excluding_tax( $product ) );
        $data['vat'] = $product_rate;
        $data['reference'] = $product->get_sku() ? $product->get_sku() : strval( $product->get_id() );
        $data['description'] = $product->get_short_description();
        $data['isService'] = false;

        return $data;

    }

    /**
     * Create/Update a product (shipping) on INFast
     *
     * @since    1.0.0
     * @param      int    $shipping_id       WooCommerce shipping ID
     * @param      int    $method_id       WooCommerce shipping method ID
     * @param      WC_Order_Item    $item       Order item used to compute VAT
     * @param      int    $infast_product_id       INFast product ID you want to update, use NULL to create
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    public function create_product_shipping( $shipping_id, $method_id, $item, $infast_product_id = NULL, $force = false ) {

        $access_token = $this->get_oauth2_token( $force );
        if ( $access_token == false ) {
            error_log( 'INFast API: Shipping not created/updated, invalid client ID and/or client secret' );
            return false;
        }

        if ( $infast_product_id == NULL ) {
            $request_url = INFAST_API_URL . 'api/v1/products';
            $request_type = 'POST';
        } else {
            $request_url = INFAST_API_URL . 'api/v1/products/' . $infast_product_id;
            $request_type = 'PATCH';
        }

        $data = $this->create_product_prepare_data_shipping( $shipping_id, $item );

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => json_encode( $data ),
            'headers'     => $headers,
            'method'      => $request_type,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_request( $request_url, $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->create_product_shipping( $shipping_id, $method_id, $item, $infast_product_id, true );
            }
            
            error_log( 'INFast API: Product created error: Athentication failed' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            error_log( 'INFast API: Product created error:' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $infast_shipping_id = $response['_id'];

            $data_shipping = get_option( 'woocommerce_' . $method_id . '_' . $shipping_id . '_settings' );
            $data_shipping['infast_shipping_id'] = $infast_shipping_id;
            update_option( 'woocommerce_' . $method_id . '_' . $shipping_id . '_settings', $data_shipping );
            return $infast_shipping_id;         
        }

    }

    /**
     * Fill an array with all data needed to call the API to create/update a product (shipping)
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/products/createproduct
     *
     * @since    1.0.0
     * @param      int    $product       WooCommerce product ID
     */
    private function create_product_prepare_data_shipping( $shipping_id, $item ) {

        $data = array();

        $zones = WC_Shipping_Zones::get_zones();
        $shipping_methods = array_map(function($zone) {
            return $zone['shipping_methods'];
        }, $zones);
        $basic_zone_methods = (new WC_Shipping_Zone(0))->get_shipping_methods();
        array_push( $shipping_methods, $basic_zone_methods );

        foreach ( $shipping_methods as $shipping_method ) {
            foreach ( $shipping_method as $shipping_idx => $shipping ) {

                if ( $shipping_idx == $shipping_id ) {

                    if ( wc_tax_enabled() ) {
                        $tax = new WC_Tax();
                        $taxes = $tax->get_rates( $item->get_tax_class() );
                        $rates = array_shift( $taxes );
                        if ( $rates == NULL  || $item->get_total_tax() == "0" )
                            $item_rate = 0.00;
                        else
                            $item_rate = array_shift( $rates );
                    } else {
                        $product_rate = 0;
                    }

                    $data['name'] = $shipping->title;
                    $data['price'] = floatval( $shipping->cost );
                    $data['vat'] = $item_rate;
                    $data['isService'] = true;
                    return $data;

                }
            }
        }

        return $data;

    }

    /**
     * Create a customer on INFast
     *
     * @since    1.0.0
     * @param      int    $user_id      User ID used to create the customer on INFast
     * @param      int    $order_id       WooCommerce order ID used to create the customer on INFast
     * @param      int    $infast_customer_id       INFast customer ID
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    public function create_customer( $user_id, $order_id = NULL, $infast_customer_id = NULL, $force = false ) {

        if ( $order_id != NULL )
            $order = wc_get_order( $order_id );

        $access_token = $this->get_oauth2_token( $force );
        if ( $access_token == false ) {
            if ( $order )
                $order->add_order_note( 'INFast API: Document not created, check INFast settings if your Client ID and Client secret are valid' );
            error_log( 'INFast API: Document not created, invalid client ID and/or client secret' );
            return false;
        }

        $data = $this->create_customer_prepare_data( $user_id );

        if ( $infast_customer_id == NULL ) {
            $request_url = INFAST_API_URL . 'api/v1/customers';
            $request_type = 'POST';
        } else {
            $request_url = INFAST_API_URL . 'api/v1/customers/' . $infast_customer_id;
            $request_type = 'PATCH';
        }

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => json_encode( $data ),
            'headers'     => $headers,
            'method'      => $request_type,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_post( $request_url, $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->create_customer( $user_id, $order_id, $infast_customer_id, true );
            }

            if ( $order ) {
                $order->add_order_note( 'INFast API: Customer created error: Authentication failed' );
            }
            error_log( 'INFast API: Customer created error: Authentication failed' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            if ( $order )
                $order->add_order_note( 'INFast API: Customer created error:' . $error_message );
            error_log( 'INFast API: Customer created error:' . $err );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $customer_id = $response['_id'];
            if ( $order )
                $order->add_order_note( 'INFast API: Customer created ' . $customer_id );
            update_user_meta( $user_id, '_infast_customer_id', $customer_id );
            return $customer_id;     
        }

    }

    /**
     * Fill an array with all data needed to call the API to create a customer
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/documents/createcustomer
     *
     * @since    1.0.0
     * @param      int    $user_id       User ID
     */
    private function create_customer_prepare_data( $user_id ) {

        $data = array();

        $data['name'] = get_user_meta( $user_id, 'billing_first_name', true ) . ' ' . get_user_meta( $user_id, 'billing_last_name', true );
        $data['address'] = array();
        $data['address']['street'] = get_user_meta( $user_id, 'billing_address_1', true );
        $data['address']['postalCode'] = get_user_meta( $user_id, 'billing_postcode', true );
        $data['address']['city'] = get_user_meta( $user_id, 'billing_city', true );
        $data['address']['country'] = WC()->countries->countries[ get_user_meta( $user_id, 'billing_country', true ) ];
        $data['email'] = get_user_meta( $user_id, 'billing_email', true );
        $data['phone'] = get_user_meta( $user_id, 'billing_phone', true );
        $data['delivery'] = array();

        $shipping_first_name = get_user_meta( $user_id, 'shipping_first_name', true );
        $shipping_last_name = get_user_meta( $user_id, 'shipping_first_name', true );
        if ( $shipping_first_name && $shipping_last_name )
            $data['delivery']['name'] = get_user_meta( $user_id, 'shipping_first_name', true ) . ' ' . get_user_meta( $user_id, 'shipping_last_name', true );

        $shipping_street = get_user_meta( $user_id, 'shipping_address_1', true );
        $shipping_country = get_user_meta( $user_id, 'shipping_country', true );
        $data['delivery']['address'] = array();
        $data['delivery']['address']['street'] = $shipping_street;
        $data['delivery']['address']['postalCode'] = get_user_meta( $user_id, 'shipping_postcode', true );
        $data['delivery']['address']['city'] = get_user_meta( $user_id, 'shipping_city', true );
        if ( WC()->countries->countries[ $shipping_country ] != NULL )
            $data['delivery']['address']['country'] = WC()->countries->countries[ $shipping_country ];
        if ( $shipping_street && $shipping_country ) {
            $data['useDelivery'] = true;
            $data['sendToDelivery'] = true;
        }

        $data['outsideEU'] = $this->is_outside_EU( get_user_meta( $user_id, 'billing_country', true ) );

        return $data;

    }

    /**
     * Add a payment on INFast
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to get payment infos
     * @param      int    $document_id       INFast document ID used to add a payment on INFast
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    private function add_document_payment( $order_id, $document_id, $force = false ) {

        $order = wc_get_order( $order_id );

        $access_token = $this->get_oauth2_token( $force );
        if ( $access_token == false ) {
            $order->add_order_note( 'INFast API: Document payment not created, check INFast settings if your Client ID and Client secret are valid' );
            error_log( 'INFast API: Document payment not created, invalid client ID and/or client secret' );
            return false;
        }

        $data = $this->add_document_payment_prepare_data( $order_id );

        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => '',
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_post( INFAST_API_URL . 'api/v1/documents/' . $document_id . '/payment', $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->add_document_payment( $order_id, $document_id, true );
            }

            $order->add_order_note( 'INFast API: Add payment error: Authentication failed' );
            error_log( 'INFast API: Add payment error: Authentication failed');
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            $order->add_order_note( 'INFast API: Add payment error:' . $error_message );
            error_log( 'INFast API: Add payment error:' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $payment_id = $response['_id'];
            $order->add_order_note( 'INFast API: Payment added ' . $payment_id );
            return $payment_id;          
        }

    }

    /**
     * Fill an array with all data needed to call the API to add a payment
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/documents/addpaymentoninvoice
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to create the invoice on INFast
     */
    private function add_document_payment_prepare_data( $order_id ) {

        $order = wc_get_order( $order_id );

        $data = array();
        $data['payment'] = array();
        $data['payment']['method'] = 'OTHER';
        $data['payment']['info'] = $order->get_payment_method();  

        return $data;

    }

    /**
     * Send INFast document by email
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID
     * @param      int    $document_id       INFast document ID
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    private function send_document_email( $order_id, $document_id, $force = false ) {

        $order = wc_get_order( $order_id );

        $access_token = $this->get_oauth2_token( $force );
        if ( $access_token == false ) {
            $order->add_order_note( 'INFast API: Document not sent by email, check INFast settings if your Client ID and Client secret are valid' );
            error_log( 'INFast API: Document not sent by email, invalid client ID and/or client secret' );
            return false;
        }

        $data = $this->add_send_document_email_prepare_data( $order_id );
        if ( count ( $data ) == 0 )
            $data = new stdClass();
        
        $headers = array(
            'authorization' =>  'Bearer ' . $access_token,
            'content-type'  =>  'application/json',
        );

        $args = array(
            'body'        => json_encode( $data ),
            'headers'     => $headers,
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
        );

        $resp = wp_remote_post( INFAST_API_URL . 'api/v1/documents/' . $document_id . '/email', $args );
        $http_code = wp_remote_retrieve_response_code( $resp );

        if ( $http_code == 401 ) { // access token is expired
            if(!$force) {
                return $this->send_document_email( $order_id, $document_id, true );
            }

            $order->add_order_note( 'INFast API: Send document by email error: Authentication failed' );
            error_log( 'INFast API: Document sent by email error: Authentication failed' );
            return false;
        }

        if ( is_wp_error( $resp ) ) {
            $error_message = $resp->get_error_message();
            $order->add_order_note( 'INFast API: Send document by email error:' . $error_message );
            error_log( 'INFast API: Document sent by email error:' . $error_message );
            return false;
        } else {
            $response = json_decode( $resp['body'], true );
            $email_id = $response['_id'];
            $order->add_order_note( 'INFast API: Document sent by email ' . $email_id );
            return $email_id;           
        }

    }

    /**
     * Fill an array with all data needed to call the API to send document by email
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/documents/sendemail
     *
     * @since    1.0.0
     */
    private function add_send_document_email_prepare_data() {

        $cc_setting = get_option( 'infast_woocommerce' )['cc_email'];

        $data = array();
        if ( ! empty( $cc_setting ) && $cc_setting != NULL )
            $data['cc'] = $cc_setting;

        return $data;

    }

    /**
     * Check if a country is outside European Union. Can be used for VAT purpose.
     *
     * @since    1.0.0
     * @param      string    $country_code       Country code to check (same format that the one used by WooCommerce)
     */
    private function is_outside_EU( $country_code ) {

        $eu_country_codes = array(
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'EL',
            'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV',
            'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
        );

        return ( ! in_array( $country_code , $eu_country_codes ) );

    }

}