<?php

namespace ShuGlobal\PG2c2pPaymentManager;

use Jose\Component\Core\JWK;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\Signature\Algorithm\PS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSLoader;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use ShuGlobal\RsaManager\RSAManager;

enum RSAPrivateKeyFormat {
    case PEM;
    case PFX;
}

class PG2C2PEncryptionManager
{
    private static ?PG2C2PEncryptionManager $sharedInstance = null;
    public ?JWK $publicKey_2c2p = null;
    public ?JWK $publicKey_merchant = null;
    public ?JWK $privateKey_merchant = null;

    public static function getInstance(): PG2C2PEncryptionManager {
        if (!isset(self::$sharedInstance)) {
            self::$sharedInstance = new PG2C2PEncryptionManager();
        }

        return self::$sharedInstance;
    }

    public function setupRSAKey(
        $publicKeyFile_2c2p,
        $publicKeyFile_merchant,
        $privateKeyFile_merchant,
        RSAPrivateKeyFormat $rsaFormat = RSAPrivateKeyFormat::PEM,
        $passphrase=null
    ) {
        $this->publicKey_2c2p = RSAManager::getPublicKey($publicKeyFile_2c2p);
        $this->publicKey_merchant = RSAManager::getPublicKey($publicKeyFile_merchant);

        if ($rsaFormat == RSAPrivateKeyFormat::PEM) {
            $this->privateKey_merchant = RSAManager::getPrivateKey($privateKeyFile_merchant, $passphrase);
        }
        else if ($rsaFormat == RSAPrivateKeyFormat::PFX) {
            $this->privateKey_merchant = RSAManager::getPFXPrivateKey(
                $privateKeyFile_merchant,
                $publicKeyFile_merchant,
                $passphrase
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function encryptDataAndSign($payload): string
    {
        $cipherText = PG2C2PEncryptionManager::getInstance()->encrypt($payload);
        return PG2C2PEncryptionManager::getInstance()->signSignature($cipherText);
    }

    /**
     * @throws \Exception
     */
    public function verifyAndDecrypt($data): string
    {
        $cipherText = PG2C2PEncryptionManager::getInstance()->verifySignature($data);
        return PG2C2PEncryptionManager::getInstance()->decrypt($cipherText);
    }

    // JSON Web Encryption

    /**
     * @throws \Exception
     */
    private function encrypt($payload, $key=null): string {
        if (!isset($key)) {
            $key = $this->publicKey_2c2p;
        }
        if (!isset($key)) {
            return throw new \Exception("Please setup RSA key.");
        }

        $keyAlgorithmMgr = new AlgorithmManager([
            new RSAOAEP()
        ]);

        $contentEncryptionAlgorithmMgr = new AlgorithmManager([
            new A256GCM()
        ]);

        $compressionMethodMgr = new CompressionMethodManager([
            new Deflate()
        ]);

        $jweBuilder = new JWEBuilder(
            $keyAlgorithmMgr,
            $contentEncryptionAlgorithmMgr,
            $compressionMethodMgr
        );

        $protectedHeader = [
            'alg' => 'RSA-OAEP',
            'enc' => 'A256GCM',
        ];

        $jwe = $jweBuilder
            ->create()
            ->withPayload($payload)
            ->withSharedProtectedHeader($protectedHeader)
            ->addRecipient($key)
            ->build();

        $serializer = new CompactSerializer();
        return $serializer->serialize($jwe, 0);
    }

    private function decrypt($cipherText, $key=null): ?string
    {
        if (!isset($key)) {
            $key = $this->privateKey_merchant;
        }
        if (!isset($key)) {
            return throw new \Exception("Please setup RSA key.");
        }

        $token = $cipherText;

        $keyAlgorithmMgr = new AlgorithmManager([
            new RSAOAEP()
        ]);

        $contentEncryptionAlgorithmMgr = new AlgorithmManager([
            new A256GCM()
        ]);

        $compressionMethodMgr = new CompressionMethodManager([
            new Deflate()
        ]);

        $serializerMgr = new JWESerializerManager([
            new CompactSerializer()
        ]);

        $jweDecrypter = new JWEDecrypter(
            $keyAlgorithmMgr,
            $contentEncryptionAlgorithmMgr,
            $compressionMethodMgr
        );

        $jweLoader = new JWELoader(
            $serializerMgr,
            $jweDecrypter,
            null
        );

        $sig = 0;
        $plainText = $jweLoader->loadAndDecryptWithKey($token, $key, $sig);

        return $plainText->getPayload();
    }

    // JSON Web Signature

    /**
     * @throws \Exception
     */
    private function signSignature($payload, $key=null): string
    {
        if (!isset($key)) {
            $key = $this->privateKey_merchant;
        }
        if (!isset($key)) {
            return throw new \Exception("Please setup RSA key.");
        }

        $algMgr = new AlgorithmManager([
            new PS256()
        ]);

        $jwsBuilder = new JWSBuilder(
            $algMgr
        );

        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($key, ['alg' => 'PS256', 'typ' => 'JWT'])
            ->build();

        $jwsSerializer = new \Jose\Component\Signature\Serializer\CompactSerializer();
        return $jwsSerializer->serialize($jws, 0);
    }

    /**
     * @throws \Exception
     */
    private function verifySignature($token, $key=null): ?string
    {
        if (!isset($key)) {
            $key = $this->publicKey_2c2p;
        }
        if (!isset($key)) {
            return throw new \Exception("Please setup RSA key.");
        }

        $serializerManager = new JWSSerializerManager([
            new \Jose\Component\Signature\Serializer\CompactSerializer()
        ]);
        $jws = $serializerManager->unserialize($token);

        $algMgr = new AlgorithmManager([
            new PS256()
        ]);

        $jwsVerifier = new JWSVerifier(
            $algMgr
        );

        $sig = 0;
        $isVerified = $jwsVerifier->verifyWithKey($jws, $key, $sig);

        if ($isVerified) {
            $jwsLoader = new JWSLoader(
                $serializerManager,
                $jwsVerifier,
                null
            );

            $jws = $jwsLoader->loadAndVerifyWithKey($token, $key, $sig);

            return $jws->getPayload();
        }

        return null;
    }
}