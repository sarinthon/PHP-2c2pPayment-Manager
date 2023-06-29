<?php

namespace ShuGlobal\PG2c2pPaymentManager\Model;

use ShuGlobal\PG2c2pPaymentManager\PG2C2PManager;

class ResponsePaymentInquiry extends ResponsePayment
{
    public ?string $cardNo;
    public ?string $cardToken;
    public string $merchantID;
    public string $invoiceNo;
    public float $amount;
    public string $tranRef;
    public string $referenceNo;
    public string $transactionDateTime;
    public string $agentCode;
    public string $channelCode;
    public string $issuerCountry;
    public ?string $issuerBank;
    public ?string $userDefined1;
    public ?string $userDefined2;
    public ?string $userDefined3;
    public ?string $userDefined4;
    public ?string $userDefined5;

    public function __construct(string $jwt)
    {
        parent::__construct();

        $payload = PG2C2PManager::decode($jwt);

        if ( isset($payload) ) {
            $this->respCode = $payload->respCode;
            $this->respDesc = $payload->respDesc;

            $this->cardNo = $payload->cardNo ?? null;
            $this->cardToken = $payload->cardToken;
            $this->merchantID = $payload->merchantID ?? "";
            $this->invoiceNo = $payload->invoiceNo ?? "";
            $this->amount = $payload->amount ?? 0;
            $this->tranRef = $payload->tranRef ?? "";
            $this->referenceNo = $payload->referenceNo ?? "";
            $this->agentCode = $payload->agentCode ?? "";
            $this->channelCode = $payload->channelCode ?? "";
            $this->issuerCountry = $payload->issuerCountry ?? "";
            $this->issuerBank = $payload->issuerBank;

            $this->userDefined1 = $payload->userDefined1;
            $this->userDefined2 = $payload->userDefined2;
            $this->userDefined3 = $payload->userDefined3;
            $this->userDefined4 = $payload->userDefined4;
            $this->userDefined5 = $payload->userDefined5;
        }
    }
}