<?php
namespace Radical\Utility\Payment\Modules;

use Radical\Utility\Payment\Transaction;

use Radical\Utility\Payment\Order;

class BitPay implements IPaymentModule {
	protected $ipn;
	protected $account;
	protected $bitPay;
	
	function __construct($ipn,$account){
		$this->ipn = $ipn;
		$this->account = $account;
		
		$options = array();
		$options['notificationURL'] = $this->ipn . '?action=ipn';
		$options['redirectURL'] = $this->ipn . '?action=success';
		$options['currency'] = 'USD';
		$options['physical'] = 'false';
		$options['transactionSpeed'] = 'high';

		
		$this->bitPay = new \BitPay\BitPay(
				new \BitPay\Request\Curl,
				new \BitPay\Hash,
				$this->account,
				$options // array, optional
		);
	}
	
	function bill($order){
		if(!is_object($order))
			$order = new Order($order);
		
		$options = array();
		$options['itemCode'] = $order->item;
		$pos_data = implode('|',array($order->name,$order->item));
		
		return $this->bitPay->createInvoice($order->id, $order->ammount, $pos_data, $options);
	}
	function subscribe($ammount){
		
	}
	function ipn(){
		$post_data = file_get_contents("php://input");
		//file_put_contents('/tmp/test_post', file_get_contents("php://input"));
		//$post_data = file_get_contents("/tmp/test_post");
		
		$invoice = $this->bitPay->verifyNotification($post_data);
		
		if(!is_object($invoice)){
			die('ERR: Verification');
		}
		
		//Check the status
		if($invoice->status == 'confirmed') {
			$transaction = new Transaction();
			$transaction->id = $invoice->id;
			
			$transaction->gross = $invoice->price;
			$transaction->fee = 0;
			$transaction->sender = md5($_SERVER['REMOTE_ADDR']).'@bitpay.com';
			
			$order = new Order($transaction->gross - $transaction->fee);
			
			list($order->name, $order->item) = explode('|', $invoice->posData);
			
			$transaction->order = $order;
			
			return $transaction;
		}
		
		//A message that we dont care about
		return true;
	}
}
