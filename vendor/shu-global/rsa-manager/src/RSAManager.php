<?php

namespace ShuGlobal\RsaManager;

use Jose\Component\Core\JWK;

class RSAManager
{
    public static function getPublicKey($keyFile) {
        $publicKey = file_get_contents( $keyFile );
        $keyInfo = openssl_pkey_get_details(openssl_pkey_get_public($publicKey));

        return new JWK([
            'kty'=> 'RSA',
            'n'=> self::getKey($keyInfo['rsa']['n']),
            'e'=> self::getKey($keyInfo['rsa']['e']),
        ]);
    }

    public static function getPrivateKey($keyFile, $passphrase=null) {
        $privateKey = file_get_contents( $keyFile );
        $keyInfo = openssl_pkey_get_details(openssl_pkey_get_private($privateKey, $passphrase));

        return new JWK([
            'kty'=> 'RSA',
            'n'=> self::getKey($keyInfo['rsa']['n']),
            'e'=> self::getKey($keyInfo['rsa']['e']),
            'p'=> self::getKey($keyInfo['rsa']['p']),
            'd'=> self::getKey($keyInfo['rsa']['d']),
        ]);
    }

    public static function getPFXPrivateKey($keyFile, $certFile, $passphrase=null) {
        $privateKey = file_get_contents( $keyFile );
        $publicKey = file_get_contents( $certFile );

        $certs = [
            $publicKey
        ];
        if (openssl_pkcs12_read($privateKey, $certs, $passphrase)) {
            $keyInfo = openssl_pkey_get_details( openssl_pkey_get_private($certs['pkey'], $passphrase) );

            return new JWK([
                'kty'=> 'RSA',
                'n'=> self::getKey($keyInfo['rsa']['n']),
                'e'=> self::getKey($keyInfo['rsa']['e']),
                'p'=> self::getKey($keyInfo['rsa']['p']),
                'd'=> self::getKey($keyInfo['rsa']['d']),
            ]);
        }

        return null;
    }

    private static function getKey($keyInfo) {
        return rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($keyInfo)), '=');
    }
}