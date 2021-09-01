<?php
namespace Radical\Payment\Components;


class Customer
{
	public $businessName;
	public $name;
	public $contactPhone;
	public $account;
	public $email;
	public $ip;

	/**
	 * @var Address
	 */
	public $address;

	public function __construct($account = null)
	{
		$this->address = new Address();
		$this->account = $account;
	}
}