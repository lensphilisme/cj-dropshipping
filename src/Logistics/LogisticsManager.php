<?php

namespace Lunx\CjDropshipping\Logistics;

use GuzzleHttp\Client;
use Lunx\CjDropshipping\Auth\Authenticator;
use Exception;

class LogisticsManager
{
    private $client;
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->client = new Client();
        $this->authenticator = $authenticator;
    }

    /**
     * Freight Calculation (POST)
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function calculateFreight(array $data)
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/logistic/freightCalculate';
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

            if ($data['result']) {
                return $data['data'];
            } else {
                throw new Exception("Error calculating freight: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Get Tracking Information (GET)
     * @param string $trackNumber
     * @return array
     * @throws Exception
     */
    public function getTrackingInfo(string $trackNumber)
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/logistic/trackInfo';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                ],
                'query' => ['trackNumber' => $trackNumber],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                return $data['data'];
            } else {
                throw new Exception("Error fetching tracking info: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Handle errors.
     * @param Exception $exception
     */
    private function handleError($exception)
    {
        error_log($exception->getMessage());
        echo "An error occurred: " . $exception->getMessage();
    }
}
