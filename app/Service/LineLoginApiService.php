<?php

namespace App\Service;

use GuzzleHttp\Client;

class LineLoginApiService
{
    public static function verifyAccessToken(string $accessToken)
    {
        $client = new Client();
        $response = $client->request(
            'GET',
            'https://api.line.me/oauth2/v2.1/verify',
            [
                'query' => [
                    'access_token' => $accessToken,
                ],
                'http_errors' => false //404エラーも通す指定
            ]
        );

        if ($response->getStatusCode() !== 200) {
            return [
                'status' => 'error',
                'message' => 'invalid access token',
            ];
        }

        $responseData = json_decode($response->getBody(), true);

        if ($responseData['client_id'] !== config('line.liff_channel_id') || !$responseData['expires_in'] > 0) {
            return [
                'status' => 'error',
                'message' => 'invalid access token',
            ];
        }

        return [
            'status' => 'success',
            'message' => 'valid access token',
        ];
    }

    public static function getProfileFromAccessToken(string $accessToken)
    {
        $client = new Client();
        $response = $client->request(
            'GET',
            'https://api.line.me/v2/profile',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
                'http_errors' => false,
            ],
        );

        \Log::debug($response->getStatusCode());
        \Log::debug(json_decode($response->getBody(), true));

        if ($response->getStatusCode() !== 200) {
            return [
                'status' => 'error',
                'message' => 'invalid access token',
            ];
        }

        $response = json_decode($response->getBody(), true);

        return [
            'status' => 'success',
            'message' => 'valid access token',
            'data' => $response,
        ];
    }
}
