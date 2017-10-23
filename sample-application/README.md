# F3-Pesapal Sample Implementation

To use this sample application update the config.ini parameters with your environment parameters i.e. 

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

If being placed in a sub folder also update the RewriteBase line of the .htaccess file e.g. if installed in a sub folder called pesapal line 8 of the htaccess will be: 

```
RewriteBase /pesapal
```

The application takes a 4 step- process to checkout: 
* Load shopping cart , currently initialized using the basket function of F3; basically this means add all items needed to cart. 
* Fill in mandatory buyer information amount is held from the cart so no need to re-fill it here. 
* Post the transaction to pesapal 
* redirection is done to thank you page once payment is successful. 

### Note:
Card's cannot be tested on test at the moment of this writing; they are purely on production ; mobile money transactions are however possible to test using the sandbox. 

To use in production change the endpoint to production; additionally update the keys and call back URL.

## Checkout Process
1. Select and add items to cart
![Select and add Items to cart](https://github.com/alienwithin/F3-Pesapal/raw/master/sample-application/1-choose-items-to-buy.PNG "Pesapal Integration in FatFree")

2. Fill in Buyer information

![Fill in buyer information that is mandatory](https://github.com/alienwithin/F3-Pesapal/raw/master/sample-application/2-add-buyer-information.PNG "Pesapal Integration in FatFree")

3. Pay using preferred method on Pesapal

![Pay and confirm transaction on pesapal](https://github.com/alienwithin/F3-Pesapal/raw/master/sample-application/3-confirm-transaction.PNG "Pesapal Integration in FatFree")

4. Get redirected to vendor's call back page. 

![Get Redirected to thank you page](https://github.com/alienwithin/F3-Pesapal/raw/master/sample-application/4-buying-successful.PNG "Pesapal Integration in FatFree")
