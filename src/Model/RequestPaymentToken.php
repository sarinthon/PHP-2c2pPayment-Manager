<?php

namespace ShuGlobal\PG2c2pPaymentManager\Model;

class RequestPaymentToken
{
    public string $merchantID;
    public string $currencyCode;

    public $paymentExpiry;
    public string $invoiceNo;
    public string $description;
    public float $amount;

    public string $frontendReturnUrl;
    public string $backendReturnUrl;

    public bool $tokenize = true;
    public array $cardTokens = [];

    public string $locale = "th";

    public $paymentChannel = ["ALL"];

//    public $installmentPeriodFilter = [6, 12];

    public function __construct()
    {
        $this->merchantID = env("PG_2C2P_MERCHANT_ID");
        $this->currencyCode = env("PG_2C2P_CURRENCY");
    }
}