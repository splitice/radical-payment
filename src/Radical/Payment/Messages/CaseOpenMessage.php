<?php
namespace Radical\Payment\Messages;


use Radical\Payment\Components\Transaction;

class CaseOpenMessage implements IPaymentMessage {
    public $id;

    /**
     * @var Transaction
     */
    public $transaction;

    public function __construct($id, Transaction $trasaction){
        $this->id = $id;
        $this->transaction = $trasaction;
    }
}