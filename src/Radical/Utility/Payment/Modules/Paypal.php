<?php
namespace Radical\Utility\Payment\Modules;

use Radical\Utility\Payment\Transaction;

use Radical\Utility\Payment\Order;
use Radical\Utility\Payment\External;

class Paypal implements IPaymentModule {
	const SANDBOX_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	
	protected $ipn;
	protected $account;
	protected $p;
	
	function __construct($ipn,$account){
		$this->ipn = $ipn;
		$this->account = $account;
		
		$this->p = new External\Paypal();
		
		if($this->sandbox)
			$this->p->paypal_url = self::SANDBOX_URL;
		
		$this->p->add_field ( 'business', $this->account );
		$this->p->add_field ( 'return', $this->ipn . '?action=success' );
		$this->p->add_field ( 'cancel_return', $this->ipn . '?action=cancel' );
		//$this->p->add_field ( 'notify_url', $this->ipn . '?action=ipn' );
	}
	private $sandbox;
	function sandboxed($is){
		$this->sandbox = $is;
		if($is)
			$this->p->paypal_url = self::SANDBOX_URL;
	}
	function bill($order){
		if(!is_object($order))
			$order = new Order($order);
		
		if($order->name)
			$this->p->add_field ( 'item_name', $order->name );
		
		$this->p->add_field ( 'amount', $order->ammount );
		
		if($order->item)
			$this->p->add_field ('item_number', $order->item );
		
		if(!$order->address)
			$this->p->add_field ('noshipping', 1 );
		
		$this->p->submit_paypal_post ();
	}
	function subscribe($ammount){
		
	}
	function ipn(){
		if ($this->p->validate_ipn ()){
			if(isset($this->p->ipn_data['payment_status']) && ($this->p->ipn_data['payment_status'] == 'Completed' || $this->p->ipn_data['payment_status'] == 'Reversed')) {
				$transaction = new Transaction();
				$transaction->id = $this->p->ipn_data['txn_id'];
				
				$transaction->gross = $this->p->ipn_data ['mc_gross'];
				$transaction->fee = $this->p->ipn_data['mc_fee'];
				$transaction->sender = $this->p->ipn_data['payer_email'];
				
				$order = new Order($transaction->gross);
				$order->name = $this->p->ipn_data['item_name'];
				$order->item = $this->p->ipn_data['item_number'];
                $order->data = $this->p->ipn_data;
				
				$transaction->order = $order;
				
				return $transaction;
			}
			
			//A message that we dont care about
			return true;
		}
	}
}
