<?php

namespace ShuGlobal\NetworkManager;

use GuzzleHttp\Client;

enum NetworkMethod: string {
    case GET = "GET";
    case POST = "POST";
}

class NetworkManager
{
    public static function request($url, $body, $headers=null, NetworkMethod $method=NetworkMethod::POST): ?object {
        $client = new Client();
        $response = $client->request($method->value, $url, [
            'body'=> $body,
            'headers' => $headers ?? [
                    'Accept' => 'text/plain',
                    'Content-Type' => 'application/json'
                ],
        ]);
        // Response
        $responseBody = $response->getBody();

        if (($headers['Accept'] ?? "") == "application/jose") {
            return str($responseBody->getContents());
        }

        return json_decode($responseBody, false);
    }
}