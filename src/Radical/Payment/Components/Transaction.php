<?php
namespace Radical\Payment\Components;

class Transaction {
	public $id;

    /**
     * @var Order
     */
	public $order;
	public $date;

	/** @var Customer|null */
	public $sender;
	
	public $gross;
	public $fee;

    public $ip;

    public $sandbox;
}