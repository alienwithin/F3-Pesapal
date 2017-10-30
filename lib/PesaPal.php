<?php
/**
*	@Package: FatFree Pesapal Payment Gateway
*	@Description: Allows use of kenyan payment processor Pesapal - http://pesapal.com.
*	@Version: 1.0.4
*	@Author: Munir Njiru
*	@Author URI: https://www.alien-within.com
*	@License: GPLv3
*	@License URI: http://www.gnu.org/licenses/gpl-3.0.html
*
*	Copyright 2017  Munir Njiru  (email : munir@alien-within.com)
*/
include_once("OAuth.php");
class PesaPal extends Prefab
{
    protected $f3;
    private   $pesapalSettings = array();
    public    $endpoint;
    public    $line_items = array();
    public    $item_counter = 0;
    public    $item_total = 0;
    public    $pesapal_parameters = array();
    public    $logger;
	
     /**
     *    Class constructor
     *    Defines API endpoint, pesapal Settings in configuration file
     *    @param  $options array
     */
    function __construct($options = null)
    {
        $f3 = Base::instance();
        @session_start();
        $f3->sync('SESSION');
        if ($options == null)
            if ($f3->exists('PESAPAL'))
                $options = $f3->get('PESAPAL');
            else
                $f3->error(500, 'No configuration options set for Pesapal on Fat Free Framework');
		if ($options['endpoint'] == "production") {
            $this->endpoint = 'https://www.pesapal.com/';
        } else {
            $this->endpoint = 'https://demo.pesapal.com/';
        }
		$this->pesapalSettings['key'] = $options['consumer_key'];
                $this->pesapalSettings['secret'] = $options['consumer_secret'];
		$this->pesapalSettings['callback'] = $options['call_back'];
		$this->pesapalSettings['currency'] = $options['currency'];
		$this->pesapalSettings['type'] = $options['type'];
        if ($options['log']) {
            $this->logger = new Log('pesapal.log');
        }
	}
	/**
     * Build array of line items & calculating item total.
     * @param $item_name string
     * @param $item_quantity integer
     * @param $item_price string
     */
    function setLineItem($item_name, $item_quantity = 1, $item_price)
    {
        $i = $this->item_counter++;
        $this->line_items["L_PAYMENTREQUEST_0_NAME$i"] = $item_name;
        $this->line_items["L_PAYMENTREQUEST_0_QTY$i"] = $item_quantity;
        $this->line_items["L_PAYMENTREQUEST_0_AMOUNT$i"] = $item_price;
        $this->item_total += ($item_quantity * $item_price);
    }
	/**
     * Create XML to be used in Pesapal for ordering and populate mandatory fields for pesapal.
     * @param $orderID string
	 * @param $first_name string
	 * @param $last_name string
	 * @param $Description string
	 * @param $email string
	 * @param $telephone string
	 * @param $totalAmount integer
     */
	function create_pesapal_xml($orderID,$first_name,$last_name,$Description,$email,$telephone,$totalAmount){
		$f3 = Base::instance();
		$getCurrency=$this->pesapalSettings['currency'];
		$getTransType=$this->pesapalSettings['type'];
		$Orderxml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
            <PesapalDirectOrderInfo xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"
            Amount=\"" . $totalAmount . "\"
            Description=\"" . $Description . "\"
            Type=\"" . $getTransType . "\"
            Reference=\"" . $orderID . "\"
            FirstName=\"" . $first_name . "\"
            LastName=\"" . $last_name . "\"
            Email=\"" . $email . "\"
	    Currency=\"" . $getCurrency . "\"
            PhoneNumber=\"" . $telephone . "\"
            xmlns=\"http://www.pesapal.com\" />";
		//protect against XSS
		return htmlentities($Orderxml);
		
	}
	/**
     * Generate Pesapal Iframe based on Pesapal Settings as well as XML generated
     * @param $post_xml string
     */
	function send_payment_to_pesapal($post_xml){
		$this->token = $this->params = NULL;
		/*Pick Settings from config and constructor*/
		$getCallback=$this->pesapalSettings['callback'];
		$getEndpoint=$this->endpoint;
		$getConsumerKey=$this->pesapalSettings['key'];
		$getConsumerSecret=$this->pesapalSettings['secret'];
		/*End Pick Settings from config and constructor*/
		$signature_method = new OAuthSignatureMethod_HMAC_SHA1();
		//Build Pesapal Link 
		$consumer = new OAuthConsumer($getConsumerKey,$getConsumerSecret);
		$iframe_link = $getEndpoint."api/PostPesapalDirectOrderV4";
		$iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $iframe_link, $params);
		$iframe_src->set_parameter("oauth_callback",$getCallback );
		$iframe_src->set_parameter("pesapal_request_data", $post_xml);
		$iframe_src->sign_request($signature_method, $consumer, $token);  
		//End Build Pesapal Link		
		//Generate the proper Iframe and pass this function to your view render
		$iframe = '<iframe src="'.$iframe_src.'" width="100%" height="700px"  scrolling="no" frameBorder="0">
                            <p>Browser unable to load iFrame</p>
                    </iframe>';
        return $iframe;
	}
	/**
     * Copy basket() to Pesapal Checkout
     * Transfer your basket details to the Pesapal Checkout
     * Returns a total value of items
     * @param  $basket object
     * @param  $name string
     * @param  $amount string
     */
    function copyBasket($basket, $name = 'name', $quantity = 'qty', $amount = 'amount')
    {
        $totalamount = 0;
        foreach ($basket as $lineitem) {

            if (empty($lineitem->{$quantity})) {
                $lineitem->{$quantity} = 1;
            }

            $this->setLineItem($lineitem->{$name}, $lineitem->{$quantity}, $lineitem->{$amount});
            $totalamount += $lineitem->{$amount} * $lineitem->{$quantity};
        }

        return $totalamount;
    }
	/**
     * Check IPN status when Pesapal sends a notification change
	 * Returns the Status of the transaction. 
     * @param $orderID string
	 * @param $pesapalTrackingId string
     */
	function checkStatusUsingTrackingIdandMerchantRef($orderID,$pesapalTrackingId){
		$f3 = Base::instance();
		// Parameters sent to you by PesaPal IPN
		$pesapalNotification=$f3->get('GET.pesapal_notification_type');
		/*Pick Settings from config and constructor*/
		$getCallback=$this->pesapalSettings['callback'];
		$getEndpoint=$this->endpoint;
		$getConsumerKey=$this->pesapalSettings['key'];
		$getConsumerSecret=$this->pesapalSettings['secret'];
		$statusrequestAPI=$getEndpoint."api/querypaymentstatus";
		/*End Pick Settings from config and constructor*/
		if($pesapalNotification=="CHANGE" && $pesapalTrackingId!=''){
			$token = $params = NULL;
			$consumer = new OAuthConsumer($getConsumerKey,$getConsumerSecret);
			$signature_method = new OAuthSignatureMethod_HMAC_SHA1();
			$request_status = OAuthRequest::from_consumer_and_token($getConsumerKey, $token, "GET", $statusrequestAPI, $params);
			$request_status->set_parameter("pesapal_transaction_tracking_id",$pesapalTrackingId);
			$request_status->sign_request($signature_method, $consumer, $token);
			$web = Web::instance();
			$options = array(
							'method'  => 'GET',
						);
			$check_transaction_status = $web->request($request_status,$options);
			$pesapal_response_data=$check_transaction_status['body'];
			$status_headers=$check_transaction_status['headers'];
			switch ($pesapal_response_data) {
		    case "INVALID":
		        return $status_body;
		    break;
			case "PENDING":
		        return $status_body;
		    break;
			case "COMPLETED":
		        return $status_body;
		    break;
			case "FAILED":
		        return $status_body;
		    break;
			default:
		    	return "Status check could not be completed.";
		    break;
			}
		}
	}
}
