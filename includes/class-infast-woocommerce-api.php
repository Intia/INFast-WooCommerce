<?php

/**
 * The file that defines the class interacting with INFast API
 *
 *
 * @link       https://www.vangus-agency.com
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
 * @author     Vangus <hello@vangus-agency.com>
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

        $curl = curl_init( $url );
        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );

        $headers = array(
           'Content-Type: application/x-www-form-urlencoded',
        );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );

        $options = get_option( 'infast_woocommerce' );
        $data = 'client_id=' . $options['client_id'];
        $data .= '&client_secret=' . $options['client_secret'];
        $data .= '&grant_type=client_credentials&scope=write';

        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );

        $resp = curl_exec( $curl );
        if ( curl_errno( $curl ) ) {

            error_log( 'INFast WooCommerce - Get OAuth2 access token : ' . curl_error( $curl ) );
            update_option( 'infast_access_token', false );
            curl_close( $curl );
            return false;

        } else {

            $resp = json_decode( $resp, true );
            if ( is_array( $resp ) && array_key_exists( 'access_token', $resp ) ){
                $access_token = $resp['access_token'];
                update_option( 'infast_access_token', $access_token );
                curl_close( $curl );
                return $access_token;                
            } else {
                error_log( 'INFast WooCommerce - Get OAuth2 access token : ' . $resp );
                update_option( 'infast_access_token', false );
                curl_close( $curl );
                return false;
            }

        }

    }

    /**
     * Manage to process to create a new invoice on INFast from a WooCommercer order ID, including creating a new customer on INFast first
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to create the invoice on INFast
     */
    public function generate_invoice( $order_id ) {

        $customer_id = $this->create_customer( $order_id );
        if ( $customer_id ) {
            $document_id = $this->create_document( $order_id, $customer_id );
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
            return false;
        }

        $data = $this->create_document_prepare_data( $order_id, $customer_id );

        $curl = curl_init();

        curl_setopt_array( $curl, array(
            CURLOPT_URL => INFAST_API_URL . 'api/v1/documents',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode( $data ),
            CURLOPT_HTTPHEADER => array(
                'authorization: Bearer ' . $access_token,
                'content-type: application/json'
            ),
        ) );

        $response = curl_exec( $curl );
        $err = curl_error( $curl );
        $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        curl_close( $curl );

        if ( $code == 401) { // access_token is expired
            return $this->create_document( $order_id, $customer_id, true );
            
        }

        if ( $err ) {
            $order->add_order_note( 'INFast API: Document created error:' . $err );
            return false;
        } else {
            $response = json_decode( $response, true );
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

            $taxes = $tax->get_rates( $product->get_tax_class() );
            $rates = array_shift( $taxes );
            $item_rate = round( array_shift( $rates ) );

            $data['lines'][] = array(
                'lineType' => 'product',
                'name' => $item->get_name(),
                'price' => floatval( $product->get_price() ),
                'vat' => $item_rate,
                'reference' => $product->get_sku() ? $product->get_sku() : strval( $product->get_id() ),
                'description' => $product->get_short_description(),
                'quantity' => $item->get_quantity(),
                'amount' => $item->get_total() - $item->get_total_tax(),
                'vatPart' => floatval( $item->get_total_tax() ),
                'amountVat' => floatval( $item->get_total() ),
            );
        }

        foreach( $order->get_items( 'fee' ) as $item_id => $item ) {

            $taxes = $tax->get_rates( $item->get_tax_class() );
            $rates = array_shift( $taxes );
            $item_rate = round( array_shift( $rates ) );

            $data['lines'][] = array(
                'lineType' => 'product',
                'name' => $item->get_name(),
                'price' => floatval( $item->get_total() ),
                'vat' => floatval( $item_rate ),
                'quantity' => 1,
            );
        }

        foreach( $order->get_items( 'shipping' ) as $item_id => $item ) {

            $taxes = $tax->get_rates( $item->get_tax_class() );
            $rates = array_shift( $taxes );
            $item_rate = round( array_shift( $rates ) );

            $data['lines'][] = array(
                'lineType' => 'product',
                'name' => $item->get_method_title(),
                'price' => floatval( $item->get_total() ),
                'vat' => floatval( $item_rate ),
                'quantity' => 1,
            );
        }

        $data['discount']['type'] = 'CASH';
        $data['discount']['amount'] = floatval( $order->get_discount_total() ) - floatval( $order->get_discount_tax() );

        return $data;

    }

    /**
     * Create a customer on INFast
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to create the customer on INFast
     * @param      bool    $force       Force OAuth2 token regeneration
     */
    private function create_customer( $order_id, $force = false ) {

        $order = wc_get_order( $order_id );

        $access_token = $this->get_oauth2_token( $force );
        if ( $access_token == false ) {
            $order->add_order_note( 'INFast API: Document not created, check INFast settings if your Client ID and Client secret are valid' );
            return false;
        }

        $data = $this->create_customer_prepare_data( $order_id );

        $curl = curl_init();

        curl_setopt_array( $curl, array(
            CURLOPT_URL => INFAST_API_URL . 'api/v1/customers',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode( $data ),
            CURLOPT_HTTPHEADER => array(
                'authorization: Bearer ' . $access_token,
                'content-type: application/json'
            ),
        ) );

        $response = curl_exec( $curl );
        $err = curl_error( $curl );
        $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        curl_close( $curl );

        if ( $code == 401) { // access_token is expired
            return $this->create_customer( $order_id, true );
        }

        if ($err) {
            $order->add_order_note( 'INFast API: Customer created error:' . $err );
            return false;
        } else {
            $response = json_decode( $response, true );
            $customer_id = $response['_id'];
            $order->add_order_note( 'INFast API: Customer created ' . $customer_id );
            return $customer_id;
        }

    }

    /**
     * Fill an array with all data needed to call the API to create a customer
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/documents/createcustomer
     *
     * @since    1.0.0
     * @param      int    $order_id       WooCommerce order ID used to create the invoice on INFast
     */
    private function create_customer_prepare_data( $order_id ) {

        $order = wc_get_order( $order_id );

        $data = array();

        $data['name'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $data['address'] = array();
        $data['address']['street'] = $order->get_billing_address_1();
        $data['address']['postalCode'] = $order->get_billing_postcode();
        $data['address']['city'] = $order->get_billing_city();
        $data['address']['country'] = WC()->countries->countries[ $order->get_billing_country() ];
        $data['email'] = ($a = get_userdata( $order->get_user_id() ) ) ? $a->user_email : '';
        $data['phone'] = $order->get_billing_phone();
        $data['delivery'] = array();
        $data['delivery']['address'] = array();
        $data['delivery']['address']['street'] = $order->get_shipping_address_1();
        $data['delivery']['address']['postalCode'] = $order->get_shipping_postcode();
        $data['delivery']['address']['city'] = $order->get_shipping_city();
        $data['delivery']['address']['country'] = WC()->countries->countries[ $order->get_shipping_country() ];
        $data['delivery']['name'] = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
        $data['outsideEU'] = $this->is_outside_EU( $order->get_billing_country() );

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
            return false;
        }

        $data = $this->add_document_payment_prepare_data( $order_id );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => INFAST_API_URL . 'api/v1/documents/' . $document_id . '/payment',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'authorization: Bearer ' . $access_token,
                'content-type: application/json'
            ),
        ));

        $response = curl_exec( $curl );
        $err = curl_error( $curl );
        $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        curl_close( $curl );

        if ( $code == 401) { // access_token is expired
            return $this->add_document_payment( $order_id, $document_id, true );
        }

        if ($err) {
            $order->add_order_note( 'INFast API: Add payment error:' . $err );
            return false;
        } else {
            $response = json_decode( $response, true );
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
            return false;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => INFAST_API_URL . 'api/v1/documents/' . $document_id . '/email',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'authorization: Bearer ' . $access_token,
                'content-type: application/json'
            ),
        ));

        $response = curl_exec( $curl );
        $err = curl_error( $curl );
        $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        curl_close( $curl );

        if ( $code == 401) { // access_token is expired
            return $this->send_document_email( $order_id, $document_id, true );
        }

        if ($err) {
            $order->add_order_note( 'INFast API: Send document by email error:' . $err );
            return false;
        } else {
            $response = json_decode( $response, true );
            $email_id = $response['_id'];
            $order->add_order_note( 'INFast API: Document sent by email ' . $email_id );
            return $email_id;
        }

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