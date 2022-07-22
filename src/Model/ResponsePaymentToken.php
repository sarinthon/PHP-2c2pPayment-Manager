<?php

namespace ShuGlobal\PG2c2pPaymentManager\Model;

use ShuGlobal\PG2c2pPaymentManager\PG2C2PManager;

class ResponsePaymentToken extends ResponsePayment
{
    public string $webPaymentUrl;
    public string $paymentToken;
    public string $respCode;
    public string $respDesc;

    public function __construct(string $jwt = null)
    {
        if ($jwt == null) {return;}
        $payload = PG2C2PManager::decode($jwt);

        if ( isset($payload) ) {
            $this->webPaymentUrl = $payload->webPaymentUrl;
            $this->paymentToken = $payload->paymentToken;
            $this->respCode = $payload->respCode;
            $this->respDesc = $payload->respDesc;
        }
    }
}