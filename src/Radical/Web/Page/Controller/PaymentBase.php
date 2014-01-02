<?php
namespace Radical\Web\Page\Controller;

use Radical\Utility\Payment\Order;
use Radical\Web\Page\Handler\HTMLPageBase;
use Radical\Utility\Payment;
use Radical\Web\Page\Controller\Special\Redirect;

abstract class PaymentBase extends HTMLPageBase {
	protected $system;
	
	function __construct($data,$address = null,$extra = null){		
		$this->system = new Payment\UpstreamSystem($this,$data['module'],$address,$extra);
	}
	
	/**
	 * @return Order
	 */
	abstract function getOrder();
	
	private function _GET(){
		if(isset($_GET['action']))
			return $this->system->process();
		else{
			$order = $this->getOrder();
			if($order){
				if($order instanceof Redirect)
					return $order;
				
				return $this->bill($order);
			}
		}
	}
	
	protected function bill(Order $order){
		return $this->system->bill($order);
	}
	
	/**
	 * Handle GET request
	 *
	 * @throws \Exception
	 */
	function GET(){
		return $this->_GET();
	}

	/**
	 * Handle POST request
	 *
	 * @throws \Exception
	 */
	function POST(){
		return $this->_GET();
	}
}