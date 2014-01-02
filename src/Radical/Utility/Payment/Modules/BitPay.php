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
		
		return $this->bitPay->createInvoice($order->id, $order->ammount, $order->name, $options);
	}
	function subscribe($ammount){
		
	}
	function ipn(){
		if(isset($this->p->ipn_data['payment_status']) && ($this->p->ipn_data['payment_status'] == 'Completed' || $this->p->ipn_data['payment_status'] == 'Reversed')) {
			$transaction = new Transaction();
			$transaction->id = $this->p->ipn_data['txn_id'];
			
			$transaction->gross = $this->p->ipn_data ['mc_gross'];
			$transaction->fee = $this->p->ipn_data['mc_fee'];
			$transaction->sender = $this->p->ipn_data['payer_email'];
			
			$order = new Order($transaction->gross - $transaction->fee);
			$order->name = $this->p->ipn_data['item_name'];
			$order->item = $this->p->ipn_data['item_number'];
			
			$transaction->order = $order;
			
			return $transaction;
		}
		
		//A message that we dont care about
		return true;
	}
}
