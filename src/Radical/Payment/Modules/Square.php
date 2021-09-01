<?php
namespace Radical\Payment\Modules;

use Radical\Payment\Components\IOrder;
use Radical\Payment\Components\Order;
use Radical\Payment\Components\Transaction;
use Radical\Payment\Messages\IPNErrorMessage;
use Radical\Payment\Messages\NoHandleMessage;
use Radical\Payment\Messages\PaymentCompleteMessage;
use Radical\Payment\WebInterface\StandardWebInterface;
use Radical\Web\Page\Controller\Special\Redirect;
use Square\Models\CreatePaymentResponse;

class Square {
	protected $accessToken;
	protected $locationId;

	function __construct($accessToken, $locationId, $environment)
    {
        $this->accessToken = $accessToken;
        $this->locationId = $locationId;

        $this->client = new \Square\SquareClient([
            'accessToken' => $accessToken,
            'environment' => $environment,
        ]);
    }

	function getCurrency($currency){
	    switch($currency){
            case "AUD":
                return \Square\Models\Currency::AUD;
        }
    }

    function isSandbox(){
	    return $this->client->getEnvironment() === 'sandbox';
    }

    /**
     * @param $body_sourceId
     * @param $body_idempotencyKey
     * @param $amount
     * @param $currency
     * @param $referenceId
     * @param $description
     * @return CreatePaymentResponse|null
     * @throws \Square\Exceptions\ApiException
     */
	function submitApi($body_sourceId, $body_idempotencyKey, $amount, $currency, $referenceId, $description){
        $paymentsApi = $this->client->getPaymentsApi();

        $body_amountMoney = new \Square\Models\Money;
        $body_amountMoney->setAmount($amount);
        $body_amountMoney->setCurrency($this->getCurrency($currency));
        $body = new \Square\Models\CreatePaymentRequest(
            $body_sourceId,
            $body_idempotencyKey,
            $body_amountMoney
        );
        //$body->setDelayDuration('delay_duration6');
        $body->setAutocomplete(true);
        $body->setLocationId($this->locationId);
        $body->setReferenceId($referenceId);
        //$body->setDelayDuration('PT1H');
        $body->setNote($description);

        $apiResponse = $paymentsApi->createPayment($body);

        if ($apiResponse->isSuccess()) {
            return $apiResponse->getResult();
        } else {
            $errors = $apiResponse->getErrors();
            if(count($errors)){
                throw new \Exception(json_encode($errors[0]));
            }
        }

        return null;
    }

    function getReferenceForPayment($paymentId){
        $paymentsApi = $this->client->getPaymentsApi();

        $payment = $paymentsApi->getPayment($paymentId);
        if($payment->isError()){
            throw new \Exception("Error getting payment ".$paymentId);
        }
        $body = $payment->getBody();
        if(is_string($body)){
            $body = json_decode($body, true);
            return $body['payment']['reference_id'];
        }
        return $payment->getBody()->getReferenceId();
    }
    function purchase(IOrder $order, StandardWebInterface $web){
		return null;
	}
}
