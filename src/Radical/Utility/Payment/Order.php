<?php
namespace Radical\Utility\Payment;

class Order {
	public $ammount;
	public $name;
	public $item;
	public $address;
	public $id;
	
	function __construct($ammount){
		$this->ammount = $ammount;
	}
	
	function getIdRequired(){
		if($this->id === null){
			return time() ^ rand(PHP_INT_MAX*-1, PHP_INT_MAX);
		}
		
		return $this->id;
	}
}