<?php

namespace ShuGlobal\PG2c2pPaymentManager\Model;

use ShuGlobal\Core\Model\BaseModel;

class ResponsePayout extends BaseModel
{
    public string $respCode; // https://developer.2c2p.com/docs/response-code-payout
    public string $respDesc;


    public string $version;
    public ?string $merchantID;

    public string $requestID; // UUID format
    public string $UTR; // Unique transaction reference (UTR)

    public string $payoutDate; // dd/MM/yyyy format
    public float $amount; // 2-decimal format

    public string $beneficiaryName;
    public ?string $beneficiaryBankCode;
    public ?string $beneficiaryBankName;

    public ?string $beneficiaryAccountNo;
    public ?string $beneficiaryMobileNo;
}