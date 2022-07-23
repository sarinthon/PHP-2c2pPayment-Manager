<?php

namespace ShuGlobal\PG2c2pPaymentManager;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use ShuGlobal\NetworkManager\NetworkManager;
use ShuGlobal\PG2c2pPaymentManager\ENUM\PG2C2PCurrencyCode;
use ShuGlobal\PG2c2pPaymentManager\Model\RequestPaymentToken;
use ShuGlobal\PG2c2pPaymentManager\Model\ResponseFXRateInquiry;
use ShuGlobal\PG2c2pPaymentManager\Model\ResponsePaymentAction;
use ShuGlobal\PG2c2pPaymentManager\Model\ResponsePaymentInquiry;
use ShuGlobal\PG2c2pPaymentManager\Model\ResponsePaymentToken;
use ShuGlobal\PG2c2pPaymentManager\Model\ResponseRefundStatus;

enum PG2C2PProcessType: string
{
    case Inquiry = "I";
    case Settlement = "S";
    case VoidPayment = "V";
    case Refund = "R";
    case RefundStatus = "RS";
}

class PG2C2PManager
{
    private static function getHostURL(): string
    {
        if (env('PG_2C2P_ENV') == "SANDBOX") {
            return "https://sandbox-pgw.2c2p.com/payment/4.1";
        }
        return "https://pgw.2c2p.com/payment/4.1";
    }

    private static function getHostForPaymentAction(): string
    {
        if (env('PG_2C2P_ENV') == "SANDBOX") {
            return "https://demo2.2c2p.com/2C2PFrontend/PaymentAction/2.0/action";
        }
        return "https://t.2c2p.com/PaymentAction/2.0/action";
    }

    private static function setupRSAKey()
    {
        if (env('PG_2C2P_ENV') == "SANDBOX") {
            $publicKeyFile_2c2p = storage_path(env("PG_2C2P_CERT_2C2P_PK"));
            $publicKeyFile_merchant = storage_path(env("PG_2C2P_CERT_MERCHANT_PK"));
            $privateKeyFile_merchant = storage_path(env("PG_2C2P_CERT_MERCHANT_SK"));
            $passphrase_merchant = env("PG_2C2P_CERT_MERCHANT_PASSPHRASE");
        } else {
            $publicKeyFile_2c2p = storage_path(env("PG_2C2P_CERT_2C2P_PK"));
            $publicKeyFile_merchant = storage_path(env("PG_2C2P_CERT_MERCHANT_PK"));
            $privateKeyFile_merchant = storage_path(env("PG_2C2P_CERT_MERCHANT_SK"));
            $passphrase_merchant = env("PG_2C2P_CERT_MERCHANT_PASSPHRASE");
        }

        PG2C2PEncryptionManager::getInstance()->setupRSAKey(
            $publicKeyFile_2c2p,
            $publicKeyFile_merchant,
            $privateKeyFile_merchant,
            RSAPrivateKeyFormat::PFX,
            $passphrase_merchant
        );
    }

    public static function getPaymentToken(RequestPaymentToken $reqData): ResponsePaymentToken
    {
        $payload = $reqData;
        $jwtData = self::encode($payload);

        $url = self::getHostURL() . "/PaymentToken";
        $body = json_encode([
            'payload' => "$jwtData"
        ]);

        // Response
        $responseToken = NetworkManager::request($url, $body);

        $resPayload = $responseToken->payload ?? null;

        if (isset($resPayload)) {
            $response = new ResponsePaymentToken($resPayload);
        } else {
            $response = new ResponsePaymentToken();
            $response->respCode = $responseToken->respCode;
            $response->respDesc = $responseToken->respDesc;
        }
        $resCode = $response->respCode ?? "0";
        if ($resCode == "9015") {
            throw new \Exception(json_encode($response));
        }

        return $response;
    }

    public static function cancelTransaction(string $paymentToken): ?object
    {
        $url = self::getHostURL() . "/CancelTransaction";
        $body = json_encode([
            'paymentToken' => $paymentToken
        ]);

        return NetworkManager::request($url, $body);
    }

    public static function paymentInquiry($paymentToken, $invoiceNo)
    {
        $url = self::getHostURL() . "/paymentInquiry";
        $payload = [
            'merchantID' => env('PG_2C2P_MERCHANT_ID'),
            'paymentToken' => $paymentToken,
            'invoiceNo' => $invoiceNo
        ];

        $body = json_encode([
            "payload" => self::encode($payload)
        ]);

        // Response
        $response = NetworkManager::request($url, $body);
        $payload = $response->payload;

        return new ResponsePaymentInquiry($payload);
    }

    public static function transactionStatusInquiry($paymentToken): ?object
    {
        $url = self::getHostURL() . "/transactionStatus";
        $body = json_encode([
            'paymentToken' => $paymentToken,
            'additionalInfo' => true
        ]);

        // Response
        return NetworkManager::request($url, $body);
    }

    // Encoding & Decoding

    public static function encode($payload): string
    {
        return JWT::encode(
            $payload,
            env('PG_2C2P_SECRET_KEY'),
            env('JWT_ALGORITHM')
        );
    }

    public static function decode($token): ?object
    {
        try {
            return JWT::decode(
                $token,
                env('PG_2C2P_SECRET_KEY'),
                array(env('JWT_ALGORITHM'))
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function paymentOption(string $paymentToken): ?object
    {
        $body = json_encode([
            'paymentToken' => $paymentToken,
            'local' => App::currentLocale()
        ]);
        $url = self::getHostURL() . "/PaymentOption";
        return NetworkManager::request($url, $body);

    }

    // Payment Action

    // Able to void payment for credit card only (VISA, mastercard, JCB, UnionPay)
    public static function voidPayment($invoiceNo): ?ResponsePaymentAction
    {
        $merchantID = env("PG_2C2P_MERCHANT_ID");
        $processType = PG2C2PProcessType::VoidPayment->value;

        $data = "<PaymentProcessRequest>
  <version>3.8</version>
  <merchantID>" . $merchantID . "</merchantID>
  <invoiceNo>" . $invoiceNo . "</invoiceNo>
  <processType>" . $processType . "</processType>
</PaymentProcessRequest>";

        $json = self::paymentAction($data, true);
        if (isset($json)) {
            return new ResponsePaymentAction($json);
        }

        return null;
    }

    public static function refund($invoiceNo, $amount): ?ResponsePaymentAction
    {
        $merchantID = env("PG_2C2P_MERCHANT_ID");
        $processType = PG2C2PProcessType::Refund->value;

        $data = "<PaymentProcessRequest>
  <version>3.8</version>
  <merchantID>" . $merchantID . "</merchantID>
  <invoiceNo>" . $invoiceNo . "</invoiceNo>
  <actionAmount>" . $amount . "</actionAmount>
  <processType>" . $processType . "</processType>
</PaymentProcessRequest>";

        $json = self::paymentAction($data, true);
        if (isset($json)) {
            return new ResponsePaymentAction($json);
        }

        return null;
    }

    public static function refundStatusInquiry($invoiceNo): ?ResponseRefundStatus
    {
        $merchantID = env("PG_2C2P_MERCHANT_ID");
        $processType = PG2C2PProcessType::RefundStatus->value;

        $data = "<PaymentProcessRequest>
  <version>3.8</version>
  <merchantID>" . $merchantID . "</merchantID>
  <invoiceNo>" . $invoiceNo . "</invoiceNo>
  <processType>" . $processType . "</processType>
</PaymentProcessRequest>";

        $json = self::paymentAction($data, true);
        if (isset($json)) {
            return new ResponseRefundStatus($json);
        }

        return null;
    }

    public static function systemCanRefund($channelCode): bool
    {
        return in_array($channelCode, ["VI", "MA", "JC", "UP", "AL", "LP"]);
    }

    public static function shouldVoid($invoiceNo): bool
    {
        $data = self::refundStatusInquiry($invoiceNo);
        return $data->status == "A";
    }

    public static function foreignExchangeRate(): ?ResponseFXRateInquiry
    {
        $merchantID = env("PG_2C2P_MERCHANT_ID");
        $now = now()->format("dmyHis");

        $data = "<FxRateRequest>
  <version>2.1</version>
  <timeStamp>" . $now . "</timeStamp>
  <merchantID>" . $merchantID . "</merchantID>
  <currency>" . PG2C2PCurrencyCode::THB->value . "</currency>
  <hashValue>" . md5($now) . "</hashValue>
</FxRateRequest>";

        $json = self::paymentAction($data, true);
        if (isset($json)) {
            return new ResponseFXRateInquiry($json);
        }

        return null;
    }

    // Core function for payment action

    private static function paymentAction($data, $usingJAVA = false) {
        if (!$usingJAVA) {
            self::setupRSAKey();
        }
        $url = self::getHostForPaymentAction();

        $reqData = $usingJAVA ? self::encryptAndSignWithJAVA($data) : PG2C2PEncryptionManager::getInstance()->encryptDataAndSign($data);
        $headers = [
            'Accept' => 'application/jose',
            "Content-Type"=> "text/plain"
        ];

        try {
            // Response
            $response = NetworkManager::request($url, $reqData, $headers);
            $result = $usingJAVA ? self::verifyAndDecryptWithJAVA($response) : PG2C2PEncryptionManager::getInstance()->verifyAndDecrypt($response);

            return json_decode(json_encode(simplexml_load_string($result)));
        } catch (\Exception) {}

        return null;
    }

    // Data encryption and signing with JAVA

    private static function encryptAndSignWithJAVA($data) {
        return self::paymentActionWithJAVA("encrypt", $data);
    }

    private static function verifyAndDecryptWithJAVA($data) {
        return self::paymentActionWithJAVA("decrypt", $data);
    }

    private static function setupKeyURLOnConfigFile($configURL) {
        $merchantPriKeyURL = base_path().storage_path(env("PG_2C2P_CERT_MERCHANT_SK"));;
        $pubKeyURL2c2p = base_path().storage_path(env("PG_2C2P_CERT_2C2P_PK"));

        File::replace($configURL, '{"merchantPriKeyURL": "'.$merchantPriKeyURL.'","2c2pPubKeyURL": "'.$pubKeyURL2c2p.'"}');
    }

    private static function paymentActionWithJAVA($action, $data): ?string {
        $output = null;
        $resultCode = null;

        $path = base_path()."/vendor/shu-global/2c2p-payment-manager/src/2c2pPayment/";

        $jarURL = $path."2c2pPayment.jar";
        $configURL = $path."config.json";

        self::setupKeyURLOnConfigFile($configURL);

        exec(
            'java -jar '.$jarURL.' '.$configURL.' "'.$action.'" "'.$data.'"',
            $output,
            $resultCode
        );

        if (count($output) > 0) {
            $result = json_decode($output[0]);

            if ($action == "encrypt") {
                return $result->entries->dataSigned;
            } else {
                return $result->entries->plaintext;
            }
        }

        return null;
    }

}