<?php

namespace ShuGlobal\PG2c2pPaymentManager\ENUM;

enum PG2C2PCurrencyCode: string {
    case THB = "764";
    case SGD = "702";
    case MYR = "458";
    case USD = "840";
    case IDR = "360";
    case TWD = "901";
    case HKD = "344";
    case PHP = "608";
    case MMK = "104";
    case EUR = "978";
    case JPY = "392";
    case AUD = "036";
    case BDT = "050";
    case CAD = "124";
    case CHF = "756";
    case CNY = "156";
    case DKK = "208";
    case GBP = "826";
    case HTG = "332";
    case KHR = "116";
    case KRW = "410";
    case LAK = "418";
    case NOK = "578";
    case NZD = "554";
    case RUB = "643";
    case SEK = "752";
    case VND = "704";
    case YER = "886";

    function getCurrencyName(){
        switch ($this) {
            case self::THB: return "Baht";
            case self::SGD: return "Singapore Dollar";
            case self::USD: return "US Dollar";
            case self::IDR: return "Indonesian Rupiah";
            case self::TWD: return "Taiwan Dollar";
            case self::HKD: return "Hong Kong Dollar";
            case self::PHP: return "Philippine Peso";
            case self::MMK: return "Myanmar Kyat";
            case self::EUR: return "Euro";
            case self::JPY: return "Yen";
            case self::AUD: return "Australian Dollar";
            case self::BDT: return "Bangladeshi Taka";
            case self::CAD: return "Canadian Dollar";
            case self::CHF: return "Swiss Franc";
            case self::CNY: return "Yuan Renminbi";
            case self::DKK: return "Danish Krone";
            case self::GBP: return "Pound Sterling";
            case self::HTG: return "Gourde";
            case self::KHR: return "Riel";
            case self::KRW: return "Korean Won";
            case self::LAK: return "Kip";
            case self::NOK: return "Norwegian Krone";
            case self::NZD: return "New Zealand Dollar";
            case self::RUB: return "Russian Ruble";
            case self::SEK: return "Swedish Krona";
            case self::VND: return "Viet Num Dong";
            case self::YER: return "Yemeni Rial";
            default: return null;
        }
    }
}