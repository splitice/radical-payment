<?php
namespace Radical\Payment\Modules;

use Radical\Payment\Components\Address;
use Radical\Payment\Components\Customer;
use Radical\Payment\Components\IOrder;
use Radical\Payment\Components\Order;
use Radical\Payment\Components\Transaction;
use Radical\Payment\External;
use Radical\Payment\Messages\FundsReturnMessage;
use Radical\Payment\Messages\IPNErrorMessage;
use Radical\Payment\Messages\NoHandleMessage;
use Radical\Payment\Messages\PaymentCompleteMessage;
use Radical\Payment\Messages\RecurringPaymentCancelled;
use Radical\Payment\Messages\ReversalMessage;
use Radical\Payment\Messages\TransactionMessage;
use Radical\Payment\WebInterface\StandardWebInterface;

class Paypal implements IPaymentModule {
	const SANDBOX_URL = 'https://sandbox.paypal.com/cgi-bin/webscr';

    private $sandbox = false;
	protected $client;
	
	function __construct($account, $sandbox = false){
		$this->client = new External\Paypal();
		
		if($sandbox) {
			$this->client->paypal_url = self::SANDBOX_URL;
			$this->sandbox = true;
		}
		
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

		$total_amt = $order->getAmmount();
		$this->client->add_field ( 'amount',  $total_amt);

		$tax = (float)$order->getTax();
		if($tax) {
            $this->client->add_field('tax', number_format($tax, 2));
        }
		
		if($order->getItem())
			$this->client->add_field ('item_number', $order->getItem() );

        $this->client->add_field('custom',$_SERVER['REMOTE_ADDR']);

        //todo iterate extra
        $this->client->add_field ('noshipping', 1 );
		
		$this->client->submit_paypal_post ();
	}

	private function handle_as_purchase($data){
		if(isset($data['initial_payment_status'])){
			$payment_status = $data['initial_payment_status'];
			$payment_ammount = $data['initial_payment_amount'];
			$txn_id = $data['initial_payment_txn_id'];


			$order = new Order($payment_ammount);
			$order->setName($data['product_name']);
			$order->setItem($data['recurring_payment_id']);
			$order->setRecurring(true);
			$order->setCurrency($data['mc_currency']);
			if(!empty($data['tax'])) $order->setTax((float)$data['tax']);
		} else if(isset($data['payment_status'])) {
			$payment_status = $data['payment_status'];
			$payment_ammount = $data['mc_gross'];
			$txn_id = $data['txn_id'];

			$order = new Order($payment_ammount);
			if(isset($data['recurring_payment_id'])){
				$order->setName($data['product_name']);
				$order->setItem($data['recurring_payment_id']);
				$order->setRecurring(true);
			} else {
				$order->setName($data['item_name']);
				$order->setItem($data['item_number']);
				$order->setRecurring(false);
			}
            $order->setCurrency($data['mc_currency']);
            if(!empty($data['tax'])) $order->setTax((float)$data['tax']);
		} else{
            return new IPNErrorMessage("Unknown status");
        }

		if($payment_status) {
			if ($payment_status == 'Completed' || $payment_status == 'Reversed' || $payment_status == 'Canceled_Reversal') {
				$transaction = new Transaction();
				$transaction->id = $txn_id;

				$transaction->gross = $payment_ammount;
				if (isset($data['mc_fee'])) {
					$transaction->fee = $data['mc_fee'];
				}

				$transaction->sender = new Customer($data['payer_email']);
				$transaction->sender->name = $data['first_name'] . ' ' . $data['last_name'];
				$transaction->sender->businessName = empty($data['payer_business_name']) ? null : $data['payer_business_name'];
				if (isset($data['custom'])) {
					$transaction->sender->ip = $data['custom'];
				}
				$transaction->sender->email = $data['payer_email'];
				$transaction->sender->contactPhone = empty($data['contact_phone']) ? null : $data['contact_phone'];


				if (isset($data['payment_status'])) {
					$transaction->sender->address->street = $data['address_street'];
					$transaction->sender->address->postcode = $data['address_zip'];
					$transaction->sender->address->state = $data['address_state'];
					$transaction->sender->address->city = $data['address_city'];
					$transaction->sender->address->country = $data['address_country_code'];
				}

				if($data['tax']){
				    $transaction->tax = (float)$data['tax'];
                }

				$order->additional = $data;

				$transaction->order = $order;

				if ($payment_status == 'Completed') {
					return new PaymentCompleteMessage($transaction);
				} elseif ($payment_status == 'Reversed') {
					$transaction->gross += $transaction->fee;//results in negative value!
					return new ReversalMessage('', $transaction);
				} elseif ($payment_status == 'Canceled_Reversal') {
					$transaction->gross += $transaction->fee;
					return new FundsReturnMessage('', $transaction);
				}
				return new NoHandleMessage();
			} else {
				return new IPNErrorMessage("Unknown message: " . $data['payment_status']);
			}
		}
	}

    private function handle_validated_ipn($data){
		$payment_status = null;

		if(!empty($data['txn_type'])) {
			switch ($data['txn_type']) {
				case 'new_case':
					$transaction = new Transaction();
					$transaction->id = $data['txn_id'];

					return new ReversalMessage('', $transaction);
				case 'recurring_payment_profile_cancel':
					return new RecurringPaymentCancelled($data['recurring_payment_id']);
				case 'recurring_payment':
				case 'recurring_payment_profile_created':
				case 'subscr_payment':
				case 'web_accept':
                case 'pro_hosted':
				case 'express_checkout':
				case 'merch_pmt':
					$ret = $this->handle_as_purchase($data);
					if ($ret) {
						return $ret;
					}
			}
		}else{
			$ret = $this->handle_as_purchase($data);
			if ($ret) {
				return $ret;
			}
		}

        //A message that we dont care about
        return new NoHandleMessage();
    }

	function ipn(){
	    if(!strpos($_SERVER['HTTP_USER_AGENT'], 'https://www.paypal.com/ipn')){
	        return;
        }
		if ($this->client->validate_ipn ()){
            $data = $this->client->ipn_data;
			return $this->handle_validated_ipn($data);
		}else{
			return new IPNErrorMessage("Unable to validate");
		}
	}

	function test_ipn($data){
		return $this->handle_validated_ipn($data);
	}
}
