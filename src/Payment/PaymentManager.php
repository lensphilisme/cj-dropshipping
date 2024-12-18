<?php

namespace Lunx\CjDropshipping\Payment;

use GuzzleHttp\Client;
use Lunx\CjDropshipping\Auth\Authenticator;
use Exception;

class PaymentManager
{
    private $client;
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->client = new Client();
        $this->authenticator = $authenticator;
    }

    /**
     * Get Balance (GET)
     * @return array
     * @throws Exception
     */
    public function getBalance()
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/shopping/pay/getBalance';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                return $data['data'];
            } else {
                throw new Exception("Error fetching balance: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Pay Balance (POST)
     * @param string $orderId
     * @return void
     * @throws Exception
     */
    public function payBalance(string $orderId)
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/shopping/pay/payBalance';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['orderId' => $orderId],
            ]);

            $data = json_decode($response->getBody(), true);

            if (!$data['result']) {
                throw new Exception("Error paying balance: " . $data['message']);
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
