<?php

declare(strict_types=1);

namespace Expando\LocoGraphQLPackage;

use Expando\LocoGraphQLPackage\Exceptions\AppException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JetBrains\PhpStorm\ArrayShape;

class App
{
    private array $token = [];
    private ?string $access_token = null;
    private ?string $refresh_token = null;
    private ?int $expires = null;
    private string $url = 'https://loco-app.expan.do';

    /**
     * @return bool
     */
    public function isLogged(): bool
    {
        if (!$this->access_token) {
            return false;
        }
        return true;
    }


    /**
     * @param ?array $token
     */
    public function setToken(?array $token): void
    {
        if ($token !== null) {
            $this->access_token = $token['access_token'] ?? null;
            $this->refresh_token = $token['refresh_token'] ?? null;
            $this->expires = $token['expires'] ?? null;
            $this->token = $token;
        }
    }

    /**
     * @return string[]
     */
    #[ArrayShape(['access_token' => "null|string", 'refresh_token' => "null|string", 'expires' => "int|null", 'token' => "array"])]
    public function getToken(): array
    {
        return [
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires' => $this->expires,
            'token' => $this->token,
        ];
    }

    /**
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        if (!$this->expires) {
            return false;
        }
        return $this->isLogged() && $this->expires < time();
    }

    /**
     * @param int $clientId
     * @param string $clientSecret
     * @return array|null
     */
    public function refreshToken(int $clientId, string $clientSecret): ?array
    {
        $post = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh_token,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => '',
        ];

        $headers = [
            'Accepts-version: 1.0',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . '/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);
        if ($data === false || ($data['error'] ?? null)) {
            $this->access_token = null;
            $this->refresh_token = null;
            $this->expires = null;
            $this->token = [];
            return null;
        }
        $this->setToken([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires' => time() + $data['expires_in'],
        ]);
        return $data;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @param array $payload
     * @return array
     * @throws AppException
     */
    public function graphQL(array $payload): array
    {
        if (!$this->access_token) {
            throw new AppException('Access token is required');
        }

        try {
            $client = new Client([
                'base_uri' => $this->url,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->access_token
                ],
                'verify' => false,
            ]);
            $response = $client->post("api/graphql", [
                'body' => json_encode($payload)
            ]);
            $arrayResponse = json_decode((string) $response->getBody(), true);

        } catch(GuzzleException $e) {
            throw new AppException($e->getMessage());
        }

        if (isset($arrayResponse['errors'])) {
            $messages = [];
            foreach ($arrayResponse['errors'] as $error) {
                $message = $error['message'] . " (";
                foreach ($error['extensions']['validation'] as $key => $validations) {
                    foreach ($validations as $validation) {
                        $message .= $key . ': ' . $validation . ", ";
                    }
                }
                $message .= ")";
                $messages[] = $message;
            }
            throw new AppException(implode("; ", $messages));
        }

        return $arrayResponse;
    }
}
