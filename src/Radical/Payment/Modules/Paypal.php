<?php
namespace Radical\Payment\Modules;

use Radical\Payment\Components\Address;
use Radical\Payment\Components\Customer;
use Radical\Payment\Components\IOrder;
use Radical\Payment\Components\Order;
use Radical\Payment\Components\Transaction;
use Radical\Payment\External;
use Radical\Payment\Messages\FundsReturnMessage;
use Radical\Payment\Messages\PaymentCompleteMessage;
use Radical\Payment\Messages\ReversalMessage;
use Radical\Payment\Messages\TransactionMessage;
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

        $this->client->add_field('custom',$_SERVER['REMOTE_ADDR']);

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

				$transaction->sender = new Customer($data['payer_email']);
				$transaction->sender->name = $data['first_name'] . ' ' . $data['last_name'];
				$transaction->sender->businessName = empty($data['payer_business_name'])?null:$data['payer_business_name'];
				$transaction->sender->ip = $data['custom'];
				$transaction->sender->email = $data['payer_email'];
				$transaction->sender->contactPhone = empty($data['contact_phone'])?null:$data['contact_phone'];
				$transaction->sender->address->street = $data['address_street'];
				$transaction->sender->address->postcode = $data['address_zip'];
				$transaction->sender->address->state = $data['address_state'];
				$transaction->sender->address->city = $data['address_city'];
				$transaction->sender->address->country = $data['address_country_code'];

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
            }else{
                echo "Unknown message: " . $data['payment_status'];
            }
        }

        //A message that we dont care about
        return null;
    }

	function ipn(){
		if ($this->client->validate_ipn ()){
            $data = $this->client->ipn_data;
			return $this->handle_validated_ipn($data);
		}
	}

	function test_ipn($data){
		return $this->handle_validated_ipn($data);
	}
}
