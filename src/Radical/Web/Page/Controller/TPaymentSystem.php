<?php
namespace Radical\Web\Page\Controller;

use Radical\Payment\Components\IOrder;
use Radical\Payment\Messages\IPaymentMessage;
use Radical\Payment\Modules\IPaymentModule;
use Radical\Payment\WebInterface\StandardWebInterface;

trait TPaymentSystem {
    protected abstract function payment_handle(IPaymentMessage $message);

    function payment_bill(IPaymentModule $system, IOrder $order, StandardWebInterface $web){
        $ret = $system->purchase($order, $web);
        if(is_object($ret) && $ret instanceof IPaymentMessage){
            return $this->payment_handle($ret);
        }
        return $ret;
    }

    function payment_action(IPaymentModule $system, StandardWebInterface $web){
        $action = $web->payment_get_action();
        switch($action) {
            case 'ipn':
                return $system->ipn();
            case 'successs':
            case 'cancel':
                return $action;
        }
    }

}