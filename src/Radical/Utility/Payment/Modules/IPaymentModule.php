<?php
namespace Radical\Utility\Payment\Modules;

interface IPaymentModule {
	function bill($ammount);
	function subscribe($monthly_ammount);
	function ipn();
}