<?php
namespace Radical\Utility\Payment;

class Order {
	public $ammount;
	public $name;
	public $item;
	public $address;
	public $id;
    public $data = array();
	
	function __construct($ammount){
		$this->ammount = $ammount;
	}
	
	function getIdRequired(){
        //If this gateway doesnt give IDs to transactions, then generate an id
		if($this->id === null){
			return time().rand(0, PHP_INT_MAX);
		}
		
		return $this->id;
	}
}