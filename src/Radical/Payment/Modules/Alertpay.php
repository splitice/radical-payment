<?php
namespace Radical\Payment\Modules;
use Radical\Payment\Components\Customer;
use Radical\Payment\Components\IOrder;
use Radical\Payment\Components\Order;
use Radical\Payment\Components\Transaction;
use Radical\Payment\External;
use Radical\Payment\Messages\FundsReturnMessage;
use Radical\Payment\Messages\IPNErrorMessage;
use Radical\Payment\Messages\NoHandleMessage;
use Radical\Payment\Messages\PaymentCompleteMessage;
use Radical\Payment\Messages\ReversalMessage;
use Radical\Payment\WebInterface\StandardWebInterface;

class Alertpay implements IPaymentModule {
	protected $client;
	private $security_code;
    private $sandbox;
	
	function __construct($account,$security_code){
		$this->security_code = $security_code;
		$this->client = new External\Alertpay();
		
		$this->client->add_field ( 'ap_merchant', $account );
	}
	function sandboxed($is=null){
        if($is===null){
            return $this->sandbox;
        }
		$this->sandbox = $is;
		if($is){
			$this->client->url = External\Alertpay::URL_SANDBOX;
			$this->client->ipn = External\Alertpay::IPN_SANDBOX;
		}else{
			$this->client->url = External\Alertpay::URL;
			$this->client->ipn = External\Alertpay::IPN;
		}
	}
    function purchase(IOrder $order, StandardWebInterface $web){
        $this->client->add_field ( 'ap_returnurl', $web->payment_build_url('success') );
        $this->client->add_field ( 'ap_cancelurl', $web->payment_build_url('cancel') );
        $this->client->add_field ( 'notify_url', $web->payment_build_url('ipn') );
		
		if($order->getName())
			$this->client->add_field ( 'ap_itemname', $order->getName() );
		
		$this->client->add_field ( 'ap_amount', $order->getAmmount() );
		
		if($order->getItem())
			$this->client->add_field ('ap_itemcode', $order->getItem() );

        $this->client->add_field('apc_1', $_SERVER['REMOTE_ADDR']);

		$this->client->submit ();
	}

    private function handle_validated_ipn($data){
        if(isset($data['ap_transactionstate'])) {
			$transaction = new Transaction();
			$transaction->id = $data['ap_referencenumber'];

			$transaction->gross = $data ['ap_totalamount'];
			$transaction->fee = $data['ap_feeamount'];

			$transaction->sender = new Customer($data['ap_custemailaddress']);
			$transaction->sender->email = $data['ap_custemailaddress'];
			$transaction->sender->name = $data['ap_custfirstname'] . ' ' . $data['ap_custlastname'];
			$transaction->sender->businessName = null;
			$transaction->sender->ip = $data['apc_1'];
			$transaction->sender->address->street = $data['ap_custaddress'];
			$transaction->sender->address->postcode = $data['ap_custzip'];
			$transaction->sender->address->state = $data['ap_custstate'];
			$transaction->sender->address->city = $data['ap_custcity'];
			$transaction->sender->address->country = $data['ap_custcountry'];

			$order = new Order($transaction->gross);
			$order->setName($data['ap_itemname']);
			$order->setItem($data['ap_itemcode']);
			$order->setAdditional($data);

			$transaction->order = $order;

			$payment_status = $data['ap_transactionstate'];
			if ($data['ap_notificationtype'] == 'New' && ($payment_status == 'Completed' || $payment_status == 'On Hold')) {
				return new PaymentCompleteMessage($transaction);
			} elseif ($payment_status == 'Reversed') {
				return new ReversalMessage('', $transaction);
			} elseif ($payment_status == 'Refunded') {
				return new FundsReturnMessage('', $transaction);
			}
		}

        //A message that we dont care about
        return new NoHandleMessage();
    }

    //header('X-Error: IPN Validation',true,500);
    function ipn(){
        if ($this->client->validate_ipn ($this->security_code) && $this->client->ipn_data['ap_status'] == 'Success'){
            $data = $this->client->ipn_data;
            return $this->handle_validated_ipn($data);
        }else{
        	return new IPNErrorMessage("Unable to validate");
		}
    }
}