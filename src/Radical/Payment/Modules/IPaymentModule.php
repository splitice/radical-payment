<?php
namespace Radical\Payment\Modules;

use Radical\Payment\Components\IOrder;
use Radical\Payment\WebInterface\StandardWebInterface;

interface IPaymentModule {
	function purchase(IOrder $order, StandardWebInterface $web);
	function ipn();
}