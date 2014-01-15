<?php
namespace Radical\Utility\Payment;

use Radical\Utility\Payment\Modules\IPaymentModule;
use Radical\CLI\Output\Log;

/**
 * When dealing with finances its important to log raw 
 * data reliably, so we implement a really low level logging
 * mechanism.
 * 
 * @author SplitIce
 *
 */
class Logging {
	private $module;
	private $log;
	
	function __construct($module, $id = null){
		if($module instanceof IPaymentModule){
			$module = array_pop(explode('\\',get_class($module)));
		}
		$this->module = $module;
		
		if($id === null){
			$id = date('Y-m-d').'_'.time().'_'.$module;
		}
		
		$this->log = Log::create($id,'payments');	
	}
	
	function log($text){
		$this->log->write('['.$this->module.'] '.$text );
	}
}