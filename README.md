# F3-PesaPal
F3-PesaPal is a Fat Free Framework plugin that helps in easy implementation of PesaPal Checkout.


## Quick Start Config
Add the following custom section to your project config if using the ini style configuration.

```ini
[PESAPAL]
consumer_key=yourConsumerKey
consumer_secret=yourConsumerSecret
call_back=yourCallBackURL
currency=KES
type=MERCHANT
endpoint=sandbox
log=1
```

- consumer_key - Your PayPal API Username
- consumer_secret - Your PayPal API Password
- call_back - Your PayPal API Signature
- currency - API Version current release is 204.0
- type - The URL that PayPal redirects your buyers to after they have logged in and clicked Continue or Pay
- endpoint - API Endpoint, values can be 'sandbox' or 'production'
- log - logs all API requests & responses to paypal.log

If you prefer you can also pass an array with above values when you instantiate the classes.

```php
// F3-PesaPal config
$pesaPalConfig = array(
'consumer_key'=>'yourConsumerKey',
'consumer_secret'=>'yourConsumerSecret',
'call_back'=>'yourCallBackURL',
'currency'=>'KES',
'type'=>'MERCHANT',
'endpoint'=>'sandbox',
'log'=>'1'
);

// Instantiate the class with config
$pesapal=new PesaPal($pesaPalConfig);
```


**Manual Install**
Copy the `lib/PesaPal.php` and `lib/OAuth.php`file into your `lib/` or your AUTOLOAD folder.  



## Quick Start
### PesaPal Checkout
The process is going to be a multistep process as below assuming a simple form based test: 
1. Create PesaPal Instance

```php
$pesapal=new PesaPal;
```
2. Populate PesaPal mandatory variables to create the valid XML to post to pesapal
```php
/*Define PesaPal Mandatory Variables*/
$orderID=$f3->get('POST.TransactionID');
$first_name=$f3->get('POST.first_name');
$last_name=$f3->get('POST.last_name');
$Description=$f3->get('POST.description');
$email=$f3->get('POST.email');
$telephone=$f3->get('POST.telephone');
$totalAmount = $f3->get('POST.Amount');
/*End Define PesaPal Mandatory Variables*/
//Create PesaPal XML valid format
//You can do some DB operations here based on the variables as you POST the XML
$post_xml=$pesapal->create_pesapal_xml($orderID,$first_name,$last_name,$Description,$email,$telephone,$totalAmount);
```

3. Build the proper pesapal Iframe using the XML created and pass it to a view
```php
//Use the XML to genreate the IFRAME
$content=$pesapal->send_payment_to_pesapal($post_xml);
//Update the Fatfree Hive with the new variable and assign it a value 
$f3->set('content',$content);
//Render on page
echo \Template::instance()->render('pesapal.html');
```
Your actual View i.e. pesapal.html will be tagged a sample is as below: 
```html
<include href="header.html" />
{{@content | raw}}
<include href="footer.html" />
```
### PesaPal IPN Status
The plugin currently supports IPN checking by merchant reference and Pesapal Transaction ID. When Pesapal creates a Notification you can receive it and process using the function below in your IPN route , sample is as below:
```php
$f3->route('GET /ipn',
	function ($f3) {
		$pesapal=new Pesapal;
		$orderID=$f3->get('GET.pesapal_merchant_reference');
		$pesapalTrackingId=$f3->get('GET.pesapal_transaction_tracking_id');
		$status=$pesapal->checkStatusUsingTrackingIdandMerchantRef($orderID,$pesapalTrackingId);
		//perform DB operations and Update Status to the status returned e.g. COMPLETED, PENDING, INVALID, FAILED
		}
		);
```
## Sample Checkout Page generated on Fatfree

![Pesapal Iframe successfully generated](https://github.com/alienwithin/F3-Pesapal/raw/master/fatfree_framework_pesapal_integration.png "Pesapal Integration in FatFree")
## License
F3-PesaPal is licensed under GPL v.3
