<?php
namespace Radical\Payment\Messages;


class ErrorMessage implements IPaymentMessage {
    private $message;

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function __construct($message){
        $this->message = $message;
    }
}