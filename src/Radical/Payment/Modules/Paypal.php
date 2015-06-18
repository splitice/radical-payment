<?php
namespace Radical\Payment\Modules;

use Radical\Payment\Components\IOrder;
use Radical\Payment\Components\Order;
use Radical\Payment\Components\Transaction;
use Radical\Payment\Messages\FundsReturnMessage;
use Radical\Payment\Messages\PaymentCompleteMessage;
use Radical\Payment\Messages\ReversalMessage;
use Radical\Payment\Messages\TransactionMessage;
use Radical\Payment\External;
use Radical\Payment\WebInterface\StandardWebInterface;

class Paypal implements IPaymentModule {
	const SANDBOX_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

    private $sandbox;
	protected $client;
	
	function __construct($account){
		$this->client = new External\Paypal();
		
		if($this->sandbox)
			$this->client->paypal_url = self::SANDBOX_URL;
		
		$this->client->add_field ( 'business', $account );
	}

	function sandboxed($is = null){
        if($is === null){
            return $this->sandbox;
        }
		$this->sandbox = $is;
		if($is)
			$this->client->paypal_url = self::SANDBOX_URL;
	}

	function purchase(IOrder $order, StandardWebInterface $web){
        $this->client->add_field ( 'return', $web->payment_build_url('success') );
        $this->client->add_field ( 'cancel_return', $web->payment_build_url('cancel') );

		if($order->getName())
			$this->client->add_field ( 'item_name', $order->getName() );
		
		$this->client->add_field ( 'amount', $order->getAmmount() );
		
		if($order->getItem())
			$this->client->add_field ('item_number', $order->getItem() );

        //todo iterate extra
        $this->client->add_field ('noshipping', 1 );
		
		$this->client->submit_paypal_post ();
	}

    private function handle_validated_ipn($data){
        if(isset($data['payment_status'])){
            if($data['payment_status'] == 'Completed' || $data['payment_status'] == 'Reversed' || $data['payment_status'] == 'Canceled_Reversal'){
                $transaction = new Transaction();
                $transaction->id = $data['txn_id'];

                $transaction->gross = $data ['mc_gross'];
                $transaction->fee = $data['mc_fee'];
                $transaction->sender = $data['payer_email'];

                $order = new Order($transaction->gross);
                $order->setName($data['item_name']);
                $order->setItem($data['item_number']);
                $order->additional = $data;

                $transaction->order = $order;

                $payment_status = $data['payment_status'];
                if($data['payment_status'] == 'Completed') {
                    return new PaymentCompleteMessage($transaction);
                } elseif($payment_status == 'Reversed') {
                    return new ReversalMessage('',$transaction);
                } elseif($payment_status == 'Canceled_Reversal'){
                    return new FundsReturnMessage('', $transaction);
                }
            }
        }

        //A message that we dont care about
        return true;
    }

	function ipn(){
		if ($this->client->validate_ipn ()){
            $data = $this->client->ipn_data;
			return $this->handle_validated_ipn($data);
		}
	}
}
