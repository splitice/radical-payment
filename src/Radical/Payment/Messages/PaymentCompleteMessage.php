<?php


namespace Radical\Payment\Messages;


use Radical\Payment\Components\Transaction;

class PaymentCompleteMessage implements IPaymentMessage {
    /**
     * @var Transaction
     */
    public $transaction;

    public function __construct(Transaction $trasaction){
        $this->transaction = $trasaction;
    }

    function getId(){
        return $this->transaction->id;
    }
}