<?php

namespace App\Trait;

use GuzzleHttp\Client;

trait LineLoginApiAuthTrait
{
    public function getLineUserIdFromAccessToken(string $accessToken) {

        $verifyResult = $this->verifyAccessToken($accessToken);
        if ($verifyResult['status'] === 'error') {
            return null;
        }

        $profileResult = $this->getProfileFromAccessToken($accessToken);
        if ($profileResult['status'] === 'error') {
            return null;
        }

        return $profileResult['data']['userId'];
    }

    public function verifyAccessToken(string $accessToken)
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

    public function getProfileFromAccessToken(string $accessToken)
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
