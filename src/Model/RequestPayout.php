<?php

namespace ShuGlobal\PG2c2pPaymentManager\Model;

class RequestPayout
{
    public string $merchantID;
    public string $requestID; // UUID format
    public float $amount; // Exclude fee

    public string $beneficiaryName;
    public string $beneficiaryBankCode;
    public ?string $beneficiaryAccountNo;
    public ?string $beneficiaryMobileNo; // International mobile number format

    public function __construct()
    {
        $this->merchantID = env("PG_2C2P_MERCHANT_ID");
    }
}