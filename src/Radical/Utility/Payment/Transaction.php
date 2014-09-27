<?php
namespace Radical\Utility\Payment;

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
}