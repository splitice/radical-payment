<?php
namespace Radical\Utility\Payment;

abstract class System {
	protected $module;
	function __construct($module,$arg = null,$arg2=null){
		$class = 'Radical\\Utility\\Payment\\Modules\\'.$module;
		if(!class_exists($class)){
			throw new \Exception('Payment module "'.$module.'" doesnt exist');
		}
		$this->module = new $class($this->toUrl(),$arg,$arg2);
	}
	
	function bill($ammount){
		return $this->module->bill($ammount);
	}
	
	function subscribe($ammount){
		return $this->module->subscribe($ammount);
	}
	
	function process(){
		switch ($_GET['action']) {
			case 'success' : // Order was successful...
				return $this->onSuccess();
		
			case 'cancel' : // Order was canceled...
				return $this->onCancel();
		
			case 'ipn': // Paypal is calling page for IPN validation...
				$transaction = $this->module->ipn();
				if($transaction){
					if($transaction === true){
						die('Not Handling');
					}
					$this->onReceived($transaction);
					die('Done');
				}else{
					header('Content-Type: text/plain',true,500);
					die('Not IPN request');
				}
			default:
				header('Content-Type: text/plain',true,404);
				die('Unknown Action');
		}
	}
	
	function __call($method,$arguments){
		return call_user_func_array(array($this->module,$method),$arguments);
	}
	
	/* Overloadables */
	abstract function toUrl();
	abstract function onReceived(Transaction $p);
	abstract function onSuccess();
	function onCancel(){
		echo "<html><head><title>Canceled</title></head><body><h3>The order was canceled.</h3>";
		echo "</body></html>";
	}
}