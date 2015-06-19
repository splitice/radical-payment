<?php
namespace Radical\Payment\External;


/*******************************************************************************
 * PHP Paypal IPN Integration Class
 *******************************************************************************
 * Origional Author:     Micah Carrick
 * Modifications by:	 SplitIce
 *
 *******************************************************************************
 * DESCRIPTION:
 *
 * This file provides a neat and simple method to interface with paypal and
 * The paypal Instant Payment Notification (IPN) interface.  This file is
 * NOT intended to make the paypal integration "plug 'n' play". It still
 * requires the developer (that should be you) to understand the paypal
 * process and know the variables you want/need to pass to paypal to
 * achieve what you want.  
 *
 * This class handles the submission of an order to paypal aswell as the
 * processing an Instant Payment Notification.
 * 
 * This code is based on that of the php-toolkit from paypal.  I've taken
 * the basic principals and put it in to a class so that it is a little
 * easier--at least for me--to use.  The php-toolkit can be downloaded from
 * http://sourceforge.net/projects/paypal.
 * 
 * To submit an order to paypal, have your order form POST to a file with:
 *
 * $p = new paypal_class;
 * $p->add_field('business', 'somebody@domain.com');
 * $p->add_field('first_name', $_POST['first_name']);
 * ... (add all your fields in the same manor)
 * $p->submit_paypal_post();
 *
 * To process an IPN, have your IPN processing file contain:
 *
 * $p = new paypal_class;
 * if ($p->validate_ipn()) {
 * ... (IPN is verified.  Details are in the ipn_data() array)
 * }
 *
 *
 * In case you are new to paypal, here is some information to help you:
 *
 * 1. Download and read the Merchant User Manual and Integration Guide from
 * http://www.paypal.com/en_US/pdf/integration_guide.pdf.  This gives 
 * you all the information you need including the fields you can pass to
 * paypal (using add_field() with this class) aswell as all the fields
 * that are returned in an IPN post (stored in the ipn_data() array in
 * this class).  It also diagrams the entire transaction process.
 *
 * 2. Create a "sandbox" account for a buyer and a seller.  This is just
 * a test account(s) that allow you to test your site from both the 
 * seller and buyer perspective.  The instructions for this is available
 * at https://developer.paypal.com/ as well as a great forum where you
 * can ask all your paypal integration questions.  Make sure you follow
 * all the directions in setting up a sandbox test environment, including
 * the addition of fake bank accounts and credit cards.
 * 
 *******************************************************************************
 */

class Paypal {
	
	var $last_error; // holds the last error encountered
	

	var $ipn_log; // bool: log IPN results to text file?
	

	var $ipn_log_file; // filename of the IPN log
	var $ipn_response; // holds the IPN response from paypal   
	var $ipn_data = array (); // array contains the POST values for IPN
	

	var $fields = array (); // array holds the fields to submit to paypal
	

	function __construct() {
		
		// initialization constructor.  Called when class is created.
		

		$this->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
		
		$this->last_error = '';
		
		$this->ipn_log_file = '.ipn_results.log';
		$this->ipn_log = true;
		$this->ipn_response = '';
		
		// populate $fields array with a few default values.  See the paypal
		// documentation for a list of fields and their data types. These defaul
		// values can be overwritten by the calling script.
		

		$this->add_field ( 'rm', '2' ); // Return method = POST
		$this->add_field ( 'cmd', '_xclick' );
        $this->add_field ( 'charset', 'utf-8' );
	
	}
	
	function add_field($field, $value) {
		
		// adds a key=>value pair to the fields array, which is what will be 
		// sent to paypal as POST variables.  If the value is already in the 
		// array, it will be overwritten.
		

		$this->fields [$field] = $value;
	}
	
	function submit(){
		return $this->submit_paypal_post();
	}
	
	function submit_paypal_post() {
		
		// this function actually generates an entire HTML page consisting of
		// a form with hidden elements which is submitted to paypal via the 
		// BODY element's onLoad attribute.  We do this so that you can validate
		// any POST vars from you custom form before submitting to paypal.  So 
		// basically, you'll have your own form which is submitted to your script
		// to validate the data, which in turn calls this function to create
		// another hidden form and submit to paypal.
		

		// The user will briefly see a message on the screen that reads:
		// "Please wait, your order is being processed..." and then immediately
		// is redirected to paypal.
		

		echo "<html>\n";
		echo "<head><title>Processing Payment...</title></head>\n";
		echo "<body onLoad=\"document.forms['paypal_form'].submit();\">\n";
		echo "<center><h2>Please wait, your order is being processed and you";
		echo " will be redirected to the paypal website.</h2></center>\n";
		echo "<form method=\"post\" name=\"paypal_form\" ";
		echo "action=\"" . $this->paypal_url . "\">\n";
		
		foreach ( $this->fields as $name => $value ) {
			echo "<input type=\"hidden\" name=\"$name\" value=\"$value\"/>\n";
		}
		echo "<center><br/><br/>If you are not automatically redirected to ";
		echo "paypal within 5 seconds...<br/><br/>\n";
		echo "<input type=\"submit\" value=\"Click Here\"></center>\n";
		
		echo "</form>\n";
		echo "</body></html>\n";
	
	}

    function get_postdata(){
        //https://developer.paypal.com/webapps/developer/docs/classic/ipn/ht_ipn/
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        foreach ($raw_post_array as $keyval) {
            $keyval = explode ('=', $keyval);
            if (count($keyval) == 2)
                $myPost[$keyval[0]] = urldecode($keyval[1]);
        }
        // read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
        $req = 'cmd=_notify-validate';
        if(function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }
        foreach ($myPost as $key => $value) {
            if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&$key=$value";
        }
        return $req;
    }
	
	function validate_ipn() {
		// generate the post string from the _POST vars aswell as load the
		// _POST vars into an arry so we can play with them from the calling
		// script.
        $req = $this->get_postdata();

		// open the connection to paypal
        $this->ipn_data = $_POST;
        $ch = curl_init($this->paypal_url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        $response = curl_exec($ch);
		if (! $response) {
			
			// could not open the connection.  If loggin is on, the error message
			// will be in the log.
			$this->last_error = "error verifying with paypal: ".curl_error($ch);
			$this->log_ipn_results ( false );
            curl_close($ch);

            //If for some reason PayPal is down, just return that transaction was OK
			return true;
		
		} else {
            $this->ipn_response = $response;
		}

        curl_close($ch);
		
		if (strstr ( $this->ipn_response, "VERIFIED" )) {

			// Valid IPN transaction.
			$this->log_ipn_results ( true );
			return true;
		
		} else {
			
			// Invalid IPN transaction.  Check the log for details.
			$this->last_error = 'IPN Validation Failed.';
			$this->log_ipn_results ( false );
			return false;
		
		}
	
	}
	
	function log_ipn_results($success) {
		if (! $this->ipn_log)
			return; // is logging turned off?
		

		// Timestamp
		$text = '[' . date ( 'm/d/Y g:i A' ) . '] - ';
		
		// Success or failure being logged?
		if ($success)
			$text .= "SUCCESS!\n";
		else
			$text .= 'FAIL: ' . $this->last_error . "\n";
		
		// Log the POST variables
		$text .= "IPN POST Vars from Paypal:\n";
		foreach ( $this->ipn_data as $key => $value ) {
			$text .= "$key=$value, ";
		}
		
		// Log the response from the paypal server
		$text .= "\nIPN Response from Paypal Server:\n " . $this->ipn_response;
		
		//$log = new Logging('Paypal');
		//$log->log($text);
	}
}         

