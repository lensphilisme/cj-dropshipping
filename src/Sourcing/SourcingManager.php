<?php

namespace Lunx\CjDropshipping\Sourcing;

use GuzzleHttp\Client;
use Lunx\CjDropshipping\Auth\Authenticator;
use Exception;

class SourcingManager
{
    private $client;
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->client = new Client();
        $this->authenticator = $authenticator;
    }

    public function createSourcing(array $data)
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/product/sourcing/create';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['success']) {
                return $data['data'];
            } else {
                throw new Exception("Error creating sourcing: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    public function querySourcing(array $sourceIds)
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/product/sourcing/query';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['sourceIds' => $sourceIds],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['success']) {
                return $data['data'];
            } else {
                throw new Exception("Error querying sourcing: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function handleError($exception)
    {
        error_log($exception->getMessage());
        echo "An error occurred: " . $exception->getMessage();
    }
}
