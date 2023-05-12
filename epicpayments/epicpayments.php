<?php
/*
  Plugin Name: Payment gateway via EpicPayments for WooCommerce
  Plugin URI: https://epicfrog.com/
  Description: Extends WooCommerce with a <a href="https://docs.epicfrog.com/plugins/epicpayments/" target="_blank">EpicPayments</a> gateway.
  Version: 1.0
  Author: Epic Frog
  Author URI: https://epicfrog.com/
  Text Domain: epicpay_woocommerce
  Domain Path: /languages
  Requires at least: 4.4
  Tested up to: 6.2
  WC tested up to: 7.5.1
  WC requires at least: 3.2.3
  License: GNU General Public License v3.0
  License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'EPICPAY_DIR', plugin_dir_path( __FILE__ ) );
define( 'EPICPAY_URL', plugin_dir_url( __FILE__ ) );
define( 'EPICPAY_VERSION', '1.0' );

$plugin_version = '1.0.7';
$plugin_release_timestamp = '2023-03-21 16:34';
$plugin_name        = 'Flexible Refund and Return Order for WooCommerce';
$plugin_class_name  = '\WPDesk\WPDeskFRFree\Plugin';
$plugin_text_domain = 'flexible-refund-and-return-order-for-woocommerce';
$product_id         = 'Flexible Refund and Return Order for WooCommerce';
$plugin_file        = __FILE__;
$plugin_dir         = __DIR__;
/** Dummy plugin name and description - for translations only. */
$dummy_name       = esc_html__( 'Flexible Refund and Return Order for WooCommerce', 'flexible-refunds' );
$dummy_desc       = esc_html__( 'The plugin to handle the refund form on My Account and automates the refund process for the WooCommerce store support.' );
$dummy_plugin_uri = esc_html__( 'https://wpde.sk/flexible-refunds-for-woocommerce', 'flexible-refunds' );
$dummy_author_uri = esc_html__( 'https://wpdesk.net/', 'flexible-refunds' );
$dummy_settings   = esc_html__( 'Refund Settings', 'flexible-refunds' );


$requirements = [
	'php'     => '7.3',
	'wp'      => '5.2',
	'plugins' => [
		[
			'name'      => 'woocommerce/woocommerce.php',
			'nice_name' => 'WooCommerce',
		],
	],
];

require __DIR__ . '/vendor_prefixed/wpdesk/wp-plugin-flow-common/src/plugin-init-php52-free.php';
function epicpay_wc_active() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		return true;
	} else {
		return false;
	}
}

/****************/

add_action('admin_post_nopriv_get_typeform_data',  'process_data_form_tyepform_webhook', 10);

function process_data_form_tyepform_webhook() {

   //$request = file_get_contents('php://input'); // get data from webhoook
	
	if($json = json_decode(file_get_contents("php://input"), true)) {
  
    $data = $json;
	} else {
		$data = '$_POST';
	}
	
		
	    

 
	echo "event:" . $data["data"]["attributes"]["event"]."\n";
	echo "type:" . $data["data"]["relationships"]["transaction"]["data"]["type"]."\n";
	
	$type = $data["data"]["attributes"]["event"];
	
	echo "id:" . $data["data"]["relationships"]["transaction"]["data"]["id"]."\n";
	
	echo "type:" . $data["included"][0]["type"]."\n";
	echo "id:" . $data["included"][0]["id"]."\n";
	
	echo "status:" . $data["included"][0]["attributes"]["status"]."\n";
	
	echo "referenceId:" . $data["included"][0]["attributes"]["referenceId"]."\n";
	$ref_id =  $data["included"][0]["attributes"]["referenceId"];
	$id_trans = explode("-", $ref_id);
	$id_trans_view = $id_trans[1];
	echo "ID order: ".$id_trans_view."\n";
	
	echo "processing:" . $data["included"][0]["attributes"]["processing"]["resultCode"]."\n";
	$failed_message =  $data["included"][0]["attributes"]["processing"]["resultCode"];
	//print_r($data);
	
	$ref_id_product = $data["included"][0]["attributes"]["referenceId"];
		
	$data_result = $data["included"][0]["attributes"]["processing"]["resultCode"];
	
	if( !empty($data) ){
		
		if( $type == "charge.processed" ){
		if ( $data_result == "success" ){

			global $wpdb,$woocommerce, $post;
			$get_object = $re;

			$rod = $id_trans_view;

			$order_id_res = $rod;
			$orderDetail_res = new WC_Order( $order_id_res );

			$orderDetail_res->update_status("wc-processing", 'Processing', TRUE);
			}
		}elseif( $type == "charge.failed" ){
			global $wpdb,$woocommerce, $post;
			$get_object = $re;

			$rod = $id_trans_view;

			$order_id_res = $rod;
			$orderDetail_res = new WC_Order( $order_id_res );

			$orderDetail_res->update_status("wc-cancelled", 'Cancelled', TRUE);
			// The text for the note
			//$note = __("This is my note's");
			// Add the note
			$orderDetail_res->add_order_note( "Reason: ".$failed_message );
			
			}
		}
		
		

		
	
}

// define the http_request_host_is_external callback 
function filter_http_request_host_is_external( $isexternal, $int1, $int2 ) { 
    // make filter magic happen here... 
    return true; 
}; 
         
// add the filter 
add_filter( 'http_request_host_is_external', 'filter_http_request_host_is_external', 10, 3 );


/***************/


add_action ('wp_loaded', 'pay_gate');
		function pay_gate() {
	
		   if ( $_GET['referenceId'])  {
			   $redID = $_GET['referenceId'];
			   $string_array = explode("-", $redID);
				$ord_ID = $string_array[1]; // строка после равно
			   
				global $woocommerce, $post;
				$order_id = $ord_ID;
				$orderDetail = new WC_Order( $order_id );
			   
				//$orderDetail->update_status("wc-processing", 'Processing', TRUE);
				//
			    $checkout_url = wc_get_page_permalink( 'checkout' );
			    $test_order_key = $orderDetail->get_order_key();
			    $chk_url = get_site_url().'/checkout/order-received/'.$order_id.'/?key='.$test_order_key;
				   
			    if( $orderDetail->has_status( 'pending' ) ) {
					$orderDetail->update_status( 'processing' );
					wp_safe_redirect(  $chk_url );
				   exit;
				}
			    if( $orderDetail->has_status( 'processing' ) ) {
					wp_safe_redirect(  $chk_url );
				   exit;
				}
			    if( $orderDetail->has_status( 'completed' ) ) {
					wp_safe_redirect(  $chk_url );
				   exit;
				}
			 
			    if( $orderDetail->has_status( 'failed' ) ) {
					wp_safe_redirect(  get_site_url() );
			   		exit;
				}
			    
			   if( $orderDetail->has_status( 'cancelled' ) ) {
					wp_safe_redirect(  get_site_url() );
			   		exit;
				}
			   if( $orderDetail->has_status( 'on-hold' ) ) {
				    $orderDetail->update_status( 'processing' );
					wp_safe_redirect(  $chk_url );
			   		exit;
				}
			    if( $orderDetail->has_status( 'refunded' ) ) {
					wp_safe_redirect(  get_site_url() );
			   		exit;
				}
			   
			 }
		}

add_action( 'plugins_loaded', 'woocommerce_epicpay_init', 0 );
add_action( 'plugins_loaded', 'woocommerce_epicpay_textdomain' );

function woocommerce_epicpay_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}
	//Add the gateway to woocommerce
	add_filter( 'woocommerce_payment_gateways', 'add_epicpay_gateway' );
	function add_epicpay_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Epicpay';
		return $methods;
	}
	class EpicPay {
    private $MerchantLongId;  
    private $token;
    
    private $apiGatewayUrl = 'https://api.exactly.com/api/v1/transactions';
    function __construct($MerchantLongId, $TerminalKey) {
       
        $this->MerchantLongId = $MerchantLongId;        
        $this->TerminalKey = $TerminalKey;
    }
    private function request($method = 'POST', $url, $json = false) {
        $curl = curl_init();
        $header = [
            "Content-Type: application/vnd.api+json",
            "cache-control: no-cache"
        ];
     
            $header[] = "Authorization: Api-Key ". $this->TerminalKey;
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $json?json_encode($json):"",
            CURLOPT_HTTPHEADER => $header,
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
       
        if(!empty($this->token)){
            $this->token = '';
        }
        
        return $response;
    }
    private function login() {
        $params = [
            "projectId" => $this->MerchantLongId,
        ];
        $response_json = $this->request('POST', $this->apiGatewayUrl.'',$params);
        $response = json_decode($response_json);
    }
    function transaction($order_id,$amount,$currency,$email, $merchantID, $return_url, $referenceID) {
        try{
            $this->login();
            $gen_id = rand(5, 99999);
            $price_show = sprintf('%02.2f', $amount);
            $params = [
            'data' => [
                'attributes' =>
                [
                    'projectId' => $merchantID,
                    'paymentMethod' => 'card',
                    'currency' => $currency,
                    'amount' => $price_show, 
                    'referenceId'=> $referenceID,
                    //'originalReferenceId' => $referenceID,
                    'returnUrl'=> $return_url,
                    'email' => $email,
                ],
                'type' => 'charge',
                ],
            ];
            
            $response_json = $this->request('POST', $this->apiGatewayUrl.'', $params);
           
            return $response_json;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
       }
    }
}

class EpicPay_refund {
    private $MerchantLongId;  
    private $token;
    
    private $apiGatewayUrl = 'https://api.exactly.com/api/v1/transactions';
    function __construct($MerchantLongId, $TerminalKey) {
       
        $this->MerchantLongId = $MerchantLongId;        
        $this->TerminalKey = $TerminalKey;
    }
    private function request($method = 'POST', $url, $json = false) {
        $curl = curl_init();
        $header = [
            "Content-Type: application/vnd.api+json",
            "cache-control: no-cache"
        ];
     
            $header[] = "Authorization: Api-Key ". $this->TerminalKey;
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $json?json_encode($json):"",
            CURLOPT_HTTPHEADER => $header,
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
       
        if(!empty($this->token)){
            $this->token = '';
        }
        
        return $response;
    }
    private function login() {
        $params = [
            "projectId" => $this->MerchantLongId,
        ];
        $response_json = $this->request('POST', $this->apiGatewayUrl.'',$params);
        $response = json_decode($response_json);
    }
    function transaction($order_id,$amount,$currency,$email, $merchantID, $return_url, $referenceID) {
        try{
            $this->login();
            $gen_id = rand(5, 99999);
            $price_show = sprintf('%02.2f', $amount);
            $params = [
            'data' => [
                'attributes' =>
                [
                    'projectId' => $merchantID,
                    'paymentMethod' => 'card',
                    'currency' => $currency,
                    'amount' => $price_show, 
                    //'referenceId'=> $referenceID,
                    'originalReferenceId' => $referenceID,
                    'returnUrl'=> $return_url,
                    'email' => $email,
                ],
                'type' => 'refund',
                ],
            ];
            
            $response_json = $this->request('POST', $this->apiGatewayUrl.'', $params);
           
            return $response_json;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
       }
    }
}

	/**
	 * @property string testmode
	 * @property string merchantid
	 * @property string paymentgatewayid
	 * @property string secretkey
	 * @property string langpaymentpage
	 * @property string returnUrl_API
	 * @property string cancelurl
	 * @property string errorurl
	 * @property string notification_email
	 */
	class WC_Gateway_Epicpay extends WC_Payment_Gateway {
		//const EPICPAY_ENDPOINT_SANDBOX = '';
		//const EPICPAY_ENDPOINT_LIVE = '';
		public function __construct() {
			$this->id                 = 'epicpay';
			$this->icon               = EPICPAY_URL . '/cards.png';
			$this->has_fields         = false;
			$this->method_title       =  __('EpicPayments', 'epicpay_woocommerce');
			$this->method_description = __('EpicPayments Secure Payment Page enables merchants to sell products securely on the web with minimal integration effort', 'epicpay_woocommerce');
			// What methods do support plugin
			$this->supports = array(
				'products',
				'refunds',
			);
			// Load the form fields
			$this->init_form_fields();
			$this->init_settings();
			// Get setting values
			$this->enabled            = $this->get_option( 'enabled' );
			$this->title              = $this->get_option( 'title' );
			$this->description        = $this->get_option( 'description' );
			$this->testmode           = $this->get_option( 'testmode' );
			$this->merchantid         = $this->get_option( 'merchantid' );
			$this->paymentgatewayid   = $this->get_option( 'paymentgatewayid' );
			$this->secretkey          = $this->get_option( 'secretkey' );
			$this->langpaymentpage    = $this->get_option( 'langpaymentpage' );
			$this->receipttext 		  = $this->get_option( 'receipttext', __('Thank you - your order is now pending payment. We are now redirecting you to Epicpay to make payment.', 'epicpay_woocommerce') );
			$this->redirecttext 	  = $this->get_option( 'redirecttext',  __('Thank you for your order. We are now redirecting you to Epicpay to make payment.', 'epicpay_woocommerce') );
			$this->returnUrl_API         = get_site_url();/*$this->get_option( 'returnUrl_API' );*/
			$this->cancelurl          = $this->get_option( 'cancelurl' );
			$this->errorurl           = $this->get_option( 'errorurl' );
			$this->notification_email = $this->get_option( 'notification_email' );
			$this->TotalLineItem      = 'yes' === $this->get_option( 'TotalLineItem', 'no' );
			// Filters
			add_filter( 'wcml_gateway_text_keys_to_translate', array( $this, 'epicpay_text_keys_to_translate' ) );
			// Hooks
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_api_wc_gateway_epicpay', array( $this, 'check_epicpay_response' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'check_epicpay_response' ) );
			add_action( 'before_woocommerce_pay', array( $this, 'checkout_payment_handler'), 9 );
			
			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}
		}
		
		public function admin_options() {
			?>
			<h3> <?php _e( 'EpicPayments', 'epicpay_woocommerce' ); ?></h3>
			<p> <?php _e( 'Pay with your credit card via EpicPayments.', 'epicpay_woocommerce' ); ?></p>
			<?php if ( $this->is_valid_for_use() ) : ?>
				<table class="form-table"><?php $this->generate_settings_html(); ?></table>
			<?php else : ?>
				<div class="inline error"><p><strong><?php _e( 'Gateway Disabled:', 'epicpay_woocommerce' ); ?></strong><?php _e( 'Current Store currency is not valid for epicpay gateway. Must be in ISK, USD, EUR, GBP, DKK, NOK, SEK, CHF, JPY, CAD, HUF', 'epicpay_woocommerce' ); ?></p></div>
				<?php
			endif;
		}
		//Check if this gateway is enabled and available in the user's country
		function is_valid_for_use() {
			if ( ! in_array( get_woocommerce_currency(), array(
				'ISK',
				'USD',
				'EUR',
				'GBP',
				'DKK',
				'NOK',
				'SEK',
				'CHF',
				'JPY',
				'CAD',
				'HUF'
			) )
			) {
				return false;
			}
			return true;
		}
		//Initialize Gateway Settings Form Fields
		function init_form_fields() {
			 echo '<style>#woocommerce_epicpay_notify_URL_API, #woocommerce_epicpay_returnUrl_API { font-weight:bold;max-width: 800px;width: 100%;}</style>';
			$this->form_fields = array(
				'enabled'            => array(
					'title'       => __( 'Enable/Disable', 'epicpay_woocommerce' ),
					'label'       => __( 'EpicPayments', 'epicpay_woocommerce' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title'              => array(
					'title'       =>  __( 'Title', 'epicpay_woocommerce' ),
					'type'        => 'text',
					'description' =>  __( 'This controls the title which the user sees during checkout.', 'epicpay_woocommerce' ),
					'default'     =>  __( 'EpicPay', 'epicpay_woocommerce' )
				),
				'description'        => array(
					'title'       => __( 'Description', 'epicpay_woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'epicpay_woocommerce' ),
					'default'     => __( 'Pay with your credit card via EpicPay.', 'epicpay_woocommerce' )
				),

				'notify_URL_API'           => array(
					'title'       => __( 'Notification Url', 'epicpay_woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Please setup this Url in https://dashboard.exactly.com/ for Webhooks as Event / URI', 'epicpay_woocommerce' ),
					'disabled' => true,
					'style'    => 'background:red',
					'default'     => get_site_url().'/wp-admin/admin-post.php?action=get_typeform_data'
				),
	
				'merchantid'         => array(
					'title'       => __( 'Merchant ID', 'epicpay_woocommerce' ),
					'type'        => 'text',
					'description' =>  __( 'This is the ID supplied by EpicPayments.', 'epicpay_woocommerce' ),
					'default'     => '98cda84a-24d8-4479-8606-c3a47fe80df1'
				),
				'secretkey'          => array(
					'title'       => __( 'Api Key', 'epicpay_woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This is the Secret Key supplied by EpicPayments.', 'epicpay_woocommerce' ),
					'default'     => 'GHDjtBRDO22vgB5X9s6t3iCup2gfw9ahTbC6Sri3sT4SVTtyEbLFNFrbWW4ez2PF'
				),

				'returnUrl_API'         => array(
					'title'       =>  __('Return Page URL(base url)', 'epicpay_woocommerce' ),
					'type'        => 'text',
					'description' =>  __('URL to redirect customer after transaction is completed. Host must be in allow list. Please contact support to add your host.', 'epicpay_woocommerce' ),
					
					'disabled' => true,
					'default'     => get_site_url()
				),

			);
		}
		/**
		 * @param WC_Order $order
		 *
		 * @return false|string
		 */
		function check_hash( $order ) {
			$ipnUrl           = WC()->api_request_url( 'WC_Gateway_Epicpay' );
			$hash             = array();
			$hash[]           = $this->merchantid;
			$hash[]           = esc_url_raw( $this->get_return_url( $order ) );
			$hash[]           = $ipnUrl;
			$hash[]           = 'WC-' . $order->get_id();
			$hash[]           = number_format( $order->get_total(), wc_get_price_decimals(), '.', '' );
			$hash[]           = $order->get_currency();
			$hash = apply_filters( 'epicpay_'.$this->id.'_check_hash', $hash, $order );
			$message          = implode( '|', $hash );
			$CheckHashMessage = utf8_encode( trim( $message ) );
			$Checkhash        = hash_hmac( 'sha256', $CheckHashMessage, $this->secretkey );
			return $Checkhash;
		}
		/**
		 * @param WC_Order $order
		 *
		 * @return false|string
		 */
		function check_order_hash( $order ) {
			$hash             = array();
			$hash[]           = 'WC-' . $order->get_id();
			$hash[]           = number_format( $order->get_total(), wc_get_price_decimals(), '.', '' );
			$hash[]           = $order->get_currency();
			$hash = apply_filters( 'epicpay_'.$this->id.'_check_order_hash', $hash, $order );
			$message          = implode( '|', $hash );
			$CheckHashMessage = utf8_encode( trim( $message ) );
			$Checkhash        = hash_hmac( 'sha256', $CheckHashMessage, $this->secretkey );
			return $Checkhash;
		}
		/**
		 * @param WC_Order $order
		 *
		 * @return false|string
		 */
		function check_order_refund_hash( $order ) {
			$hash             = array();
			$hash[]           = $this->merchantid;
			$hash[]           = get_post_meta( $order->get_id() , '_' . $this->id . '_refundid', true );
			$hash = apply_filters( 'epicpay_'.$this->id.'_check_order_refund_hash', $hash, $order );
			$message          = implode( '|', $hash );
			$CheckHashMessage = utf8_encode( trim( $message ) );
			$Checkhash        = hash_hmac( 'sha256', $CheckHashMessage, $this->secretkey );
			return $Checkhash;
		}
		/**
		 * @param WC_Order $order
		 *
		 * @return array
		 */
		function get_epicpay_args( $order ) {
			//Epicpay Args
			global $wp_version;
			$ipnUrl = WC()->api_request_url( 'WC_Gateway_Epicpay' );
			$epicpay_args = array(
				'merchantid'             => $this->merchantid,
				'paymentgatewayid'       => $this->paymentgatewayid,
				'checkhash'              => $this->check_hash( $order ),
				'orderid'                => 'WC-' . $order->get_id(),
				'reference'              => $order->get_order_number(),
				'currency'               => $order->get_currency(),
				'language'               => $this->langpaymentpage,
				'SourceSystem'           => 'WP' . $wp_version . ' - WC' . WC()->version . ' - BRG' . EPICPAY_VERSION,
				'buyeremail'             => $order->get_billing_email(),
				'returnurlsuccess'       => esc_url_raw($this->get_return_url( $order )),
				'returnurlsuccessserver' => $ipnUrl,
				'returnurlcancel'        =>$order->get_checkout_payment_url( true ),
				'returnurlerror'         => $order->get_checkout_payment_url( true ),
				'amount'                 => number_format( $order->get_total(), wc_get_price_decimals(), '.', '' ),
				'pagetype'               => '0',
				//If set as 1 then cardholder is required to insert email,mobile number,address.
				'skipreceiptpage'        => '1',
				'merchantemail'          => $this->notification_email,
			);
			$epicpay_args = apply_filters( 'epicpay_get_'.$this->id.'_args', $epicpay_args, $order );
			// Cart Contents
			$total_line_item = $this->TotalLineItem;
			$include_tax = $this->tax_display();
			$item_loop = 0;
			if ( sizeof( $order->get_items( array( 'line_item', 'fee' ) ) ) > 0 ) {
				if ( $total_line_item == "yes" ) {
					$item_description = '';
					foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
						$item_name = strip_tags( $item->get_name() );
						if( !empty($item_description) ) $item_description .= ', ';
						$item_description .= $item_name;
					}
					if (strlen($item_description) > 499) $item_description = mb_substr($item_description, 0, 496) . '...';
					$epicpay_args[ 'itemdescription_' . $item_loop ] = html_entity_decode( $item_description, ENT_NOQUOTES, 'UTF-8' );
					$epicpay_args[ 'itemcount_' . $item_loop ]       = 1;
					$epicpay_args[ 'itemunitamount_' . $item_loop ]  = $epicpay_args['amount'];
					$epicpay_args[ 'itemamount_' . $item_loop ]      = $epicpay_args['amount'];
				}else{
					foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
						if ( 'fee' === $item['type'] ) {
							$fee = $item->get_total();
							if ( $include_tax && $this->fee_tax_display($item) ){
								$fee += $item->get_total_tax();
							}
							$fee_total = $this->round( $fee, $order );
							$item_name = strip_tags( $item->get_name() );
							$epicpay_args[ 'itemdescription_' . $item_loop ] = html_entity_decode( $item_name, ENT_NOQUOTES, 'UTF-8' );
							$epicpay_args[ 'itemcount_' . $item_loop ]       = 1;
							$epicpay_args[ 'itemunitamount_' . $item_loop ]  = $fee_total;
							$epicpay_args[ 'itemamount_' . $item_loop ]      = $fee_total;
							$item_loop ++;
						}
						if ( $item['qty'] ) {
							$item_name = $item['name'];
							if ( $meta = wc_display_item_meta( $item ) ) {
								$item_name .= ' ( ' . $meta . ' )';
							}
							$item_name = strip_tags($item_name);
							$item_subtotal = number_format( $order->get_item_subtotal( $item, $include_tax ), wc_get_price_decimals(), '.', '' );
							$itemamount = $item_subtotal * $item['qty'];
							$epicpay_args[ 'itemdescription_' . $item_loop ] = html_entity_decode( $item_name, ENT_NOQUOTES, 'UTF-8' );
							$epicpay_args[ 'itemcount_' . $item_loop ]       = $item['qty'];
							$epicpay_args[ 'itemunitamount_' . $item_loop ]  = number_format( $item_subtotal, wc_get_price_decimals(), '.', '' );
							$epicpay_args[ 'itemamount_' . $item_loop ]      = number_format( $itemamount, wc_get_price_decimals(), '.', '' );
							$item_loop ++;
						}
					}
					if ( $order->get_shipping_total() > 0 ) {
						$shipping_total = $order->get_shipping_total();
						if( $include_tax ) $shipping_total += $order->get_shipping_tax();
						$shipping_total = $this->round( $shipping_total, $order );
						$epicpay_args[ 'itemdescription_' . $item_loop ] = sprintf( /* translators: %s: Shipping */ __('Shipping (%s)', 'epicpay_woocommerce' ), $order->get_shipping_method() );
						$epicpay_args[ 'itemcount_' . $item_loop ]       = 1;
						$epicpay_args[ 'itemunitamount_' . $item_loop ]  = number_format( $shipping_total, wc_get_price_decimals(), '.', '' );
						$epicpay_args[ 'itemamount_' . $item_loop ]      = number_format( $shipping_total, wc_get_price_decimals(), '.', '' );
						$item_loop ++;
					}
					if (!$include_tax && $order->get_total_tax() > 0){
						$epicpay_args[ 'itemdescription_' . $item_loop ] = __('Taxes', 'epicpay_woocommerce' );
						$epicpay_args[ 'itemcount_' . $item_loop ]       = 1;
						$epicpay_args[ 'itemunitamount_' . $item_loop ]  = number_format( $order->get_total_tax(), wc_get_price_decimals(), '.', '' );
						$epicpay_args[ 'itemamount_' . $item_loop ]      = number_format( $order->get_total_tax(), wc_get_price_decimals(), '.', '' );
						$item_loop ++;
					}
					if ( $order->get_total_discount() > 0 ) {
						$total_discount = $order->get_total_discount();
	/*				Woocommerce can see any tax adjustments made thus far using subtotals.
						Since Woocommerce 3.2.3*/
						if(wc_tax_enabled() && method_exists('WC_Discounts','set_items') && $include_tax){
							$total_discount += $order->get_discount_tax();
						}
						if(wc_tax_enabled() && !method_exists('WC_Discounts','set_items') && !$include_tax){
							$total_discount -= $order->get_discount_tax();
						}
						$total_discount = $this->round($total_discount, $order);
						$epicpay_args[ 'itemdescription_' . $item_loop ] = __('Discount', 'epicpay_woocommerce' );
						$epicpay_args[ 'itemcount_' . $item_loop ]       = 1;
						$epicpay_args[ 'itemunitamount_' . $item_loop ]  = - number_format( $total_discount, wc_get_price_decimals(), '.', '' );
						$epicpay_args[ 'itemamount_' . $item_loop ]      = - number_format( $total_discount, wc_get_price_decimals(), '.', '' );
						$item_loop ++;
					}
				}
			}
			return $epicpay_args;
		}
		//Generate the epicpay button link
		function generate_epicpay_form( $order_id, $redirect = true ) {
			global $woocommerce;
			if ( function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( $order_id );
			} else {
				$order = new WC_Order( $order_id );
			}
			/*if ( 'yes' == $this->testmode ) {
				$epicpay_adr = self::EPICPAY_ENDPOINT_SANDBOX . 'default.aspx';
			} else {
				$epicpay_adr = self::EPICPAY_ENDPOINT_LIVE . 'default.aspx';
			}
			$epicpay_args       = $this->get_epicpay_args( $order );
			$epicpay_args_array = array();
			foreach ( $epicpay_args as $key => $value ) {
				$epicpay_args_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
			}*/
			/*if($redirect){
				$redirecttext = $this->get_translated_string($this->redirecttext, 'redirecttext');
				$code = sprintf( '$.blockUI({
						message: "%s",
						baseZ: 99999,
	                    overlayCSS: { background: "#fff", opacity: 0.6 },
	                    css: {
	                        padding:        "20px",
	                        zindex:         "9999999",
	                        textAlign:      "center",
	                        color:          "#555",
	                        border:         "3px solid #aaa",
	                        backgroundColor:"#fff",
	                        cursor:         "wait",
	                        lineHeight:     "24px",
	                    }
	                });
	                jQuery("#epicpay_payment_form").submit();', $redirecttext );
				wc_enqueue_js( $code );
			}*/
			$merchantID = $this->merchantid;/*'98cda84a-24d8-4479-8606-c3a47fe80df1';*/
			$terminalKey = $this->secretkey; /*'GHDjtBRDO22vgB5X9s6t3iCup2gfw9ahTbC6Sri3sT4SVTtyEbLFNFrbWW4ez2PF';*/
			// environment fetch
			$epicPay = new EpicPay($merchantID,  $terminalKey);
			/*--*/
			$currency = 'EUR';
			$return_url = $this->returnUrl_API;
			$amount = $order->get_total(); // amount in euro with point x.xx or not
			$referenceID = 'charge-'.$order->get_id();
			$email = 'test@test.com';
			/*--*/
			$result = $epicPay->transaction($order_id,$amount,$currency,$email, $merchantID, $return_url, $referenceID);
			$obj = json_decode($result);
			//var_dump($obj);
				$res = $obj->included;
				$attr = '';
				$attr_url = '';
				foreach ($res as $obj1)
				{
				    // Here you can access to every object value in the way that you want
				    $attr = $obj1->attributes;
				    $attr_url = $attr->url;
				}
			if(!empty($this->errorurl)){
				//wp_safe_redirect( $this->errorurl );
				header('Location: '.$attr_url );
				//wp_safe_redirect( $attr_url );
			
			}else{
				header('Location: '.$attr_url );
				//wp_safe_redirect( $attr_url );
			}
			$cancel_btn_html = ( current_user_can( 'cancel_order', $order_id ) ) ? '<a class="button cancel" href="' . htmlspecialchars_decode($order->get_cancel_order_url()) . '">' . __( 'Cancel order &amp; restore cart', 'epicpay_woocommerce' ) . '</a>' : '';
			$html_form = '<form action="' . esc_url( $epicpay_adr ) . '" method="post" id="epicpay_payment_form">'
			             . implode( '', $epicpay_args_array )
			             . '<input type="submit" class="button" id="wc_submit_epicpay_payment_form" value="' . __( 'Pay via EpicPay', 'epicpay_woocommerce' ) . '" /> ' . $cancel_btn_html . '</form>';
			return $html_form;
		}
		function process_payment( $order_id ) {
			if ( function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( $order_id );
			} else {
				$order = new WC_Order( $order_id );
			}
			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
			);
		}
		function check_epicpay_response() {
			global $woocommerce;
			global $wp;
			
			if( empty($_POST) ) return;
			$posted = array();
			$posted['status'] = !empty( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
			$posted['orderid'] = !empty( $_POST['orderid'] ) ? sanitize_text_field( $_POST['orderid'] ) : '';
			$posted['reference'] = !empty( $_POST['reference'] ) ? sanitize_text_field( $_POST['reference'] ) : '';
			$posted['orderhash'] = !empty( $_POST['orderhash'] ) ? sanitize_text_field( $_POST['orderhash'] ) : '';
			$posted['step'] = !empty( $_POST['step'] ) ? sanitize_text_field( $_POST['step'] ) : '';
			$posted['errorcode'] = !empty( $_POST['errorcode'] ) ? sanitize_text_field( $_POST['errorcode'] ) : '';
			$posted['errordescription'] = !empty( $_POST['errordescription'] ) ? sanitize_text_field( $_POST['errordescription'] ) : '';
			$posted['authorizationcode'] = !empty( $_POST['authorizationcode'] ) ? sanitize_text_field( $_POST['authorizationcode'] ) : '';
			$posted['maskedcardnumber'] = !empty( $_POST['creditcardnumber'] ) ? sanitize_text_field( $_POST['creditcardnumber'] ) : '';
			$posted['refundid'] = !empty( $_POST['refundid'] ) ? sanitize_text_field( $_POST['refundid'] ) : '';
			$merchantID = $this->merchantid;/*'98cda84a-24d8-4479-8606-c3a47fe80df1';*/
			$terminalKey = $this->secretkey; /*'GHDjtBRDO22vgB5X9s6t3iCup2gfw9ahTbC6Sri3sT4SVTtyEbLFNFrbWW4ez2PF';*/
			// environment fetch
			$epicPay = new EpicPay($merchantID,  $terminalKey);
			/*--*/
			$currency = 'EUR';
			$return_url = $this->returnUrl_API;
			$amount = $order->get_total(); // amount in euro with point x.xx or not
			$referenceID = 'charge-'.$order->get_id();
			$email = 'test@test.com';
			/*--*/
			$result = $epicPay->transaction($order_id,$amount,$currency,$email, $merchantID, $return_url, $referenceID);
			$obj = json_decode($result);
			//var_dump($obj);
				$res = $obj->included;
				$attr = '';
				$attr_url = '';
				foreach ($res as $obj1)
				{
				    // Here you can access to every object value in the way that you want
				    $attr = $obj1->attributes;
				    $attr_url = $attr->url;
				}
			if(!empty($this->errorurl)){
				//wp_safe_redirect( $this->errorurl );
				header('Location: '.$attr_url );
				//wp_safe_redirect( $attr_url );
			
			}else{
				header('Location: '.$attr_url );
				//wp_safe_redirect( $attr_url );
			}
		}
		function receipt_page( $order_id ) {
			if(!empty($_POST) && isset($_POST['status'])){
				$posted = array();
				$posted['status'] = sanitize_text_field( $_POST['status'] );
				if($posted['status'] == 'ERROR' || $posted['status'] == 'CANCEL'){
					echo $this->generate_epicpay_form( $order_id, false);
				}
			}else{
				$receipttext = $this->get_translated_string($this->receipttext, 'receipttext');
				if( !empty($receipttext) ) printf('<p>%s</p>', $receipttext );
				echo $this->generate_epicpay_form( $order_id);
			}
		}
		/**
		 * Round prices.
		 * @param  double $price
		 * @param  WC_Order $order
		 * @return double
		 */
		protected function round( $price, $order ) {
			$precision = 2;
			if ( ! $this->currency_has_decimals( $order->get_currency() ) ) {
				$precision = 0;
			}
			return round( $price, $precision );
		}
		/**
		 * Check if currency has decimals.
		 * @param  string $currency
		 * @return bool
		 */
		protected function currency_has_decimals( $currency ) {
			if ( in_array( $currency, array( 'HUF', 'JPY', 'TWD', 'ISK' ) ) ) {
				return false;
			}
			return true;
		}
		/**
		 * Check tax display.
		 * @return bool
		 */
		protected function tax_display() {
			$prices_include_tax = wc_tax_enabled() ? get_option( 'woocommerce_prices_include_tax' ) : 'yes';
			return ( $prices_include_tax === 'yes' ) ? true : false ;
		}
		/**
		 * Check fee tax display.
		 * @param  WC_Order_Item_Fee $item
		 * @return bool
		 */
		protected function fee_tax_display( $item ) {
			$tax_display = $item->get_tax_status();
			return ( $tax_display == 'taxable' ) ? true : false ;
		}
		/**
		 * Adds text keys to_translate.
		 * @param  $text_keys array
		 * @return array
		 */
		public function epicpay_text_keys_to_translate( $text_keys ){
			if( !in_array( 'receipttext', $text_keys ) )
				$text_keys[] = 'receipttext';
			if( !in_array( 'redirecttext', $text_keys ) )
				$text_keys[] = 'redirecttext';
			return $text_keys;
		}
		/**
		 * Getting translated value
		 * @param  $string string Original field value
		 * @param  $name string Field key
		 * @return string
		 */
		public function get_translated_string( $string, $name ) {
			$translated_string = $string;
			$current_lang = apply_filters( 'wpml_current_language', NULL );
			if($current_lang && class_exists('WCML_WC_Gateways') ){
				if(defined('WCML_WC_Gateways::STRINGS_CONTEXT')){
					$domain = WCML_WC_Gateways::STRINGS_CONTEXT;
				} else {
					$domain = 'woocommerce';
				}
				$translated_string = apply_filters(
					'wpml_translate_single_string',
					$string,
					$domain,
					$this->id . '_gateway_' . $name,
					$current_lang
				);
			}
			if ( $translated_string === $string ) {
				$translated_string = __( $string, 'epicpay_woocommerce' );
			}
			return $translated_string;
		}
		/**
		 * Get cancel order url
		 */
		function checkout_payment_handler(){
			global $wp;
			$order_id = '';
			if( !empty( $wp->query_vars['order-pay']) ){
				$order_id = (int) $wp->query_vars['order-pay'];
			}
			if ( $order_id && !empty($_POST) && isset($_POST['status']) ) {
				$posted = [];
				$posted['status'] = sanitize_text_field($_POST['status']);
				$posted['errordescription'] = !empty( $_POST['errordescription'] ) ? sanitize_text_field( $_POST['errordescription'] ) : '';
				if($posted['status'] === 'ERROR'){
					if ( function_exists( 'wc_get_order' ) ) {
						$order = wc_get_order( $order_id );
					} else {
						$order = new WC_Order( $order_id );
					}
					if( !empty($order) && $order->get_payment_method() == $this->id ) {
						wc_add_notice( $posted['errordescription'], 'error' );
						$this->error_response_process($order, $posted['status'] . ' : ' . $posted['errordescription']);
					}
				}
				elseif($posted['status'] === 'CANCEL'){
					wc_add_notice( __('Payment cancelled', 'epicpay_woocommerce'), 'notice' );
					if ( function_exists( 'wc_get_order' ) ) {
						$order = wc_get_order( $order_id );
					} else {
						$order = new WC_Order( $order_id );
					}
					$this->cancel_response_process($order);
				}
			}
		}
		//redirect if responsw api return erorr
		function error_response_process($order, $message = ''){
			$order->add_order_note( $message );
			$merchantID = $this->merchantid;/*'98cda84a-24d8-4479-8606-c3a47fe80df1';*/
			$terminalKey = $this->secretkey; /*'GHDjtBRDO22vgB5X9s6t3iCup2gfw9ahTbC6Sri3sT4SVTtyEbLFNFrbWW4ez2PF';*/
			// environment fetch
			$epicPay = new EpicPay($merchantID,  $terminalKey);
			/*--*/
			$currency = 'EUR';
			$return_url = $this->returnUrl_API;
			$amount = $order->get_total(); // amount in euro with point x.xx or not
			$referenceID = 'charge-'.$order->get_id();
			$email = 'test@test.com';
			/*--*/
			$result = $epicPay->transaction($order_id,$amount,$currency,$email, $merchantID, $return_url, $referenceID);
			$obj = json_decode($result);
			//var_dump($obj);
				$res = $obj->included;
				$attr = '';
				$attr_url = '';
				foreach ($res as $obj1)
				{
				    // Here you can access to every object value in the way that you want
				    $attr = $obj1->attributes;
				    $attr_url = $attr->url;
				}
			if(!empty($this->errorurl)){
				//wp_safe_redirect( $this->errorurl );
				header('Location: '.$attr_url );
				//wp_safe_redirect( $attr_url );
			
			}else{
				header('Location: '.$attr_url );
				//wp_safe_redirect( $attr_url );
			}
		}
		function cancel_response_process($order, $message = ''){
			$user_can_cancel  = current_user_can( 'cancel_order', $order->get_id() );
			if($user_can_cancel){
				$redirect = htmlspecialchars_decode($order->get_cancel_order_url());
				wp_safe_redirect( $redirect );
				exit;
			}else{
				$order->add_order_note( __('Payment canceled by the customer','epicpay_woocommerce') );
				if(!empty($this->cancelurl)){
					wp_safe_redirect( $this->cancelurl );
					exit;
				}
			}
		}
		/**
		 * Save order metas
		 * @since 1.0.0
		 * @param WC_Order $order The order which is in a transitional state.
		 * @param array $meta Response meta data
		 */
		public function save_order_metas($order, $metas ){
			if( !empty($metas) ){
				foreach ($metas as $key => $meta) {
					if( !empty($meta) ) $order->update_meta_data( '_' . $this->id . '_' . $key, $meta );
				}
				$order->save();
			}
		}
		/**
		 * Process refund.
		 *
		 * @param  int        $order_id Order ID.
		 * @param  float|null $amount Refund amount.
		 * @param  string     $reason Refund reason.
		 * @return boolean True or false based on success, or a WP_Error object.
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			if ( function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( $order_id );
			} else {
				$order = new WC_Order( $order_id );
			}
			$merchantID = $this->merchantid;/*'98cda84a-24d8-4479-8606-c3a47fe80df1';*/
			$terminalKey = $this->secretkey; /*'GHDjtBRDO22vgB5X9s6t3iCup2gfw9ahTbC6Sri3sT4SVTtyEbLFNFrbWW4ez2PF';*/
			// environment fetch
			$epicPay_refund = new EpicPay_refund($merchantID,  $terminalKey);
			/*--*/
			$currency = 'EUR';
			$return_url = $this->returnUrl_API;
			$amount = $order->get_total(); // amount in euro with point x.xx or not
			$referenceID = 'charge-'.$order->get_id();
			$email = 'test@test.com';
			/*--*/
			$result = $epicPay_refund->transaction($order_id,$amount,$currency,$email, $merchantID, $return_url, $referenceID);
			$obj = json_decode($result);
			
			/*if ( ! $this->can_refund_order( $order ) ) {
				return new WP_Error( 'error', __( 'Refund failed.', 'epicpay_woocommerce' ) );
			}*/
			//$refundid = get_post_meta( $order_id , '_' . $this->id . '_refundid', true );
			/*if ( 'yes' == $this->testmode ) {
				$epicpay_adr = self::EPICPAY_ENDPOINT_SANDBOX . 'refund.aspx';
			} else {
				$epicpay_adr = self::EPICPAY_ENDPOINT_LIVE . 'refund.aspx';
			}*/
			/*$data = [];
			$data['RefundId'] = $refundid;
			$data['MerchantId'] = $this->merchantid;
			$data['PaymentGatewayId'] = $this->paymentgatewayid;
			$data['Checkhash'] = $this->check_order_refund_hash( $order );
			$data['amount'] = $amount;*/
			/*$response = wp_remote_post(
				$epicpay_adr,
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'headers'     => array(),
					'body'        => $data,
					'sslverify'   => false
				)
			);*/
			/*if ( empty( $response['body'] ) ) {
				$message = __( 'Empty Response', 'epicpay');
				$order->add_order_note( $message );
				return new WP_Error( 'error', $message );
			} elseif ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
				$order->add_order_note( $message );
				return new WP_Error( 'error', $message );
			}*/
			/*$body = wp_remote_retrieve_body( $response );
			if( !empty($body) ) {
				parse_str($body, $result);
				if( isset($result['action_code']) && $result['action_code'] == '000' && isset($result['ret']) && $result['ret'] == true ) {
					$message = sprintf( __('Refunded %s %s via Epicpay', 'epicpay_woocommerce' ), $amount, $order->get_currency() );
					$order->add_order_note( $message );
					return true;
				}
				else {
					/*$message = sprintf( __('Epicpay error: %s, Amount: %s %s', 'epicpay_woocommerce' ), $result['message'], $amount, $order->get_currency() );
					$order->add_order_note( $message );
					return new WP_Error( 'error', $result['message'] );
				}
			}*/
				if( 1==1) {
					$message = sprintf( __('Refunded %s %s via EpicPayments', 'epicpay_woocommerce' ), $amount, $order->get_currency() );
					$message2 = sprintf( __('EpicPayments error: %s, Amount: %s %s', 'epicpay_woocommerce' ), $result['message2'], $amount, $order->get_currency() );
					$order->add_order_note( $message );
					return true;
					//return new WP_Error( 'error', $result['message2'] );
				}
			return false;
		}
	}
	add_action( 'woocommerce_cancelled_order', 'epicpay_cancel_order' );
	function epicpay_cancel_order( $order_id ){
		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = new WC_Order( $order_id );
		}
		if( !empty($order) && $order->get_payment_method() == 'epicpay' ) {
			$epicpay_settings = get_option('woocommerce_epicpay_settings');
			if( !empty($epicpay_settings) && !empty( $epicpay_settings['cancelurl'] ) ){
				if ( !empty($_POST) && isset($_POST['status']) ) {
					$posted = array();
					$posted_order_id = '';
					$posted['orderid'] = !empty( $_POST['orderid'] ) ? sanitize_text_field( $_POST['orderid'] ) : '';
					$posted['reference'] = !empty( $_POST['reference'] ) ? sanitize_text_field( $_POST['reference'] ) : '';
					if ( ! empty( $posted['orderid'] ) ) {
						$order_id = (int) str_replace( 'WC-', '', $posted['orderid'] );
					}
					elseif ( !empty( $posted['reference'] )) {
						$order_id = (int) $posted['reference'];
					}
					if($posted_order_id == $order_id){
						wp_safe_redirect( $epicpay_settings['cancelurl'] );
						exit;
					}
				}else{
					wp_safe_redirect( $epicpay_settings['cancelurl'] );
					exit;
				}
			}
		}
	}
}
function woocommerce_epicpay_textdomain(){
	global $wp_version;
	// Default languages directory for Epicpay.
	$lang_dir = EPICPAY_DIR . 'languages/';
	$lang_dir = apply_filters( 'epicpay_languages_directory', $lang_dir );
	$current_lang = apply_filters( 'wpml_current_language', NULL );
	if($current_lang){
		$languages = apply_filters( 'wpml_active_languages', NULL );
		$locale = ( isset($languages[$current_lang]) && isset($languages[$current_lang]['default_locale']) ) ? $languages[$current_lang]['default_locale'] : '' ;
	}else{
		$locale = get_locale();
		if ( $wp_version >= 4.7 ) {
			$locale = get_user_locale();
		}
	}
	$mofile = sprintf( '%1$s-%2$s.mo', 'epicpay_woocommerce', $locale );
	// Setup paths to current locale file.
	$mofile_local  = $lang_dir . $mofile;
	$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
	if ( file_exists( $mofile_global ) ) {
		// Look in global folder.
		load_textdomain( 'epicpay_woocommerce', $mofile_global );
	} elseif ( file_exists( $mofile_local ) ) {
		// Look in local  folder.
		load_textdomain( 'epicpay_woocommerce', $mofile_local );
	} else {
		// Load the default language files.
		load_plugin_textdomain( 'epicpay_woocommerce', false, $lang_dir );
	}
}