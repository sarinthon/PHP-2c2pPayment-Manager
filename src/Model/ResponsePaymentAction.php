<?php

namespace ShuGlobal\PG2c2pPaymentManager\Model;

use ShuGlobal\Core\Model\BaseModel;

class ResponsePaymentAction extends BaseModel
{
    public string $request;

    public string $respCode;
    public string $respDesc;
}