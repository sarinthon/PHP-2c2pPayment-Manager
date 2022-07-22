<?php

namespace ShuGlobal\PG2c2pPaymentManager\Model;

class ResponseRefundStatus extends ResponsePayment
{
    public string $timeStamp;
    public string $processType;
    public string $invoiceNo;
    public string $amount;
    public string $status;
    /*
     * RP = Refund Pending
     * RF = Refund confirmed
     * RR1 = Refund Rejected – insufficient balance
     * RR2 = Refund Rejected – invalid bank information
     * RR3 = Refund Rejected – bank account mismatch
     * RS = Ready for Settlement
     * S = Settled
     */
    public ?object $refundList;
}