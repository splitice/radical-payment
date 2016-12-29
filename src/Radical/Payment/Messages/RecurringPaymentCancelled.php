<?php
namespace Radical\Payment\Messages;


class RecurringPaymentCancelled implements IPaymentMessage
{
	public $id;

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	function __construct($id)
	{
		$this->id = $id;
	}
}