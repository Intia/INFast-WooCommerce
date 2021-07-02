<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.vangus-agency.com
 * @since      1.0.0
 *
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Infast_Woocommerce
 * @subpackage Infast_Woocommerce/includes
 * @author     Vangus <hello@vangus-agency.com>
 */
class Infast_Woocommerce_Api {

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

    public function generate_invoice( $order_id ) {

        $customer_id = $this->create_customer( $order_id );
        if ( $customer_id )
            $this->create_document( $order_id, $customer_id );
    }

    private function create_document( $order_id, $customer_id, $force = false ) {

        $access_token = $this->get_oauth2_token( $force );

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
            $this->create_document( $order_id, $customer_id, true );
            return;
        }

        $order = wc_get_order( $order_id );
        if ( $err ) {
            $order->add_order_note( 'INFast API: Document created error:' . $err );
        } else {
            $order->add_order_note( 'INFast API: Document created' );
            $order->add_order_note( $response );
        }

    }

    /*
     * Fill an array with all data needed to call the API to create a document
     * Full parameters list: https://infast.docs.stoplight.io/api-reference/documents/createdocument
    */
    private function create_document_prepare_data( $order_id, $customer_id ) {

        $order = wc_get_order( $order_id );

        $data = array();
        $data['type'] = 'INVOICE';
        $data['status'] = 'ACCEPTED';
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

    private function create_customer( $order_id, $force = false ) {

        $access_token = $this->get_oauth2_token( $force );

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
            $this->create_customer( $order_id, true );
            return;
        }

        $order = wc_get_order( $order_id );
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

    /*
     * Fill an array with all data needed to call the API to create a document
     * https://infast.docs.stoplight.io/api-reference/customers/createcustomer
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

    private function is_outside_EU( $country_code ) {

        $eu_country_codes = array(
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'EL',
            'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV',
            'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
        );

        return ( ! in_array( $country_code , $eu_country_codes ) );

    }

    

}