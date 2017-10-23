<?php

// Kickstart the framework
$f3=require('lib/base.php');

// Load configuration
$f3->config('config.ini');

/*Home page loads the basket and prepopulates Items
Ideally this is where you would have the logic for basket for users to select items and add to cart.
In this case we have just initialized as it is a demo
*/
$f3->route('GET /',
	function($f3) {
		$basket = new \Basket();
        $basket->drop();

        // add item
        $basket->set('name', 'Kahawa');
        $basket->set('amount', '15.00');
        $basket->set('qty', '2');
        $basket->save();
        $basket->reset();
        // add item
        $basket->set('name', 'Mafuta');
        $basket->set('amount', '100.00');
        $basket->set('qty', '1');
        $basket->save();
        $basket->reset();

        $cart = $basket->find();
        foreach ($cart as $item) {
            $subtotal += $item['amount'] * $item['qty'];
            $itemcount+=$item['qty'];
        }
        $f3->set('itemcount', $itemcount);
        $f3->set('cartitems', $cart);
        $f3->set('subtotal', sprintf("%01.2f", $subtotal));
        echo \Template::instance()->render('choose.html');
	}
);
/*
Here we display the cart summary as the checkout process begins. 
We get the buyer/billing information and number of items in basket
*/
$f3->route('GET|POST /checkout',
    function ($f3) {
        $basket = new \Basket();
        $f3->set('itemcount', $basket->count());
        echo \Template::instance()->render('checkout.html');
    }
);
/*
We then perform the checkout finalization by sending the required items to Pesapal;
Status of the transaction here can be considered as "PLACED" 
*/
$f3->route('GET|POST /pesapal',
	function ($f3) {
		$basket = new \Basket();
        $cartitems = $basket->find();
		$pesapal= new Pesapal;
		/*Define Pesapal Mandatory Variables*/
		$orderID=generatePesapalTransactionID();
		$Description= $f3->get('POST.description');
		$first_name=$f3->get('POST.first_name');
		$last_name=$f3->get('POST.last_name');
		$email=$f3->get('POST.email');
		$telephone=$f3->get('POST.telephone');
		$subtotal = $pesapal->copyBasket($cartitems);
		/*End Define Pesapal Mandatory Variables*/
		//Create Pesapal XML valid format
		$post_xml=$pesapal->create_pesapal_xml($orderID,$first_name,$last_name,$Description,$email,$telephone,$subtotal);
		//Generate Iframe and pass it to the view
		$content=$pesapal->send_payment_to_pesapal($post_xml);
		$f3->set('content',$content);
		//Render on page
		echo \Template::instance()->render('pesapal.html');
	}
);
/*Pesapal responds to our call back URL with the transaction_tracking_id which will allow us to change the transaction status to "PENDING"*/
$f3->route('GET|POST /thankyou',
    function ($f3) {
			$pesapalTrackingId=$f3->get('GET.pesapal_transaction_tracking_id');
			//perform DB operations update to PENDING after getting a tracking ID
			echo \Template::instance()->render('thanks.html');
    }
);
/*
Pesapal hits back our IPN to tell us if the transaction was successful or not and we update accordingly. i.e. 
- COMPLETED
- INVALID
- FAILED
- PENDING
*/
$f3->route('GET /ipn',
	function ($f3) {
		$pesapal=new Pesapal;
		$orderID=$f3->get('GET.pesapal_merchant_reference');
		$pesapalTrackingId=$f3->get('GET.pesapal_transaction_tracking_id');
		$status=$pesapal->checkStatusUsingTrackingIdandMerchantRef($orderID,$pesapalTrackingId);
		//perform DB operations and Update Status;
	}
);
/*
Simple function used to generate alphanumeric transaction IDs
*/
function generatePesapalTransactionID($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
$f3->run();
