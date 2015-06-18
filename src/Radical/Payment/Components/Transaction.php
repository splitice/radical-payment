<?php
namespace Radical\Payment\Components;

class Transaction {
	public $id;

    /**
     * @var Order
     */
	public $order;
	public $date;
	public $sender;
	
	public $gross;
	public $fee;

    public $sandbox;
}