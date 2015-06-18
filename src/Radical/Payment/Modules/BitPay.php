<?php
namespace Radical\Payment\Modules;

use Radical\Payment\Components\IOrder;
use Radical\Payment\Components\Order;
use Radical\Payment\Components\Transaction;
use Radical\Payment\Messages\IPNErrorMessage;
use Radical\Payment\WebInterface\StandardWebInterface;

class BitPay implements IPaymentModule {
	protected $bitPay;
	
	function __construct($account){
		$options = array();
		$options['currency'] = 'USD';
		$options['physical'] = 'false';
		$options['transactionSpeed'] = 'high';

		
		$this->bitPay = new \BitPay\BitPay(
				new \BitPay\Request\Curl,
				new \BitPay\Hash,
                $account,
				$options // array, optional
		);
	}

    function purchase(IOrder $order, StandardWebInterface $web){
		$options = array();
		$options['itemCode'] = $order->getItem();
        $options['notificationURL'] = $web->payment_build_url('ipn');
        $options['redirectURL'] = $web->payment_build_url('success');

		$pos_data = implode('|',array($order->getName(),$order->getItem()));
		
		return $this->bitPay->createInvoice($order->getId(), $order->getAmmount(), $pos_data, $options);
	}
	function ipn(){
		$post_data = file_get_contents("php://input");
		
		$invoice = $this->bitPay->verifyNotification($post_data);
		
		if(!is_object($invoice)){
			return new IPNErrorMessage("Not valid IPN");
		}
		
		//Check the status
		if($invoice->status == 'confirmed') {
			$transaction = new Transaction();
			$transaction->id = $invoice->id;
			
			$transaction->gross = $invoice->price;
			$transaction->fee = 0;
			$transaction->sender = md5($_SERVER['REMOTE_ADDR']).'@client.bitpay.com';
			
			$order = new Order($transaction->gross);
			
			list($name, $item) = explode('|', $invoice->posData);
			$order->setName($name);
            $order->setItem($item);

			$transaction->order = $order;
			
			return $transaction;
		}
		
		//A message that we dont care about
		return true;
	}
}