<?php

namespace ShuGlobal\PG2c2pPaymentManager\Model;

use ShuGlobal\Core\Model\BaseModel;

class ResponseFXRateInquiry extends BaseModel
{
    public string $responseCode;
    public string $respReason;
    public ?object $baseCurrency;
    public ?object $currencyList;
}